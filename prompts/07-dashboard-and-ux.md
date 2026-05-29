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

Should show asset details, current condition, location, tags, warranty date, Paperless links, external links, photo timeline, upcoming work, past work, related expenses, and contacts.

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

## Calendar UX

Default calendar subscription:

```text
one ICS feed per home
```

Use emoji-prefixed event titles.

## Tag UX

Tags should have name, description, comment, sort order, color, and type.
