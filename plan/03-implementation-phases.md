# Implementation Phases

Small, incremental steps. Each phase is independently shippable: the app works, tests are green, Pint and PHPStan pass. Do not pull work forward from later phases — if a phase seems to need something from a later one, the plan is wrong and should be corrected first.

Migrations are created in the phase that introduces the table. Columns for stated future use (e.g. `home_users.role`, `recurring_rules.reminder_days_before`) are included in their table's original migration because carrying a column is free; whole tables are never created early.

```text
M1 Foundation        P0 scaffold · P1 Filament + auth · P2 homes + tenancy + activity log
M2 Core data         P3 locations · P4 assets · P5 work items
M3 Daily usefulness  P6 results + Complete action · P7 dashboard v1 · P8 recurring rules
M4 Enrichment        P9 tags · P10 contacts + expenses · P11 photos + timelines · P12 links
M5 Integrations      P13 ICS feed · P14 cost forecast
M6 Polish            P15 steps · P16 seed data + duplicate · P17 i18n + mobile
```

MVP = M1–M5 + P16 + P17. P15 is an optional enhancement that can ship after first real-world use.

Activity logging is **mandatory and starts in Phase 2** — before the first domain model exists — because the log only records from installation onward, and "what happened to this asset in the past" is a core purpose of the app, not a polish feature. Every phase that creates a domain model adds `LogsActivity` to it in that same phase; the timeline UI ships with the asset detail page work in Phase 11.

---

## Milestone 1 — Foundation

### Phase 0: Project scaffold

**Goal:** an empty Laravel app with the full quality toolchain, so every later phase lands on green CI.

- Create the Laravel app (latest stable — Laravel 13.x on PHP 8.5 at time of writing) in a fresh repo; PostgreSQL 18 via a local `docker-compose.yml` (app runs natively, only Postgres in Docker).
- Install and configure Pest, Pint, PHPStan + Larastan (level 6), Rector.
- GitHub Actions workflow: pint --test, phpstan, pest, on every push.
- Set `app.timezone = UTC`. Create `config/maintenance.php` with `recently_completed_days => 14`.
- Create the `app/Domain/Maintenance` and `app/Domain/Integrations` directory skeleton.

**Done when:** fresh clone + `composer install` + one command brings the app up; CI is green on an empty test suite.

### Phase 1: Filament panel + authentication

**Goal:** a logged-in user sees an empty panel, protected by two-factor authentication.

- Install Filament (5.x at time of writing), one panel mounted at `/`, with login, password reset, and profile page enabled.
- Auth is **Filament's built-in panel authentication** — no Breeze, Jetstream, or Fortify (rationale in `02-technical-decisions.md` → Authentication).
- **Two-factor authentication (must-have):** Filament's built-in app authentication (TOTP + recovery codes), enrolled from the profile page — encouraged, not forced. Email-code MFA waits until a mailer exists.
- `users` migration: standard Laravel columns + `locale` + the MFA storage columns.
- **No registration route** (decision S8). Accounts are created via a `users:create` artisan command built on Laravel Prompts: interactive and guided, validates with re-prompts instead of aborting, clear success/error output, options for scripted use.

**Done when:** created user can log in and out; unauthenticated requests are redirected to login; TOTP can be enrolled from the profile and challenges at next login; `users:create` produces a working account from the CLI.

### Phase 2: Homes + tenancy

**Goal:** all future data is home-scoped from the very first domain table.

- `homes` migration: name, address fields (`address_line_1`, `address_line_2`, `city`, `postal_code`, `country`, all nullable), GPS coordinates (`latitude`, `longitude`, nullable decimals), `default_currency` (ISO 4217, default `HUF`), `default_locale` (default `hu`), `timezone` (default `Europe/Budapest`), notes.
- `home_users` migration: `home_id`, `user_id`, `role` (owner/adult/child/guest — stored, not enforced), `unique(home_id, user_id)`.
- Configure Filament tenancy with `Home` as tenant: tenant registration page ("create your home"), tenant switcher.
- `BelongsToHome` trait (tenant global scope + `home_id` auto-fill on create).
- Members page inside the panel: the owner creates member accounts (name, email, password, role) and can detach them.
- Install and configure `spatie/laravel-activitylog` **now**, so no domain model ever exists without history. Convention from here on: every domain model adds `LogsActivity` in the phase that creates it (locations in P3, assets in P4, work items in P5, …). First consumers: `Home` and home membership changes (who added/removed whom).

