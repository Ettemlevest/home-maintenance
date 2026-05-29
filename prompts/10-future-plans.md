# Future Plans

This document is the **index** of everything the planning set explicitly defers beyond MVP. It does not replace the descriptions in the original files — those remain authoritative — but consolidates them so a reader can see the full post-MVP backlog at a glance and know where each item is described in detail.

Each entry below has:

- a short name
- a one-line description
- the source file/section that owns the full definition
- the reason it is deferred (when the source file gives one)

For things that are explicitly **never** intended to be built (inventory tracking, double-entry accounting, vehicle maintenance, etc.), see the Non-goals list in [`01-overview-and-requirements.md`](./01-overview-and-requirements.md). Those are out of scope, not deferred.

---

## Integrations

### Home Assistant
- **What**: REST endpoints / webhooks so HA can display due maintenance, trigger automations when a task becomes overdue, or push completion events back into this app.
- **Source**: [`05-integrations-calendar-paperless-external-links.md`](./05-integrations-calendar-paperless-external-links.md) → "Future integrations".
- **Why deferred**: avoids scope creep; the data model intentionally supports the wiring later without rework.

### Paperless-ngx webhook receiver
- **What**: receive Paperless-ngx's "new document" webhook, match the document against assets/work items, auto-suggest a `paperless_link` row.
- **Source**: [`05-integrations-calendar-paperless-external-links.md`](./05-integrations-calendar-paperless-external-links.md) → "Future integrations".
- **Why deferred**: manual link creation works for MVP; auto-suggestion is a quality-of-life improvement.

### Push notification channels (ntfy / email / in-app)
- **What**: deliver reminders through ntfy, email (Laravel Mail), or in-app Filament notifications. The schema columns `recurring_rules.reminder_days_before` and `auto_create_days_before_due` already exist and are forward-compatible.
- **Source**: [`05-integrations-calendar-paperless-external-links.md`](./05-integrations-calendar-paperless-external-links.md) → "Future integrations".
- **Why deferred**: ICS feed is the only notification surface in MVP; pick a channel after MVP ships based on real usage.

---

## Data model extensions

### Structured test measurements with auto PASS/FAIL
- **What**: extend `work_item_steps` with `measurement_type` (range / value / boolean / free_text) and `expected_min`/`expected_max`/`expected_value`/`expected_unit`; add `actual_value` at result time; auto-evaluate PASS/FAIL in a service (extended `WorkItemDueStateService` or a dedicated `WorkItemEvaluationService`).
- **Source**: [`03-work-items-statuses-and-due-dates.md`](./03-work-items-statuses-and-due-dates.md) → "Future extension: structured measurements with auto pass/fail".
- **Why deferred**: household tests rarely need automated pass/fail evaluation; free-text `expected_result` is sufficient for MVP. The schema is documented so a future implementer can extend consistently.

### Per-step materials / tools / safety notes
- **What**: move `materials_text`, `tools_text`, `safety_notes` from rule-level fields onto individual `recurring_rule_steps` and `work_item_steps` so a 6-step inspection can show different tools per step.
- **Source**: [`04-recurring-rules-and-scheduling.md`](./04-recurring-rules-and-scheduling.md) → "Generated work items".
- **Why deferred**: intentional simplicity for MVP; revisit only if real usage shows it matters.

### Multi-currency conversion via MNB daily rates
- **What**: daily background job pulls Hungarian National Bank reference rates from [mnb.hu](https://mnb.hu) into an `exchange_rates` table; `CostForecastService` computes a display-only `huf_equivalent` so mixed-currency cost views can show a single HUF total. Stored amounts are never overwritten.
- **Source**: [`08-architecture-and-business-logic.md`](./08-architecture-and-business-logic.md) → "Future: MNB-rate currency conversion".
- **Why deferred**: the per-record `currency` column + `homes.default_currency` is enough for households that mostly transact in one currency; conversion is only needed when mixed-currency expenses become routine.

---

## Permissions / multi-user

### Role-based permissions
- **What**: enforce `home_users.role` (owner / adult / child / guest) so guests are read-only, children are limited, etc. Implementation will use either `bezhansalleh/filament-shield` (Filament-native) or `spatie/laravel-permission` (preferred by the implementer for familiarity).
- **Source**: [`08-architecture-and-business-logic.md`](./08-architecture-and-business-logic.md) → "FilamentPHP implementation notes" → "Role-based permissions".
- **Why deferred**: a single household typically trusts all its adult members equally; no immediate need. The `role` column exists so the package can be added later without a schema migration.

---

## Hardware / field workflow

### Niimbot D11-compatible printable QR-code labels
- **What**: generate PNG / PDF sticker sheets sized for Niimbot D11 label printers, encoding each asset's `/a/{slug}` URL as a QR code so household members can scan a sticker to jump to the asset's detail page.
- **Source**: [`01-overview-and-requirements.md`](./01-overview-and-requirements.md) → "Asset identity and QR-coded labels".
- **MVP scope**: the `assets.slug` column and the `/a/{slug}` resolver route are in MVP — the data is ready for the label work.
- **Why deferred**: label-template generation is a self-contained chunk of work that doesn't block the rest of the app; ship the asset-identity foundation first.

---

## Deployment and operations

The whole of [`09-deployment.md`](./09-deployment.md) is a future-goal placeholder. The items below are the specific deliverables it points at; each can be done independently.

### Single Docker image runnable on Unraid OS
- **What**: a Docker image containing PHP-FPM, an HTTP server (nginx or Caddy), the Laravel app, and a Supervisor-managed queue worker. Distribution as an Unraid Community Applications template for one-click install.
- **Source**: [`09-deployment.md`](./09-deployment.md) → "Deployment target".
- **Why deferred**: not blocking the planning set; the application logic must be solid first.

### `docker-compose.yml` for non-Unraid self-hosters
- **What**: a compose file for users running the app outside the Unraid ecosystem.
- **Source**: [`09-deployment.md`](./09-deployment.md) → "Deployment target".

### Backup automation
- **What**: documented direction for backing up DB, photos, and config — `pg_dump` from host / sidecar, volume snapshots for photos via Unraid's CA Backup plugin, `.env` and `config/` overrides alongside the dump.
- **Source**: [`09-deployment.md`](./09-deployment.md) → "Backup direction".
- **Why deferred**: relies on the user's existing Unraid backup routine; this app does not run its own backup scheduler in MVP.

### User-facing `home:export` artisan command
- **What**: a single artisan command that zips JSON of all tables plus the photos folder for a one-click full export.
- **Source**: [`09-deployment.md`](./09-deployment.md) → "Backup direction".
- **Why deferred**: the recommended backup approach (DB dump + volume snapshot) covers the same need; add the export command only if there's demand.
