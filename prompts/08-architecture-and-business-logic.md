# Architecture and Business Logic

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
        Location.php
        Asset.php
        WorkItem.php
        WorkItemStep.php
        WorkItemResult.php
        RecurringRule.php
        RecurringRuleStep.php
        Expense.php
        Tag.php
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

### IcsFeedGenerator

Builds valid ICS output, applies feed filters, generates events from hard deadlines and due windows, applies templates, and adds emoji prefixes.

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