**Done when:** Pest test proves user in home A cannot query or route to home B's data; a second home can be created and switched to; adding a member leaves an activity log entry with the acting user as causer.

---

## Milestone 2 — Core data

### Phase 3: Locations

**Goal:** the household's physical structure can be entered.

- `locations` migration per schema (`parent_id` self-reference `restrict`, `type` enum, `sort_order`).
- `LocationType` PHP enum with lang-file labels.
- Filament resource: list grouped/indented by parent, parent select scoped to the same home, reorderable by `sort_order`.

**Done when:** the full seed location tree from `prompts/06` can be entered by hand and displays hierarchically.

### Phase 4: Assets

**Goal:** the asset registry with QR-ready identity.

- `assets` migration per schema **minus** nothing — includes `slug` (unique), `replaced_by_asset_id`, `parent_id`, condition fields. Enums: `AssetType`, `AssetStatus`, `AssetCondition`.
- Slug generation: 8-char random Crockford base32 in a `creating` observer with uniqueness retry (decision in `02-technical-decisions.md`).
- Observer invariant: `status = replaced` ⇔ `replaced_by_asset_id` set.
- Filament resource: list (filterable by location, status, condition), form, view page. "Replace with…" header action sets successor + status in one step.
- `/a/{slug}` route → auth → redirect to the asset's Filament view URL in its home's tenant context.
- `condition_updated_at` auto-set when condition changes.

**Done when:** an asset created in the UI resolves via `/a/{slug}` on a phone; replacement lineage is enforced by tests.

### Phase 5: Work items (core lifecycle)

**Goal:** one-off tasks can be tracked from creation to completion — the app is already minimally useful.

- `work_items` migration per schema with the simplifications from `01-mvp-scope-and-simplifications.md`: single `asset_id` (S1), **no** `severity` (S2), **no** `actual_minutes` (S3), plus `doer` (diy/contractor, nullable) and `needs_special_equipment` (boolean). CHECK constraint on the due window pair. Enums: `WorkItemType` (**four values**, S11: task, inspection, repair, replacement), `WorkItemStatus`, `WorkItemPriority`, `WorkItemSource`, `WorkItemDoer`.
- `WorkItemDueStateService`: computes `upcoming` / `due_now` / `overdue` / `needs_attention` / `completed_recently` and the urgency tier (0–33/34–66/67–90/91–100/overdue) in the home's timezone. **Exhaustive Pest coverage** — this is one of the two core services.
- Filament resource: list with computed due-state badge column, filters (status, type, priority, asset, location, doer, needs special equipment), form with either hard due date or due window (mutually exclusive UI), status transitions per the allowed matrix in `prompts/03`.

**Done when:** a work item moves draft → open → completed/failed/blocked/cancelled through the UI; due-state service tests cover every boundary (window edges, hard due day, TZ midnight).

---

## Milestone 3 — Daily usefulness

### Phase 6: Results + composite "Complete" action

**Goal:** the single highest-value mobile flow: "I just did this — mark it done."

- `work_item_results` migration per schema. `WorkItemResult` enum.
- Composite Filament action on work items (table row + view page), one mobile-friendly form: result (default `done`), optional summary, optional measured value + unit, and — when result is `failed` / `not_fixed` / `needs_followup` — a pre-checked "create follow-up" toggle.
- Submit: update status to the matching lifecycle state, create the result row, optionally create the follow-up (`source = followup`, `parent_id` set). (Photo upload joins this form in Phase 11; recurrence advancement in Phase 8.)
- "Current result" = latest row by `created_at`, shown on the view page with prior results as history.

**Done when:** the whole flow works one-handed on a phone viewport; result history accumulates over repeated fail/retry cycles.

### Phase 7: Dashboard v1

**Goal:** opening the app answers "what needs attention?" in one glance.

