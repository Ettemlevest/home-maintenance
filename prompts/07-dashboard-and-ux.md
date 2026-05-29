# Dashboard and UX

## Design goal

The dashboard should answer quickly:

```text
What needs attention?
What is overdue?
What should I do today or this month?
What did we recently finish?
What costs are coming?
```

## Primary dashboard sections

```text
Needs Attention
Overdue
Due Now
Upcoming Next 30 Days
Expected Cost Next 3 Months
Recently Completed
```

The top chrome of every dashboard page hosts a **global search bar**. It searches across asset names and notes, work item titles and descriptions, expense descriptions, contact names, and location names (see [`08-architecture-and-business-logic.md`](./08-architecture-and-business-logic.md) → `SearchService`).

## Urgency tier mapping

The urgency tiers defined in [`03-work-items-statuses-and-due-dates.md`](./03-work-items-statuses-and-due-dates.md) map to dashboard sections and visual treatment as follows:

| Computed urgency | Dashboard section | Visual treatment | Sort weight |
|---|---|---|---|
| 0–33% window elapsed (low) | Upcoming Next 30 Days | muted gray text, no badge | lowest |
| 34–66% (normal) | Upcoming Next 30 Days | default text, no badge | low |
| 67–90% (high) | Due Now | amber accent / badge | medium |
| 91–100% (urgent) | Due Now | red accent / badge | high |
| past window end (overdue) | Overdue | red bold + warning icon | highest |
| `hard_due_at` ≤ 7 days away | Due Now | amber → red as days approach 0 | medium → high |
| `hard_due_at` passed | Overdue | red bold + warning icon | highest |

`failed` and `blocked` work items appear in **Needs Attention** regardless of urgency.

## Needs Attention

Includes failed and blocked work items.

## Overdue

Includes open work items where `hard_due_at < today` or `due_window_end < today`.

## Due Now

Includes open work items where today is within the due window.

## Upcoming Next 30 Days

Includes open work items where due window or hard deadline starts within the next 30 days.

## Expected Cost

Dynamic reports:

```text
next 30 days
next 3 months
next 6 months
next 12 months
next calendar year
custom date range
```

Costs come from work items and recurring rules. Group by inferred category.

## Asset detail page

Should show asset details, current condition, location, tags, warranty date, Paperless links, external links, photo timeline, **activity timeline**, upcoming work, past work, related expenses, contacts, and **replacement lineage**.

The **activity timeline** is a sibling panel to the photo timeline. It surfaces who-did-what-when events from `spatie/laravel-activitylog`: "Anna marked the FI relay test as failed at 18:43", "Béla updated the heat pump's condition from `good` to `fair`", "István attached a service report from MyContractor Kft."

The **replacement lineage** shows the chain of assets connected via `assets.replaced_by_asset_id`. If the current asset replaced a previous one (or is itself replaced by a successor), the page surfaces the older → newer chain so the user can see the full history across asset generations — useful for warranty / lifetime cost analysis on long-lived equipment like heat pumps or boilers.

## Bulk admin actions

Filament resources expose a few household-specific admin actions:

- **Duplicate N times** on `Asset`: takes a count and a numbering pattern (e.g. `Füstérzékelő {n}` → `Füstérzékelő 1`..`Füstérzékelő N`) and clones the source asset's recurring rules so each new asset has its own independent schedule. Saves real pain when seeding identical units (smoke detectors, RCDs, fixtures across rooms).
- **Regenerate feed URL** on `CalendarFeed`: rotates `token` and invalidates the old URL (see [`05-integrations-calendar-paperless-external-links.md`](./05-integrations-calendar-paperless-external-links.md)).

## Work item detail page

Should show title, type, status, computed due state, priority, severity, asset, location, due window/hard deadline, steps, free-text materials/tools/safety notes, estimated/actual cost, photos, links, contacts, and result.

## Mobile UX

Important mobile actions:

```text
mark completed
mark failed
mark blocked
upload photo
view steps
open Paperless link
open external link
call contractor
view photo timeline
```

Avoid forcing users to mark tasks as `in_progress`.

### Composite "Complete" action

The single highest-value mobile action is "I just did this — here's a photo, mark it done". A composite Filament action on work items captures, in one mobile-friendly form:

- result value (defaults to `done`)
- optional photo upload (uses the device camera)
- optional measured value + unit (for tests and inspections)
- optional short summary

A single submit:

1. Updates the work item's `status` to `completed` (or whichever lifecycle state matches the result).
2. Creates a `work_item_result` row.
3. Attaches the photo to the work item.
4. Advances the recurring rule if the result is one of `done`, `passed`, `fixed`, `skipped` (see [`04-recurring-rules-and-scheduling.md`](./04-recurring-rules-and-scheduling.md)).

The form must work on a phone in one hand without zooming.

## Calendar UX

Default calendar subscription:

```text
one ICS feed per home
```

Use emoji-prefixed event titles.

## Tag UX

Tags should have name, description, comment, sort order, color, and type.
