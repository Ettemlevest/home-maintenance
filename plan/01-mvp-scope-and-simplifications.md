# MVP Scope and Simplifications

The source docs (`prompts/01`–`10`) describe the full product vision. This document records what the MVP actually builds, and — importantly — where it **deliberately simplifies** relative to the source docs. Every simplification here favors less administration over more capability, per the core value statement: *minimum data entry, maximum long-term usefulness*.

## In MVP (built by the phases in `03-implementation-phases.md`)

- Multiple homes, multiple users per home (Filament native tenancy)
- Filament built-in authentication with two-factor authentication (TOTP + recovery codes)
- Hierarchical locations
- Assets with status, condition, slug + `/a/{slug}` QR-target route
- Work items with the 6-status lifecycle, due windows and hard deadlines, computed due states
- Work item results (multiple per item, latest wins)
- Composite mobile "Complete" action (result + photo + measured value + summary in one form)
- Recurring rules with all four window strategies and all three reschedule modes, attachable to **multiple assets** with independent per-asset schedules
- DIY vs contractor marking (`doer`), special-equipment flag for batching rented-tool work, preferred contact on rules
- Daily scheduled job materializing upcoming work items
- Tags + polymorphic tagging
- Contacts (with `work_item_contacts` roles) and expenses
- Photos via medialibrary, with photo timeline on assets
- Activity log (who-did-what-when) recording from the very first domain model, with timeline panels on assets and work items
- Paperless-ngx links and external links (reference-only, symmetric polymorphic targets)
- One ICS calendar feed per home, token-rotatable
- Dashboard: Needs Attention / Overdue / Due Now / Upcoming 30 Days / Recently Completed / Expected Cost
- Simple cost forecasting (estimated costs forward, expense sums backward)
- Seed data (tags, locations, assets, recurring rules — see [`04-seed-data.md`](./04-seed-data.md)) + "Duplicate N times" asset action
- Bilingual HU/EN UI via standard Laravel lang files
- Checklist steps on recurring rules and work items (late phase, optional)

## Simplified relative to the source docs

These are deviations from `prompts/02`–`08`. Each is a deliberate call; if real usage proves one wrong, the schema notes how to add it back.

| # | Source doc says | MVP does instead | Rationale |
|---|---|---|---|
| S1 | `work_items.asset_id` **and** a `work_item_assets` pivot with roles (`02`) | Only `work_items.asset_id`. No pivot table. | Resolves the ambiguity flagged in `todo-items.md` #1. A household work item touching multiple assets is rare; when it happens, create sibling work items or mention it in the description. Pivot can be added later without touching existing data. |
| S2 | `work_items.severity` + `recurring_rules.severity` (`02`) | Column dropped entirely. | `priority` alone is enough for a household (`todo-items.md` #29: `type=cleaning, severity=critical` is meaningless). One less field on every form. |
| S3 | `work_items.actual_minutes` (`02`) | Column dropped. | No input path existed (`todo-items.md` #11) and asking "how long did it take?" on every completion is administration for its own sake. `estimated_minutes` stays (used for planning). |
| S4 | `calendar_feeds` with 10+ filter/template/event-toggle columns (`02`) | Minimal feed: `name`, `token`, `is_active`, `lookahead_days`. Event formatting (emoji prefixes, description template) is hard-coded with sensible defaults in `IcsFeedGenerator`. | One feed per home with good defaults covers the stated UX (`05`). Filter and template columns get added only if someone actually needs per-user filtered feeds. |
| S5 | `SearchService` with tsvector/pg_trgm full-text search (`08`) | Filament's built-in global search across resources (assets, work items, contacts, locations, expenses). | Filament global search is free and covers "find the thing by name" for a household-sized dataset. Postgres FTS is post-MVP if fuzzy matching proves necessary. |
| S6 | Filament translation manager plugin (`08`) | Plain Laravel lang files (`lang/hu/`, `lang/en/`), committed to the repo. | The household picks a language and stays there; a runtime translation-editing UI is admin overhead. |
| S7 | Steps as core work item feature | Steps built in a late, optional phase (M6). The app is fully usable without them. | Most chores are single-step. Building steps early would drag checklist UI complexity through every earlier phase. |
| S8 | Registration/invite flow unspecified (`todo-items.md` #22) | No self-registration. The home owner creates member accounts from a Users page inside the panel. | Simplest possible auth surface for a self-hosted family app. Invite links are post-MVP. |
| S9 | In-app Filament notifications (`08`) | Not in MVP. The dashboard and the ICS feed are the two notification surfaces. | Matches `05`: "ICS feed is the only notification surface in MVP". |
| S10 | `reminder_days_before` consumed by a daily reminder job (`08`) | Column exists on `recurring_rules` but nothing consumes it yet. | No delivery channel is committed in MVP (`10`); carrying the column is free. |
| S11 | 10 work item types (`02`, `03`) | **4 types: `task`, `inspection`, `repair`, `replacement`.** `maintenance`/`cleaning` → `task` (+ tags), `test` → `inspection`, `installation`/`upgrade` → `replacement`, `project` → an umbrella work item with children via `parent_id`. | A type earns its place only if the app behaves differently for it. Ten overlapping labels force pointless entry-time decisions; four map one-to-one onto distinct result vocabularies. |
| S12 | One recurring rule per asset; "Duplicate N times" clones rules (`06`, `07`) | **One rule, many schedule rows** (`recurring_rule_schedules` with per-asset `next_due_at`). Duplicate N attaches clones to the source's existing rules. | Defines a procedure once for 9 smoke detectors instead of 9 near-identical rules; a procedure change is one edit, not nine. |

## Additions relative to the source docs

- `homes.latitude` / `homes.longitude` (GPS coordinates; stored, no MVP consumer)
- `doer` (`diy`/`contractor`) on work items and recurring rules — replaces the draft `contractor-needed` tag
- `needs_special_equipment` flag on work items and recurring rules — batching filter for rented-tool work
- `recurring_rules.preferred_contact_id` — generated items carry who to call

## Deferred (post-MVP, per `prompts/10-future-plans.md`)

Unchanged from the source docs — these stay deferred and this plan adds nothing to them:

- Home Assistant integration; Paperless-ngx webhook receiver; push notification channels
- Structured measurements with auto pass/fail; per-step materials/tools
- MNB exchange-rate conversion
- Role-based permission enforcement (`home_users.role` is stored, not enforced — including for `child`/`guest`)
- Niimbot QR label sheet generation (the `/a/{slug}` target ships in MVP)
- Docker image, Unraid template, docker-compose, backup automation, `home:export`

## Non-goals

Unchanged from `prompts/01`: no inventory, procurement, accounting, document storage, tool tracking, CalDAV write sync, SaaS multi-tenancy, or vehicle maintenance.