- `DashboardQueryService` backed by `WorkItemDueStateService`.
- Filament dashboard widgets in order: **Needs Attention** (failed + blocked), **Overdue**, **Due Now**, **Upcoming Next 30 Days**, **Recently Completed** (last 14 days, config constant).
- Visual treatment + sort per the urgency table in `prompts/07`, tiebreak by priority then due date.
- Each row links to the work item; the Complete action is available directly from dashboard rows.

**Done when:** seeded scenario data appears in the correct sections with correct badges; empty states are friendly, not blank.

### Phase 8: Recurring rules + scheduler

**Goal:** the app starts generating work instead of only recording it. This is the heart of the product.

- `recurring_rules` migration (definition + recurrence parameters; no `severity` per S2, no `next_due_at`; includes `doer`, `needs_special_equipment`, `reminder_days_before` unused per S10) **plus `recurring_rule_schedules`** (per S12: `asset_id` nullable, `next_due_at`, `last_completed_at`, `is_active`, unique on rule + asset) — one rule defines a procedure once, schedule rows attach it to any number of assets with independent due dates. Enums for `interval_unit`, `reschedule_from`, `window_strategy`, `anchor_season`.
- `RecurrenceService`: next-occurrence math for all `window_strategy` × `reschedule_from` combinations, season year-boundary convention, first-activation rule, `only_after_success` gating on `done`/`passed`/`fixed`/`skipped`. **Exhaustive Pest coverage** — the other core service.
- Daily scheduled job: for each active schedule row with `next_due_at <= today + auto_create_days_before_due` (default 14), materialize a work item (`status = open`, `source = recurring_rule`, copying title/description/type/priority/location/estimates/doer/equipment/free-text fields from the rule, `asset_id` from the schedule row) unless an open one for that occurrence already exists (idempotent).
- Hook into the Complete action: an advancing result on a rule-generated work item recalculates `next_due_at` **on that work item's schedule row only** — sibling assets on the same rule are untouched.
- Filament resource for rules: form with strategy-dependent conditional fields, attached-assets management (attach/detach/pause per asset), earliest "next due" column, activate/deactivate toggle.

**Done when:** a monthly rolling-window rule generates an item, completing it advances its schedule row correctly; failing it does not; a rule attached to 3 assets generates 3 independent work items and completing one advances only that asset's schedule; season and `previous_due_at` worked examples from `prompts/04` pass as literal test cases.

---

## Milestone 4 — Enrichment

### Phase 9: Tags

- `tags` (+ `unique(home_id, slug)`) and `taggables` (with `created_at`) migrations. `TagType` enum.
- Filament resource for tags (name, type, color, sort order); tag multi-select on work items, assets, recurring_rules, locations, contacts, expenses.
- Tag filter on work item and asset lists.
- Rule-generated work items copy the rule's tags.

**Done when:** tagging and filtering round-trips; generated items inherit rule tags.

### Phase 10: Contacts + expenses

- `contacts`, `work_item_contacts` (role enum), `expenses` migrations per schema; add `preferred_contact_id` (nullable, set null) to `recurring_rules` — generated work items carry who to call.
- Filament resources: contacts (with click-to-call `tel:` links on mobile); expenses (standalone list + relation manager on work items and assets).
- Work item view shows actual cost = expense sum per currency next to `estimated_cost`.
- Contacts attachable to work items with roles (performer, inspector, …); asset view shows past performers from `work_item_contacts` history.

**Done when:** "which contractor performed this?" is answerable from a work item and from the asset's past-performers list; a rule's preferred contact appears on its generated items; expense sums render per currency.

### Phase 11: Photos + asset timelines

