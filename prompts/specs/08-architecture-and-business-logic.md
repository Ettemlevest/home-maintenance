# Architecture and Business Logic

> **Status: source spec.** Authoritative for feature intent. Where [`../plan/`](../plan/) decides otherwise (scope, schema, work item types), the plan wins.

## Recommended architecture

Use a modular monolith.

```text
One Laravel application
One shared database
Separated business logic modules
Clear module boundaries
```

Do not split into separate applications or databases for the MVP.

## Suggested Laravel structure

```text
app/
  Domain/
    Maintenance/
      Models/
        Home.php
        HomeUser.php
        Location.php
        Asset.php
        WorkItem.php
        WorkItemStep.php
        WorkItemResult.php
        WorkItemAsset.php
        WorkItemContact.php
        RecurringRule.php
        RecurringRuleStep.php
        Expense.php
        Tag.php
        Contact.php
      Services/
        WorkItemStatusService.php
        WorkItemDueStateService.php
        RecurrenceService.php
        CostForecastService.php
        DashboardQueryService.php

    Integrations/
      Paperless/
        Models/PaperlessLink.php
        Services/PaperlessLinkService.php
      ExternalLinks/
        Models/ExternalLink.php
        Services/ExternalLinkService.php
      Calendar/
        Models/CalendarFeed.php
        Services/IcsFeedGenerator.php
        Services/CalendarEventBuilder.php
        Services/CalendarFeedFilterService.php
      Photos/
        Models/Photo.php
        Services/PhotoTimelineService.php
```

## Module boundary rule

```text
Maintenance decides what something means.
Integrations decide how it is shown elsewhere.
```

| Question | Owning module |
|---|---|
| Is this task overdue? | Maintenance |
| What is the next due window? | Maintenance |
| Should recurrence advance? | Maintenance |
| What does the calendar event title look like? | Calendar |
| Which emoji should prefix a task? | Calendar |
| Which Paperless document is linked? | Paperless |
| Which manufacturer page is useful? | External Links |
| Which photos belong to this asset timeline? | Photos |

## Recommended services

### WorkItemDueStateService

Calculates upcoming, due_now, overdue, needs_attention, and completed_recently.

### RecurrenceService

Generates next work items, calculates due windows, respects only_after_success, and handles rolling windows, calendar-month windows, and calendar-season windows.

### CostForecastService

Calculates expected costs for future windows and groups by inferred cost category.

For **future-looking** views (next 30 days, next 3 months, etc.) the service uses `work_items.estimated_cost` and `recurring_rules.estimated_cost`. For **historical** views it uses `SUM(expenses.amount WHERE work_item_id = …)`; there is no `work_items.actual_cost` column.

**Cost category inference** — applied in this priority order, first match wins:

1. Explicit `cost_category` tag on the work item.
2. Explicit `cost_category` tag on the originating recurring rule.
3. Tag on the work item's primary asset.
4. `assets.type` of the primary asset.
5. Tag on the work item's location.
6. `contacts.type` of any linked `performer` contact.

Items with no match are grouped under `cost-general-maintenance`.

### IcsFeedGenerator

Builds valid ICS output, applies feed filters, generates events from hard deadlines and due windows, applies templates, and adds emoji prefixes.

### SearchService

Provides the global search bar's backing query (see [`07-dashboard-and-ux.md`](./07-dashboard-and-ux.md)). Searchable fields:

```text
assets.name
assets.notes
work_items.title
work_items.description
expenses.description
contacts.name
locations.name
```

Implementation uses PostgreSQL's full-text search — `tsvector` columns with GIN indexes for ranking, or `pg_trgm` for fuzzy / typo-tolerant matching. The choice between a hand-rolled `tsvector` migration and Laravel Scout with a Postgres driver is deferred to implementation time.

## Background-job model

Time-based work runs as queued jobs dispatched by Laravel's scheduler:

- `php artisan schedule:run` is invoked once a minute by the deployment's cron.
- A daily `RecurrenceService::generateUpcoming()` task scans active recurring rules and, for each rule where `next_due_at <= today + auto_create_days_before_due`, dispatches a job that materializes the work item.
- A daily reminder job consumes `recurring_rules.reminder_days_before` to surface upcoming items in dashboards / future notification channels.

The queue driver (Redis vs database) is picked at implementation time. The queue worker runs in the same Docker container under Supervisor — operational details live in [`09-deployment.md`](./09-deployment.md).

## Tooling

| Concern | Tool |
|---|---|
| Tests | **PestPHP** (not PHPUnit directly) |
| Linting / code style | **Laravel Pint** |
| Static analysis / type coverage | **PHPStan + Larastan**; track type-coverage as a CI metric |
| Automated refactors | **Rector** (alongside Pint, which handles style only) |

Minimum test coverage targets sit on `RecurrenceService` and `WorkItemDueStateService` — that's where the maintenance business logic lives and where regressions hurt most. Other modules are tested pragmatically rather than exhaustively.

## FilamentPHP implementation notes

Plugins to consider when implementing the panel:

- `spatie/laravel-medialibrary` + the matching Filament plugin — owns physical photo storage (`media` table) and image conversions / thumbnails. The app's `photos` table only keeps app-specific metadata; see [`02-database-schema.md`](./02-database-schema.md).
- `spatie/laravel-activitylog` — drives the activity timeline on the asset detail page. Configure on `Asset`, `WorkItem`, `WorkItemResult`, `Expense`, `Contact`.
- A Filament translation manager plugin (e.g. `solution-forest/filament-translation-manager` or equivalent) — manages UI translation strings for the bilingual HU + EN setup.
- Filament in-app notifications — surfaces overdue / due-now items when a user is in the panel.

### Role-based permissions

For MVP, all members of a home can see and edit everything inside that home; `home_users.role` (owner / adult / child / guest) is recorded but **not enforced**. The role column exists so role-based permissions can be added later without a schema migration.

When permissions are needed, the chosen package will be either:

- `bezhansalleh/filament-shield` — the Filament-native option.
- `spatie/laravel-permission` — the implementer's preferred alternative (more familiarity).

No immediate work required.

## Future: MNB-rate currency conversion

The MVP stores per-record `currency` on `work_items`, `recurring_rules`, and `expenses`, defaulting to `homes.default_currency` (typically `HUF`). Amounts are stored and displayed in their original currency without conversion.

When the household starts mixing currencies regularly (parts ordered from EUR webshops, USD imports), a daily background job populates an `exchange_rates` table from the Hungarian National Bank's public reference rates ([mnb.hu](https://mnb.hu)):

```text
exchange_rates
- base_currency
- quote_currency
- rate
- rate_date
- created_at
```

`CostForecastService` then computes a display-only `huf_equivalent` for mixed-currency views: it looks up the appropriate `rate_date` (the expense's `spent_at` for historical items, today's rate for forecasts). The stored `amount` and `currency` are never overwritten — conversion is purely a presentation concern.

This is documented here so the future implementer knows the intended source (MNB) and the display-only semantic; **not** part of MVP.

## Implementation principle

Good maintenance-core fields:

```text
work_items.due_window_start
work_items.due_window_end
work_items.hard_due_at
work_items.estimated_cost
work_items.status
```

Avoid integration-specific fields in core:

```text
work_items.calendar_emoji
work_items.ics_description
work_items.paperless_invoice_url
work_items.google_calendar_event_id
```

Better:

```text
calendar_feeds define event formatting
paperless_links store Paperless references
external_links store generic URLs
```
