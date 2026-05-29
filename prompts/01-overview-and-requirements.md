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

The MVP should not support inventory tracking, stock movement history, purchase order management, supplier procurement workflows, product catalog management, tool tracking, full double-entry accounting, CalDAV write synchronization, internal document storage, or multi-tenant SaaS complexity.

## Mobile-first requirement

The app should be usable during actual maintenance work: open upcoming chores, mark selected items completed, upload photos, check steps, see required tools/materials as free text, view asset history, open Paperless links, open manufacturer/support links, call contractors, and identify contacts by profile photo.

## Recommended implementation architecture

Use a modular monolith:

```text
One Laravel application
One shared database
Separated business logic modules
Clear module boundaries
```

Do not split into multiple applications or multiple databases for the MVP.

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
```
