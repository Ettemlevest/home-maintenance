# Planning Review — TODO Items

Open items found during a cross-check of the planning docs (`README.md` → `01`–`10`). Grouped by category. Each item references the source file(s) so the fix can be made in-place.

---

## Contradictions

### 1. `work_items.asset_id` vs `work_item_assets` pivot
**Source:** `02-database-schema.md`

You have both a singular `work_items.asset_id` and a pivot with a `primary` role. Either is reasonable on its own, but together they're ambiguous: is `asset_id` the canonical "primary" asset and the pivot is for additional affected assets? Or is the pivot authoritative and `asset_id` is a denormalized cache?

**Action:** Pick one and say so explicitly. Most likely: drop `asset_id` and let the pivot's `primary` role be the single source of truth — otherwise every query needs to reconcile both.

### 2. Status `failed` vs Result `failed`
**Source:** `03-work-items-statuses-and-due-dates.md`

The doc tries to separate "lifecycle (status)" from "outcome (result)", but uses identical labels (`failed`, `skipped`). When someone writes "the test failed", they can't tell which axis is meant.

**Action:** Rename one set (e.g., `result=unsuccessful` and `status=failed_attempt`) or document the allowed `(status, result)` combinations as a matrix.

### 3. `users.locale` fallback target doesn't exist
**Source:** `02-database-schema.md` L398

The comment says "falls back to home or app default" but `homes` has no `default_locale`/`locale` column.

**Action:** Either add `homes.default_locale default 'hu'` or remove the "home" hop from the fallback chain.

### 4. `paperless_links.linkable_type` vs `external_links.linkable_type` asymmetry
**Source:** `02-database-schema.md` L277, L305

- `paperless_links` allows: assets, work_items, contacts, expenses, locations
- `external_links` allows: assets, work_items, locations, contacts, recurring_rules

