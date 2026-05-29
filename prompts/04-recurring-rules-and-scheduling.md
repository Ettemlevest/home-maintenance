# Recurring Rules and Scheduling

## Purpose

Recurring rules generate future work items.

Examples:

```text
Test FI relay every month
Inspect heat pump outdoor unit every 3 months
Clean gutters every 6 months
Service heat pump yearly
Replace smoke detector battery every year during June
```

## Recommended fields

```sql
recurring_rules
- interval_unit -- day, week, month, year
- interval_count
- reschedule_from -- completed_at, fixed_anchor
- only_after_success boolean
- window_strategy -- exact_day, rolling_window, calendar_month, calendar_season
- window_length_days nullable
- anchor_month nullable
- anchor_day nullable
- anchor_season nullable
- next_due_at nullable
```

## Reschedule behavior

### completed_at

The next occurrence is calculated from the previous successful completion.

```text
Inspect heat pump every 3 months
Due: March 1
Completed: March 20
Next due: June 20
```

### fixed_anchor

The task keeps a fixed calendar schedule.

```text
Clean gutters every April and October
```

## only_after_success

If `only_after_success = true`, the recurring rule advances only after a successful result.

Successful results:

```text
done
passed
fixed
```

If unsuccessful, the app should create or suggest a follow-up work item instead of advancing the rule.

## Window strategies

### rolling_window

The due window starts from the calculated next occurrence and lasts for a configured number of days.

```text
Inspect heat pump every 3 months
window_length_days = 14
Last successful completion: 2026-03-20
Generated: 2026-06-20 to 2026-07-04
```

### calendar_month

```text
Replace smoke detector battery every year during June
anchor_month = 6
Generated: 2027-06-01 to 2027-06-30
```

### calendar_season

```text
Clean gutters every autumn
anchor_season = autumn
Generated: 2027-09-01 to 2027-11-30
```

Initial season defaults:

```text
spring: March 1 - May 31
summer: June 1 - August 31
autumn: September 1 - November 30
winter: December 1 - February 28/29
```

## Generated work items

Generated work items should normally be:

```text
status = open
source = recurring_rule
```

They should copy title, description, type, priority, severity, location, asset, estimated values, free-text materials/tools/safety notes, steps, and tags from the recurring rule.
