# Home Maintenance — Repository Conventions

Read [`prompts/README.md`](./prompts/README.md) first: the implementation plan lives in `prompts/plan/`, the product vision in `prompts/specs/`. **The plan overrides the specs.** Find the current phase in `prompts/plan/03-implementation-phases.md` and do not pull work forward from later phases.

## Stack

- PHP 8.5+ (Laravel Herd locally: `php85`), Laravel 13.x, PostgreSQL 18 (Docker via `docker-compose.yml`; the app runs natively)
- FilamentPHP 5.x, single panel, native multi-tenancy with `Home` as tenant (from Phase 1–2)
- Pest for tests, Pint for formatting, PHPStan + Larastan level 6, Rector

## Commands

| Command | Purpose |
|---|---|
| `composer run setup` | .env, key, Postgres up, migrate, npm build |
| `composer run dev` | serve + queue + logs + vite |
| `composer run test` | Pest (local default: sqlite in-memory; CI: Postgres 18) |
| `composer run format` | Pint |
| `composer run analyse` | PHPStan level 6 |
| `composer run refactor` | Rector |
| `composer run check` | pint --test + phpstan + pest (what CI runs) |

## Architecture

Modular monolith (`prompts/specs/08-architecture-and-business-logic.md`):

```text
app/
  Domain/
    Maintenance/     Models, Enums, Services, Observers — decides what things MEAN
    Integrations/    Calendar, Paperless, ExternalLinks, Photos — decides how things are SHOWN ELSEWHERE
  Filament/          Resources, Pages, Widgets, Actions (standard Filament layout)
```

No integration-specific columns on core tables.

## Non-negotiable conventions

- **Tenancy:** every top-level domain table carries `home_id` and the `BelongsToHome` trait. Child tables without `home_id` (`work_item_steps`, `work_item_results`, `work_item_contacts`, `recurring_rule_steps`) are **only ever read through their parent relationship** — no Filament resources of their own, no route model binding.
- **Enums:** closed value sets are PHP backed string enums in `app/Domain/.../Enums/`, stored as strings (never Postgres enum types, never integers). Labels come from lang files.
- **Dates:** storage is UTC; all date math and display use the home's timezone (`homes.timezone`). `app.timezone` stays `UTC`.
- **History is never silently destroyed:** deleting is for mistakes; ending a thing's life is a status change. FK delete behavior table in `prompts/plan/02-technical-decisions.md`.
- **Activity log:** every domain model adds `LogsActivity` in the phase that creates it (from Phase 2 on).
- Every phase ships working: tests green, `pint --test` and `phpstan` clean, before commit.