A recurring rule can have an external link (e.g., manufacturer's manual) but not a Paperless reference (e.g., OEM service spec PDF)? An expense can have a Paperless receipt but not an external link to a webshop product? Neither asymmetry is justified.

**Action:** Make both sets the same unless there is a specific reason.

### 5. Calendar `lookahead_days = 180` vs `auto_create_days_before_due`
**Source:** `02-database-schema.md`, `05-integrations-calendar-paperless-external-links.md`, `08-architecture-and-business-logic.md`

ICS feed lookahead defaults to 180 days, but work items are materialized only `auto_create_days_before_due` (presumably 7–30) days ahead of due. So a calendar subscriber can never see anything more than ~30 days out, regardless of the 180-day setting.

**Action:** Either:
- The ICS generator must virtually project unmaterialized recurring rule occurrences within the lookahead window (and document it in `IcsFeedGenerator`), or
- Reduce the default to something honest like 60.

This is the most important gap — the integration is misleading as currently specified.

### 6. Activity log scope vs RecurringRule changes
**Source:** `08-architecture-and-business-logic.md` L152

Activity log targets: Asset, WorkItem, WorkItemResult, Expense, Contact. **RecurringRule is missing** — but rule interval/anchor changes are exactly the kind of "wait, when did we change this to quarterly?" audit need that the activity log exists for.

**Action:** Add `RecurringRule` to the activity log target list.

---

## Missing parts

### 7. Foreign key ON DELETE behavior
**Source:** `02-database-schema.md`

What happens when:
- A location with child locations and assets is deleted?
- An asset linked from many work items, expenses, photos, recurring rules, and `replaced_by_asset_id` is deleted?
- A contact referenced by `work_item_contacts` and `expenses` is deleted?

**Action:** Cascade vs set null vs restrict matters a lot — add this in `02` alongside the indexes.

### 8. Date vs datetime types
**Source:** `02-database-schema.md`

Every timestamp is listed without SQL type. For maintenance, most "due" fields should be `date` (no hour precision):

- `hard_due_at`, `due_window_start`, `due_window_end`, `installed_at`, `warranty_until`, `spent_at`, `next_due_at`, `paperless_created_at` → `date`
- `completed_at`, `condition_updated_at`, `taken_at`, `created_at`, `updated_at` → `timestamptz`

**Action:** Pick this now — getting it wrong forces a painful migration later, and recurrence math near midnight depends on it.

### 9. Time zone handling
**Source:** `01-overview-and-requirements.md` or `08-architecture-and-business-logic.md`

Bilingual HU/EN, single home server, but no statement of UTC storage vs Europe/Budapest display. "Inspect every June" needs to be evaluated in the user's local TZ.

**Action:** State the storage/display convention in `01` or `08`.

### 10. First-time activation logic only covered for `calendar_season`
**Source:** `04-recurring-rules-and-scheduling.md` L139

The convention is documented for winter/autumn windows but the implied "current window if active, next otherwise" logic for `rolling_window` and `calendar_month` is left to the reader.

**Action:** Write it explicitly — it's the kind of thing each implementer will guess differently.

### 11. `work_items.actual_minutes` has no defined input path
**Source:** `02-database-schema.md`, `07-dashboard-and-ux.md`

The composite "Complete" action captures result, photo, measured value, summary — no minutes.

**Action:** Either drop the column or add it to the action.

### 12. Multi-home user context switching
**Source:** *missing — likely `07-dashboard-and-ux.md` or `08-architecture-and-business-logic.md`*

A user in two homes — how is the active home picked on the dashboard? URL-scoped (`/h/{slug}/...`)? Session? Not addressed anywhere.

**Action:** Decide and document — this is plumbing-level but affects every Filament resource.

### 13. Followup work item generation responsibility
**Source:** `04-recurring-rules-and-scheduling.md`, `07-dashboard-and-ux.md`, `08-architecture-and-business-logic.md`

Section `04` says "the app should create or suggest a follow-up work item" but neither `RecurrenceService` (`08`) nor the composite Complete action (`07`) is tagged with that responsibility.

**Action:** Decide: is followup creation a side effect of `WorkItemResult` save, a service method, or a queued job?

### 14. Cross-home tenant scoping
**Source:** `08-architecture-and-business-logic.md`

Tables without `home_id` (`work_item_steps`, `work_item_results`, `work_item_assets`, `work_item_contacts`, `recurring_rule_steps`) rely on parent FK for tenant scoping.

**Action:** State explicitly that all reads must go through the parent's global scope, or include `home_id` as a denormalized column. Otherwise a `WorkItemStep::find($id)` bypasses tenancy.

### 15. `home_users` uniqueness constraint
**Source:** `02-database-schema.md`

Should be unique on `(home_id, user_id)` but not in the recommended indexes.

**Action:** Add `unique(home_id, user_id)` to the indexes list.

### 16. `work_items.due_window_start` and `due_window_end` integrity
**Source:** `02-database-schema.md`, `03-work-items-statuses-and-due-dates.md`

Should be both-null or both-set, with start ≤ end.

**Action:** Add a CHECK constraint or validation rule.

### 17. Asset slug generation strategy
**Source:** `01-overview-and-requirements.md`, `02-database-schema.md`

"short URL-safe slug (or UUID)" — auto-derived from name? Random? Collision resolution? This affects the QR-label workflow significantly.

**Action:** Pick a strategy (recommendation: short random nanoid-style string, e.g. 8 chars) and document it.

### 18. CostForecastService behavior with mixed currencies in MVP
**Source:** `08-architecture-and-business-logic.md`

Without conversion, the forecast can't aggregate.

**Action:** State the MVP behavior — per-currency buckets? Hide non-default-currency rows? Surface them with a warning?

### 19. `work_item_steps` aggregation into the parent's result
**Source:** `03-work-items-statuses-and-due-dates.md`, `07-dashboard-and-ux.md`

If 4 of 6 steps are `completed` and 2 are `failed`, what's the work item's result? Manual? Computed (worst-of)? The composite Complete action defaults `result = done`, which would mask step-level failures.

**Action:** Either document manual override semantics or specify the computed rule.

### 20. Seed data for multi-unit prototypes is missing
**Source:** `06-seed-data.md`

Section `06` describes the smoke-detector duplication workflow but the seed asset list (L119–156) doesn't include the prototype assets (e.g., `Füstérzékelő — gyártó A/B/C`) or their per-manufacturer recurring rules.

**Action:** Either include them in the seed lists or drop the workflow claim.

### 21. `completed_recently` window is "configurable" with no config location
**Source:** `03-work-items-statuses-and-due-dates.md`

App-level constant? Per home? Per dashboard view?

**Action:** Decide and document.

### 22. Filament family-invite / new-user flow
**Source:** *missing — likely `08-architecture-and-business-logic.md` or `09-deployment.md`*

Nothing covers how an `adult` joins an existing home. Default Filament register? Owner-issued invite link?

**Action:** Decide the auth surface for MVP.

---

## Improvements

### 23. Expand `SearchService` indexes
**Source:** `08-architecture-and-business-logic.md` L114

Also include `work_items.safety_notes`, `materials_text`, `tools_text`, `recurring_rules.title/description`, `paperless_links.title`, `external_links.title/comment`. Otherwise users searching "kerti csap" miss a rule named exactly that.

### 24. `taggables` is missing `created_at`
**Source:** `02-database-schema.md`

For consistency with other pivots and for "when did we tag this" queries.

### 25. Allow `expenses` to be tagged directly
**Source:** `02-database-schema.md`, `08-architecture-and-business-logic.md`

`taggables` is polymorphic but `CostForecastService`'s cost category inference (`08` L97) doesn't include direct expense tags. For one-off expenses without a work item (e.g., spontaneous IKEA trip for storage bins), direct tagging is the only category signal.

### 26. `paperless_document_id` nullable rationale
**Source:** `02-database-schema.md`

Clarify that null is for non-document Paperless URLs (tag views, saved searches), not just "we forgot to fill it in".

### 27. PostgreSQL extension prerequisites
**Source:** `08-architecture-and-business-logic.md`, `09-deployment.md`

`08` mentions `tsvector` and `pg_trgm`. The deployment doc should list the extensions to create at provisioning time.

### 28. Priority + computed urgency interaction
**Source:** `07-dashboard-and-ux.md`

The sort weight table only considers urgency. A `priority = urgent` work item that's far from its due window should still sort above a `priority = low` one within the same urgency tier.

**Action:** State the tiebreaker.

### 29. `work_items.severity` is semantically odd on `task`, `cleaning`, `installation`
**Source:** `02-database-schema.md`, `03-work-items-statuses-and-due-dates.md`

Currently you can have `type=cleaning, severity=critical` which means nothing useful.

**Action:** Either restrict the column to repair/inspection/test in validation, or rename to make it acceptable on all types (e.g., `impact_if_skipped`).

### 30. MVP role behavior should be explicit
**Source:** `08-architecture-and-business-logic.md` L156

"All members of a home can see and edit everything" reads as "owner/adult equality is OK", but `guest` and `child` also get full edit access in MVP.

**Action:** State it so it's not a surprise to read in a year.

### 31. `assets.replaced_by_asset_id` integrity
**Source:** `02-database-schema.md`

Convention-only right now. When `status` transitions to `replaced`, `replaced_by_asset_id` must be set (and vice versa).

**Action:** Add a model observer or DB constraint.

### 32. "Duplicate N times" needs zero-padding option
**Source:** `06-seed-data.md`, `07-dashboard-and-ux.md`

"Füstérzékelő 1..10" sorts as 1, 10, 2, 3 in strings.

**Action:** Either always pad (`{n:02}`), use `sort_order`, or document the trade-off.

### 33. Currency code format
**Source:** `02-database-schema.md`

**Action:** State ISO 4217 3-letter codes for `currency` columns and `homes.default_currency`.

---

## Suggested next moves

The two structural calls to make first, because they ripple through the rest:

1. **`asset_id` vs pivot decision** (item 1) — affects every work item query.
2. **ICS lookahead vs auto-create alignment** (item 5) — affects the calendar integration's user-visible promise.

After those, items 7 (FK behavior), 8 (date types), 9 (time zone), and 14 (tenant scoping) are the schema-shaping decisions that are hardest to reverse after implementation begins.
