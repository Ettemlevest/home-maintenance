# Home Maintenance App — Implementation Plan

This directory turns the planning documents in [`../prompts/`](../prompts/) into an actionable, incremental implementation plan. The source documents remain authoritative for feature intent; this plan decides **what gets built, in what order, and what gets simplified or deferred**.

## Documents

| File | Purpose |
|---|---|
| [`01-mvp-scope-and-simplifications.md`](./01-mvp-scope-and-simplifications.md) | What is in the MVP, what is simplified relative to the source docs, and what is deferred |
| [`02-technical-decisions.md`](./02-technical-decisions.md) | Resolved technical decisions, including answers to the open items in `prompts/todo-items.md` |
| [`03-implementation-phases.md`](./03-implementation-phases.md) | The step-by-step build order: 6 milestones, 17 small phases, each independently shippable |
| [`04-seed-data.md`](./04-seed-data.md) | The opt-in "Hungarian family house" seed template — also the worked example of the data model (location/asset rule, four work item types, multi-asset rules) |

## Guiding principles

1. **Every phase leaves the app in a working, usable state.** No phase depends on a future phase to be coherent.
2. **Simplicity beats completeness.** The source docs describe the full vision; the MVP cuts anything that adds administration burden without daily value. A household member should be able to complete a chore from their phone in under 30 seconds.
3. **Schema decisions are made early, UI decisions late.** Columns that are cheap to carry (e.g. `assets.replaced_by_asset_id`, `home_users.role`) are created in their phase's migration even when no UI uses them yet. Whole tables are *not* created until their phase.
4. **The business logic core (`RecurrenceService`, `WorkItemDueStateService`) gets real test coverage.** Everything else is tested pragmatically.
5. **Follow Laravel and Filament conventions.** Custom architecture only where the domain demands it (see `prompts/08-architecture-and-business-logic.md` for the module layout).

## Tech stack

Latest stable at implementation start (per `prompts/01-overview-and-requirements.md`):

- PHP 8.5+, Laravel 13.x
- FilamentPHP 5.x (single panel, native multi-tenancy)
- PostgreSQL 18.x
- PestPHP, Laravel Pint, PHPStan + Larastan, Rector
- `spatie/laravel-medialibrary` (photos), `spatie/laravel-activitylog` (audit history, recording from Phase 2)

## How to use this plan

Work through `03-implementation-phases.md` top to bottom. Each phase lists its goal, tasks, what is explicitly out of scope, and a definition of done. When a phase is done (definition of done met, tests green, Pint/PHPStan clean), commit and move on. Do not pull work forward from later phases.
