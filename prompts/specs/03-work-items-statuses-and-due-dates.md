# Work Items, Statuses, Results, and Due Dates

> **Status: source spec.** Authoritative for feature intent. Where [`../plan/`](../plan/) decides otherwise (scope, schema, work item types), the plan wins.

## Work item types

```text
task
maintenance
repair
test
inspection
cleaning
installation
replacement
upgrade
project
```

## Stored work item statuses

The app is not a project management tool. There is no `in_progress` status because most household tasks are short and users will usually only mark completion at the end.

| Status | Meaning | Normal dashboard visibility | Typical next state |
|---|---|---|---|
| draft | Idea or future task that is not active yet | No | open, cancelled |
| open | Active task waiting to be completed | Yes | completed, failed, blocked, cancelled |
| completed | Successfully finished | History only | N/A |
| failed | Attempted but unsuccessful | Yes, needs attention | open, completed |
| blocked | Cannot currently be completed | Yes, needs attention | open, completed, cancelled |
| cancelled | No longer relevant | History only | N/A |

## Work item result values

A work item may have **multiple `work_item_results` rows over its lifetime** — for example a failed first attempt followed by a successful retry. The "current outcome" displayed in dashboards and used for recurrence advancement (see [`04-recurring-rules-and-scheduling.md`](./04-recurring-rules-and-scheduling.md)) is the **latest row by `created_at`**. Older rows are preserved as history.

Status describes lifecycle. Result describes outcome.

| Result | Meaning |
|---|---|
| done | Generic successful completion |
| passed | Successful test or inspection |
| fixed | Successful repair |
| failed | Attempted but unsuccessful |
| partially_done | Partially completed |
| not_fixed | Repair attempted but problem remains |
| skipped | Intentionally not performed |
| needs_followup | Additional work required |
| unknown | Outcome not recorded |

Example:

```text
Status: completed
Result: skipped
Reason: Roof replacement project already included gutter cleaning.
```

## Computed states

Computed states are not stored.

```text
upcoming = status open and due date/window is in the future
due_now = status open and current date is within due_window_start and due_window_end
overdue = status open and hard_due_at < today or due_window_end < today
needs_attention = status failed or blocked
completed_recently = status completed and completed_at inside configurable window
```

## Due date model

Use only two scheduling concepts:

```text
hard_due_at
due_window_start / due_window_end
```

Avoid separate `soft_due_at`.

## hard_due_at

Use when missing the date has real consequences.

Examples:

```text
Warranty claim deadline
Contractor appointment
Official inspection
Insurance-related task
Booked service visit
```

Behavior:

```text
exact calendar event
overdue immediately after date
high dashboard priority near deadline
```

## due window

Use for most maintenance and chores.

Examples:

```text
Replace smoke detector battery during June
Clean gutters during autumn
Inspect roof during April
Check heat pump outdoor unit within the next two weeks
```

Behavior:

```text
Before window = upcoming
Inside window = due
After window = overdue
```

Urgency should increase as the end of the window approaches.

```text
0-33% elapsed = low urgency
34-66% elapsed = normal urgency
67-90% elapsed = high urgency
91-100% elapsed = urgent
after window = overdue
```

The mapping from these urgency tiers to dashboard sections, visual treatment, and sort weight is defined in [`07-dashboard-and-ux.md`](./07-dashboard-and-ux.md).

## Future extension: structured measurements with auto pass/fail

The current model treats `work_item_steps.expected_result` as free text and stores a single `measured_value` + `measured_unit` on `work_item_results`. This is intentionally simple — household tests rarely need automated pass/fail evaluation.

If structured measurement evaluation becomes useful later (for example, water pressure tests with a strict acceptable range, or insulation resistance checks with manufacturer-specified thresholds), the schema can be extended without re-deriving the design:

```text
work_item_steps additions:
- measurement_type -- range, value, boolean, free_text
- expected_min nullable
- expected_max nullable
- expected_value nullable
- expected_unit nullable

work_item_results (or a new per-step result table) additions:
- actual_value nullable
```

Automatic PASS / FAIL evaluation would live in a service — either an extended `WorkItemDueStateService` or a dedicated `WorkItemEvaluationService` — comparing `actual_value` against the step's `measurement_type` rule.

These columns are **not** part of the MVP. They are documented here so a future implementer can extend the schema consistently.
