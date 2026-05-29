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
- reschedule_from -- completed_at, fixed_anchor, previous_due_at
- only_after_success boolean
- window_strategy -- exact_day, rolling_window, calendar_month, calendar_season
- window_length_days nullable
- anchor_month nullable
- anchor_day nullable
- anchor_season nullable
- next_due_at nullable
```

The recurring rule's `type` accepts the same set as one-off work items minus the values that don't recur (`installation`, `upgrade`, `project`):

```text
task
maintenance
repair
test
inspection
cleaning
replacement
```

`replacement` covers canonical recurring chores like "replace HVAC filter every 3 months" and "replace smoke detector battery every year".

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

### previous_due_at

The next occurrence is calculated from the **previous due date** (not the actual completion date), and lateness does not compound. Useful when calendar regularity matters more than "exactly N months since last touch".

```text
Service heat pump every 12 months
Previous due: 2026-03-01
Completed late: 2026-04-10
Next due: 2027-03-01  (not 2027-04-10)
```

## only_after_success

If `only_after_success = true`, the recurring rule advances only after a result that signals the planned outcome was reached or intentionally bypassed.

Recurrence-advancing results:

```text
done
passed
fixed
skipped
```

`skipped` advances the rule because an intentional skip with a stated reason should not freeze the schedule forever (typical case: another project already covered the chore — e.g. a roof replacement that included gutter cleaning).

If the latest result is none of the above, the app should create or suggest a follow-up work item instead of advancing the rule.

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

#### Naming convention and the year-boundary case

The `winter` window straddles two calendar years, which creates ambiguity unless the convention is fixed. The convention used throughout the app:

- A season window is **labeled by the year of its start date**. "Winter 2025" means the window that starts on 2025-12-01 and ends on 2026-02-28/29.
- **First-time activation**: if the rule is created while inside the current season window, the first generated work item uses that current window. Otherwise it uses the next upcoming window.
- **Reschedule**: `next_due_at` = the start date of the next season window whose start date is **strictly after** the latest successful `completed_at` (where "successful" is one of `done`, `passed`, `fixed`, `skipped`).

Worked examples:

```text
Rule: Ereszcsatorna tisztítás
anchor_season = autumn (Sep 1 – Nov 30)
reschedule_from = fixed_anchor
Completed successfully: 2026-10-15
Next autumn window starting after 2026-10-15 = autumn 2027 (2027-09-01 – 2027-11-30)
next_due_at = 2027-09-01
```

```text
Rule: Kerti csapok téliesítése
anchor_season = winter (Dec 1 – Feb 28/29)
reschedule_from = fixed_anchor
Completed successfully: 2026-01-20
Next winter window starting after 2026-01-20 starts on 2026-12-01
next_due_at = 2026-12-01
```

## Generated work items

Generated work items should normally be:

```text
status = open
source = recurring_rule
```

They should copy title, description, type, priority, severity, location, asset, estimated values, free-text materials/tools/safety notes, steps, and tags from the recurring rule.

Materials, tools, and safety notes live at the rule level only — there are no per-step materials/tools fields. A 6-step inspection that needs different tools per step collapses all of them into the rule-level `tools_text`. This is intentional simplicity for MVP; revisit only if real usage shows it matters.
