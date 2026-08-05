# Technical Decisions

Resolved decisions the implementation follows. Numbers in parentheses reference the open items in [`../archive/todo-items.md`](../archive/todo-items.md); every schema-shaping item from that review is settled here so implementation never has to guess.

## Authentication (#22)

- **Filament's built-in panel authentication** (login, password reset, profile) on Laravel's standard session guard. No Breeze, Jetstream, or Fortify — Filament 5 covers the full surface natively and a second auth scaffold would fight it.
- **Two-factor authentication is a must-have**, available from Phase 1: Filament's built-in MFA with app authentication (TOTP, Google Authenticator-compatible) plus recovery codes. Enrollment via the profile page; encouraged but not forced at first login. Email-code MFA stays off until the deployment work configures a mailer.
- No self-registration; accounts are created by the interactive `users:create` artisan command (Laravel Prompts — the bootstrap path for the first user) or by the home owner inside the panel (see S8 in `01-mvp-scope-and-simplifications.md`).

## Tenancy and home scoping (#12, #14)

- **Filament native multi-tenancy** with `Home` as the tenant model. Filament handles the active-home context, the home switcher, and URL scoping (`/{tenant}/...`) — no custom session plumbing.
- Every top-level domain table carries `home_id` and a `BelongsToHome` trait applying a tenant global scope.
- Child tables without `home_id` (`work_item_steps`, `work_item_results`, `work_item_contacts`, `recurring_rule_steps`) are **only ever read through their parent relationship**. They get no Filament resources of their own (relation managers / repeaters only), and no route model binding. This convention is stated in this repository's `AGENTS.md`/`CLAUDE.md` once the app is scaffolded (Phase 0).
- `home_users` gets `unique(home_id, user_id)` (#15).

## Location vs asset boundary

The two concepts overlap for building parts (roof, facade, garden taps). The rule, surfaced in UI helper texts and the app docs:

> **If it gets installed, degrades, and could be replaced as a unit, it's an asset. If you'd point at it on a floor plan to say where something is, it's a location.**

Overlap cases get both, with the asset placed in the location: location "Tető" contains asset "Tetőhéjazat". A thing that never gets serviced or replaced as a unit needs no asset row — the location is enough. The seed data in [`04-seed-data.md`](./04-seed-data.md) applies this rule throughout and doubles as the worked example.

## Dates, times, and timezone (#8, #9)

- Storage is **UTC** (`timestamptz`), display and date math use the **home's timezone**, stored as `homes.timezone` (default `Europe/Budapest`).
- Column types:
  - `date`: `hard_due_at`, `due_window_start`, `due_window_end`, `next_due_at`, `installed_at`, `warranty_until`, `spent_at`, `paperless_created_at`, `followup_due_at`
  - `timestamptz`: `completed_at`, `condition_updated_at`, `taken_at`, all `created_at`/`updated_at`
- All recurrence math (`RecurrenceService`, `WorkItemDueStateService`) evaluates "today" in the home's timezone. This is what makes "due during June" unambiguous near midnight.

## Foreign key delete behavior (#7)

Principle: **history is never silently destroyed**. Deleting is for mistakes; ending a thing's life is a status change (`inactive`, `discarded`, `cancelled`).

| Relationship | On delete |
|---|---|
| Owned children: `work_item_steps`, `work_item_results`, `work_item_contacts`, `recurring_rule_steps`, `photos`, `paperless_links`, `external_links`, `taggables` → parent | `cascade` |
| `locations.parent_id`, `assets.parent_id`, `work_items.parent_id` → parent | `restrict` (delete children first) |
| `assets.location_id`, `work_items.location_id`, `work_items.asset_id`, `expenses.work_item_id`, `expenses.asset_id`, `expenses.contact_id`, `work_items.recurring_rule_id`, `assets.replaced_by_asset_id`, user references (`assigned_to`, `created_by`, `completed_by`) | `set null` |
| `contacts` referenced by `work_item_contacts` | `restrict` (pivot rows are history — detach or keep the contact) |
| Anything → `homes` | `cascade` (deleting a home deletes everything in it; guarded by a confirmation modal) |

## Enums

All closed value sets (`status`, `condition`, `type`, `priority`, `result`, `interval_unit`, `reschedule_from`, `window_strategy`, roles, link types, photo types) are **PHP backed string enums** in `app/Domain/.../Enums/`, stored as strings — not Postgres enum types (painful to migrate) and not integers (unreadable in psql). Labels come from lang files via a `HasLabel` implementation so HU/EN works from day one.

## Asset slugs (#17)

- 8-character random lowercase base32 string (Crockford alphabet, no vowels — avoids accidental words), generated in a model `creating` hook with a uniqueness retry loop.
- Never derived from the name (names change; printed QR stickers must not).
- `/a/{slug}` is a public-URL-shaped route that requires auth and redirects to the asset's Filament view page within the correct tenant.

## Work item semantics

- **Four work item types** (S11 in `01-mvp-scope-and-simplifications.md`): `task`, `inspection`, `repair`, `replacement`. Each has distinct behavior and a natural result vocabulary: task → done/skipped, inspection → passed/failed (absorbs `test`), repair → fixed/not_fixed, replacement → done (absorbs `installation`/`upgrade`, touches asset lineage). `maintenance` and `cleaning` fold into `task` (the `cleaning` topic tag covers filtering); `project` is dropped — `parent_id` groups sub-tasks under an umbrella work item.
- Status = lifecycle, result = outcome, exactly as `specs/03`. The identical label names (`failed` status vs `failed` result) are kept — renaming (#2) was considered and rejected: a status/result matrix in the app repo docs is enough, and the enum types (`WorkItemStatus::Failed` vs `WorkItemResult::Failed`) disambiguate in code.
- **Current result = latest `work_item_results` row by `created_at`.**
- CHECK constraint on `work_items`: `due_window_start` and `due_window_end` are both null or both set, with `start <= end` (#16).
- Steps never compute the parent's result (#19). The Complete action's result field is always an explicit user choice; if required steps are failed/pending the form shows a non-blocking warning.
- Follow-up creation (#13): a side effect of the **Complete action**, not a background job. When the chosen result is `failed`, `not_fixed`, or `needs_followup`, the form shows a pre-checked "create follow-up work item" toggle; submitting creates an `open` work item with `source = followup`, `parent_id` set to the original.
- `completed_recently` window: app config constant `maintenance.recently_completed_days = 14` (#21).
- Dashboard sort within an urgency tier: `priority` descending, then due date ascending (#28).

## Recurrence semantics

- **One rule, many schedules.** A `recurring_rules` row holds the definition (title, description, type, priority, estimates, free-text fields, steps) and the recurrence *parameters* (interval, strategy, anchors). The per-target *state* lives in `recurring_rule_schedules` rows: `recurring_rule_id`, `asset_id` (nullable), `next_due_at`, `last_completed_at`, `is_active`. A rule attached to 4 smoke detectors has 4 schedule rows; completing detector #3's work item advances only detector #3's row. A rule with no asset target (e.g. gutter cleaning against a location) has a single schedule row with `asset_id = null`. `next_due_at` therefore lives on the schedule row, **not** on the rule. This kills the duplicated-rule problem: the procedure is defined once, and attaching another asset is one row, not a cloned rule.
- Occurrence math otherwise exactly as `specs/04`, including the season year-boundary convention.
- First-time activation for **all** window strategies (#10, generalizing the `calendar_season` rule): if today (home TZ) is inside the current window, the first occurrence uses the current window; otherwise the next upcoming one. For `rolling_window` with `reschedule_from = completed_at` and no prior completion, the first window starts today.
- Recurrence-advancing results: `done`, `passed`, `fixed`, `skipped`. Anything else with `only_after_success = true` leaves the schedule row's `next_due_at` untouched; the follow-up toggle in the Complete action covers the retry path.
- `auto_create_days_before_due` defaults to `14` when null.

## ICS feed honesty (#5)

`IcsFeedGenerator` emits, within `lookahead_days` (default 180):

1. Events for **materialized** open work items (window start / 85% / window end / overdue for windows; single event for `hard_due_at`).
2. **Virtual projected events** for each active recurring rule's occurrences that are not yet materialized, computed by the same `RecurrenceService` math. Projected events get a distinct marker (e.g. `〰️` prefix) and only a window-start event, so the calendar shows the real long-term picture without pretending precision.

This resolves the 180-day-lookahead vs 14-day-materialization contradiction in favor of projection.

## Costs and currency (#18, #33)

- All `currency` columns are ISO 4217 3-letter codes; `homes.default_currency` default `'HUF'`.
- No conversion in MVP. `CostForecastService` groups totals **per currency**; the home's default currency bucket is shown first. Mixed-currency homes see two numbers rather than one wrong number.
- Actual cost of a work item is always `SUM(expenses.amount)` grouped by currency — no `actual_cost` column.
- Cost category inference order as `specs/08`, with **direct tags on the expense** checked first (#25), then work item tag → rule tag → primary asset tag → asset type → location tag → performer contact type → `cost-general-maintenance`.

## DIY vs contractor, and equipment

- `doer` enum (`diy` / `contractor`, nullable = undecided) on `work_items` and `recurring_rules`; generated items copy it. Replaces the `contractor-needed` tag from the draft seed data. Drives the "what can I do myself this weekend" vs "what needs booking" filters.
- `needs_special_equipment` boolean on `work_items` and `recurring_rules` (what exactly goes in the existing `tools_text`). Filterable, so tasks waiting on the same rented lift/scaffold can be batched into one weekend.
- `preferred_contact_id` (nullable, `set null` on delete) on `recurring_rules`, added once contacts exist (Phase 10): generated work items carry who to call. "Who did it before" needs no schema — `work_item_contacts` with role `performer` already records it; the asset page surfaces past performers.

## Links and photos

- `paperless_links` and `external_links` accept the **same** polymorphic target set: `assets`, `work_items`, `locations`, `contacts`, `recurring_rules`, `expenses` (#4 — union of both lists).
- `paperless_document_id` null means "URL points at a Paperless view that is not a single document" (saved search, tag view) (#26).
- `photos` table owns app metadata (`type`, `taken_at`, `caption`, `notes`, `sort_order`); `spatie/laravel-medialibrary` owns files, conversions, thumbnails. One `Photo` row has one media item.
- Contact display photo = latest `type = profile` photo by `taken_at`, fallback `created_at` (no `profile_photo_id` column), per `specs/02`.

## Locale (#3)

- `homes.default_locale` (default `'hu'`) added. Effective UI locale: `users.locale` → `homes.default_locale` → `config('app.locale')`.

## Asset replacement lineage (#31)

- Model-level invariant enforced by an observer: setting `status = replaced` requires `replaced_by_asset_id`, and setting `replaced_by_asset_id` transitions status to `replaced`. Filament's asset form exposes this as a single "Replace with…" action rather than two raw fields.

## Misc schema details

- `taggables` gets `created_at` (#24).
- Indexes as listed in `specs/02`, plus `unique(home_id, user_id)` on `home_users` and `recurring_rule_schedules(recurring_rule_id, asset_id)` unique, minus indexes for dropped columns (`severity`), the dropped `work_item_assets` table, and `recurring_rules.next_due_at` (now on the schedule rows).
- "Duplicate N times" numbering pattern zero-pads by default: `{n}` renders `01`–`09` when N ≥ 10 (#32). The action clones the asset and **attaches the clones to the source asset's rules** (new schedule rows) — rules themselves are never cloned.
- Activity logging is **mandatory and installed in Phase 2**, before the first domain model exists — the log only records from installation onward, and asset history is a core purpose, not polish. Each model adds `LogsActivity` in the phase that creates it; covered models: `Home` (+ membership changes), `Location`, `Asset`, `WorkItem`, `WorkItemResult`, `RecurringRule` (#6), `Contact`, `Expense`. Timeline UI ships with the asset detail page (Phase 11).
- Required Postgres extensions: none for MVP (FTS deferred with S5). Document `pg_trgm` as the future search prerequisite (#27).

## Application structure

Per `specs/08`, a modular monolith:

```text
app/
  Domain/
    Maintenance/   Models, Enums, Services (WorkItemDueStateService, RecurrenceService,
                   CostForecastService, DashboardQueryService), Observers
    Integrations/
      Calendar/    CalendarFeed, IcsFeedGenerator, CalendarEventBuilder
      Paperless/   PaperlessLink
      ExternalLinks/ ExternalLink
      Photos/      Photo, PhotoTimelineService
  Filament/        Resources, Pages, Widgets, Actions (standard Filament layout)
```

Boundary rule: Maintenance decides *what things mean* (due, overdue, advance); Integrations decide *how they are shown elsewhere*. No integration-specific columns on core tables.

## Testing focus

- **Heavy, exhaustive Pest coverage**: `RecurrenceService` (every strategy × reschedule mode × year/season boundaries), `WorkItemDueStateService` (every computed state, boundary days, home-TZ edges).
- **Solid coverage**: tenancy isolation (user in home A cannot see home B data through any resource), Complete action side effects, ICS output validity.
- **Pragmatic coverage**: everything else — one happy-path feature test per Filament resource.
- Pint + PHPStan (Larastan, level 6+) run in CI from phase 0.