- Install `spatie/laravel-medialibrary`. `photos` metadata table per schema; `Photo` morphs to assets, work_items, locations, contacts; each `Photo` owns one media item (original + thumbnail conversion).
- Upload UI (camera-capable file input) on asset, work item, contact, location pages; `PhotoType` enum.
- Photo timeline section on the asset view page, ordered by `taken_at` fallback `created_at`.
- **Activity timeline panel** on the asset view page (sibling to the photo timeline) and on work items — rendering the history the activity log has been recording since Phase 2.
- Add optional photo upload to the composite Complete action (completing Phase 6's form).
- Contact avatar = latest `profile` photo.

**Done when:** phone-camera upload works in the Complete action; asset timeline shows photos chronologically; "Anna marked the FI relay test as failed at 18:43" style entries appear on the asset page, including events from before this phase.

### Phase 12: Paperless links + external links

- `paperless_links` and `external_links` migrations with the **symmetric** polymorphic target set (assets, work_items, locations, contacts, recurring_rules, expenses). Type + provider enums.
- Relation managers on all target resources; links open in a new tab; comment field surfaced in the list ("why is this useful").

**Done when:** an asset shows its manual (Paperless link) and manufacturer page (external link); a work item's service report reference is one tap away on mobile.

---

## Milestone 5 — Integrations and insight

### Phase 13: ICS calendar feed

- `calendar_feeds` minimal migration (S4): `home_id`, `user_id` nullable, `name`, `token` (unique), `is_active`, `lookahead_days` (default 180).
- Auto-create one feed per home on home creation.
- Public route `GET /calendar/{token}.ics` (unguessable token is the auth).
- `IcsFeedGenerator` + `CalendarEventBuilder`: events for open work items (window start / 85% / end / overdue; single event for hard due dates), emoji title prefixes by type, description from the placeholder list in `prompts/05`; **plus virtual projected events** for unmaterialized rule occurrences within the lookahead (decision in `02-technical-decisions.md`).
- "Regenerate feed URL" action rotating `token`.
- Validate output against an ICS validator in tests.

**Done when:** subscribing in a real calendar client shows due windows and projected future occurrences; rotating the token kills the old URL.

### Phase 14: Cost forecast

- `CostForecastService`: forward windows (30d / 3m / 6m / 12m / next calendar year / custom) from open work items' `estimated_cost` + projected rule occurrences' `estimated_cost`; historical view from expense sums. Per-currency buckets, default currency first.
- Category inference chain per `02-technical-decisions.md` (expense tag first, `cost-general-maintenance` fallback).
- Dashboard widget "Expected Cost Next 3 Months" + a report page with window selector and category breakdown.

**Done when:** forecast matches hand-computed fixtures, including a mixed-currency case showing two buckets.

---

## Milestone 6 — Polish

### Phase 15: Checklist steps (optional)

- `recurring_rule_steps` and `work_item_steps` migrations; rule steps copied onto generated work items.
- Repeater on the rule form; tappable checklist (pending/completed/skipped/failed per step) on the work item view.
- Steps never compute the parent result (#19); Complete action warns when required steps aren't completed, but never blocks.

**Done when:** a 6-step inspection rule generates an item with a working mobile checklist.

### Phase 16: Seed data + "Duplicate N times"

- Opt-in seeding on home creation ("start with a Hungarian family-house template" checkbox): tags, location tree, assets, and recurring rules from [`04-seed-data.md`](./04-seed-data.md) — the cleaned-up template that applies the location/asset rule, the four-type list, and multi-asset schedule rows (including the smoke-detector example, closing `todo-items.md` #20).
- "Duplicate N times" asset action: count + numbering pattern (`{n}` zero-padded), attaches each clone to the source asset's rules via new schedule rows — rules are never cloned.

**Done when:** a fresh seeded home immediately shows sensible upcoming work on the dashboard; duplicating a smoke detector ×4 yields 4 assets scheduling independently under the same rules.

### Phase 17: i18n + mobile pass

- Sweep: every user-facing string through `__()`; complete `lang/hu` and `lang/en` files; enum labels translated.
- Locale resolution middleware: user → home default → app default.
- Mobile QA pass on the money flows: dashboard → work item → Complete action → photo upload; `/a/{slug}` scan-to-asset; click-to-call.

**Done when:** switching user locale flips the whole panel; the Complete flow works one-handed on a real phone.

---

## After MVP

Deployment (Docker image, Unraid template, compose file), notifications, Home Assistant, Paperless webhooks, QR label sheets, permissions enforcement, MNB currency conversion — all tracked in [`../prompts/10-future-plans.md`](../prompts/10-future-plans.md). Nothing in M1–M6 blocks any of them.
