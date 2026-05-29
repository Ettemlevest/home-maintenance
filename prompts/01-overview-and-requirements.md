# Home Maintenance App — Overview and General Requirements

## High-level summary

This application is a lightweight self-hosted home maintenance system for family houses.

Its main purpose is to help a household answer these questions:

- What needs attention soon?
- What is overdue?
- What was completed recently?
- What happened in the past with a specific asset, location, system, or contractor?
- What recurring maintenance should happen next?
- What are the expected costs for the next 30 days, 3 months, 6 months, 12 months, or next calendar year?
- Which photos, Paperless-ngx document references, and useful external links belong to an asset or maintenance history item?

The app should not become an ERP system.

It should avoid inventory management, procurement workflows, warehouse-style stock tracking, full accounting, document storage, tool lending/tracking, and overcomplicated workflow states.

Documents such as invoices, manuals, warranty papers, certificates, quotes, and service reports should live in Paperless-ngx. This app should only store references to those documents.

Photos are different: the app should store photos directly because photo timelines are useful for asset history, visible condition tracking, before/after repair evidence, and contact profile pictures.

## Core user value

The system should optimize for:

```text
minimum data entry
maximum long-term usefulness
clear upcoming work
good historical traceability
simple recurring maintenance
mobile-first usage
```

## Main concepts

```text
Home
├─ Locations
├─ Assets
├─ Work Items
├─ Recurring Rules
├─ Tags
├─ Photos
├─ Paperless Links
├─ External Links
├─ Contacts
├─ Expenses
└─ Calendar Feeds
```

## General requirements

The system should support multiple homes, multiple users per home, asset tracking, hierarchical locations, work item history, recurring maintenance generation, due windows and hard deadlines, estimated and actual costs, simple cost forecasting, photos and photo timelines, contact profile photos, Paperless-ngx links, generic external links, tag-based filtering, ICS calendar feeds, and dashboard views.

## Non-goals

The MVP should not support inventory tracking, stock movement history, purchase order management, supplier procurement workflows, product catalog management, tool tracking, full double-entry accounting, CalDAV write synchronization, internal document storage, multi-tenant SaaS complexity, or vehicle / car maintenance tracking.

Vehicle maintenance shares some surface area with home maintenance (recurring service intervals, expense history, document references), but the seed data, dashboards, and asset taxonomy in this app are tuned for a house. A determined user could bend the generic asset model to track a car, but it is not a supported use case.

## Mobile-first requirement

The app should be usable during actual maintenance work: open upcoming chores, mark selected items completed, upload photos, check steps, see required tools/materials as free text, view asset history, open Paperless links, open manufacturer/support links, call contractors, and identify contacts by profile photo.

## Internationalization (i18n)

The application is bilingual from day one: Hungarian and English.

UI strings are translated through Laravel's standard language file mechanism (`resources/lang/{locale}/*.php` or the equivalent JSON-based translation files). All strings exposed to end users — including Filament resource labels, validation messages, dashboard section titles, and seed tag display names — must be wrapped in `__()` (or Filament's translatable interfaces) rather than hard-coded.

User-entered content (asset names, recurring rule titles, work item descriptions, notes) is stored in a single language per home. The MVP does not provide per-row translations (no `translations (json)` columns); a household picks the language it prefers and stays there. The `users.locale` column controls only the UI language for that user.

## Asset identity and QR-coded labels

Every asset has a short, URL-safe slug (or UUID) used to build a stable URL of the form `/a/{slug}` that resolves to the asset's detail page. This slug is the target for printed QR-code labels stuck on physical units (smoke detectors, RCDs, valves, fixtures) so a household member can scan the code with a phone and jump straight to the asset's history.

Generating the printable labels themselves — specifically Niimbot D11-compatible templates and sticker sheet layouts — is a planned follow-up after MVP. The slug column and resolver route are in scope for MVP so the data is ready for that future work.

## Recommended implementation architecture

Use a modular monolith:

```text
One Laravel application
One shared database
Separated business logic modules
Clear module boundaries
```

Do not split into multiple applications or multiple databases for the MVP.

## Tech stack and deployment target

The implementation uses the **latest stable releases** of PHP, Laravel, FilamentPHP, and PostgreSQL at the time of implementation. The planning docs intentionally do not pin specific version numbers; the implementer picks current stable when the build starts so the codebase begins on a supported baseline.

The intended deployment is a single Docker container runnable on an Unraid OS home server. The full deployment guide (Unraid Community Applications template, `docker-compose.yml`, queue worker under Supervisor, scheduler cron, photo storage volume, backup direction) lives in [`09-deployment.md`](./09-deployment.md) as a future-goal placeholder and is not required to complete the rest of the planning set.

## Document set

```text
01-overview-and-requirements.md
02-database-schema.md
03-work-items-statuses-and-due-dates.md
04-recurring-rules-and-scheduling.md
05-integrations-calendar-paperless-external-links.md
06-seed-data.md
07-dashboard-and-ux.md
08-architecture-and-business-logic.md
09-deployment.md
```
