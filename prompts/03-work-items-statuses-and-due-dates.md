# Work Items, Statuses, Results, and Due Dates

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
