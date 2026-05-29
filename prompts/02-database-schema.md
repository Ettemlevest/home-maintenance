# Database Schema

## `homes`

```sql
homes
- id
- name
- address_line_1 nullable
- address_line_2 nullable
- city nullable
- postal_code nullable
- country nullable
- notes nullable
- created_at
- updated_at
```

## `locations`

```sql
locations
- id
- home_id
- parent_id nullable
- name
- type -- property, house, room, area, outdoor_area, garden, garage, storage, technical_area, system_area, roof, facade, utility, other
- notes nullable
- sort_order default 0
- created_at
- updated_at
```

## `assets`

```sql
assets
- id
- home_id
- location_id nullable
- parent_id nullable
- name
- type -- building_part, appliance, fixture, device, system, structure, furniture, outdoor_equipment, utility_connection, other
- status -- active, needs_attention, broken, inactive, replaced, discarded
- condition -- excellent, good, fair, poor, broken, unknown
- condition_updated_at nullable
- manufacturer nullable
- model nullable
- serial_number nullable
- installed_at nullable
- warranty_until nullable
- expected_lifetime_months nullable
- notes nullable
- sort_order default 0
- created_at
- updated_at
```

## `work_items`

```sql
work_items
- id
- home_id
- location_id nullable
- asset_id nullable
- parent_id nullable
- recurring_rule_id nullable
- type -- task, maintenance, repair, test, inspection, cleaning, installation, replacement, upgrade, project
- status -- draft, open, completed, failed, blocked, cancelled
- title
- description nullable
- priority -- low, normal, high, urgent
- severity nullable -- minor, moderate, major, critical
- hard_due_at nullable
- due_window_start nullable
- due_window_end nullable
- completed_at nullable
- estimated_minutes nullable
- actual_minutes nullable
- estimated_cost nullable
- actual_cost nullable
- currency default 'HUF'
- assigned_to_user_id nullable
- created_by_user_id nullable
- source -- manual, recurring_rule, followup, inspection, warranty, imported, other
- materials_text nullable
- tools_text nullable
- safety_notes nullable
- created_at
- updated_at
```

## `work_item_steps`

```sql
work_item_steps
- id
- work_item_id
- sort_order
- title
- description nullable
- expected_result nullable
- status -- pending, completed, skipped, failed
- is_required boolean default true
- completed_at nullable
- created_at
- updated_at
```

## `work_item_results`

```sql
work_item_results
- id
- work_item_id
- result -- done, passed, failed, partially_done, fixed, not_fixed, skipped, needs_followup, unknown
- summary nullable
- notes nullable
- measured_value nullable
- measured_unit nullable
- followup_needed boolean default false
- followup_due_at nullable
- created_at
- updated_at
```

## `work_item_assets`

```sql
work_item_assets
- id
- work_item_id
- asset_id
- role -- primary, affected, inspected, tested, repaired, replaced, installed, removed
- created_at
- updated_at
```

## `recurring_rules`

```sql
recurring_rules
- id
- home_id
- location_id nullable
- asset_id nullable
- type -- task, maintenance, test, inspection, cleaning
- title
- description nullable
- priority -- low, normal, high, urgent
- severity nullable -- minor, moderate, major, critical
- estimated_minutes nullable
- estimated_cost nullable
- currency default 'HUF'
- interval_unit -- day, week, month, year
- interval_count unsigned integer
- reschedule_from -- completed_at, fixed_anchor
- only_after_success boolean default true
- window_strategy -- exact_day, rolling_window, calendar_month, calendar_season
- window_length_days nullable
- anchor_month nullable
- anchor_day nullable
- anchor_season nullable
- next_due_at nullable
- materials_text nullable
- tools_text nullable
- safety_notes nullable
- auto_create_days_before_due nullable
- reminder_days_before nullable
- is_active boolean default true
- created_at
- updated_at
```

## `recurring_rule_steps`

```sql
recurring_rule_steps
- id
- recurring_rule_id
- sort_order
- title
- description nullable
- expected_result nullable
- is_required boolean default true
- created_at
- updated_at
```

## `contacts`

```sql
contacts
- id
- home_id
- name
- company nullable
- type -- plumber, electrician, hvac, gardener, cleaner, handyman, contractor, supplier, warranty, inspector, builder, other
- email nullable
- phone nullable
- website nullable
- profile_photo_id nullable
- notes nullable
- created_at
- updated_at
```

## `photos`

```sql
photos
- id
- home_id
- photoable_type
- photoable_id
- type -- general, before, after, issue, condition, profile, progress, other
- filename
- path
- mime_type
- size
- taken_at nullable
- caption nullable
- notes nullable
- sort_order default 0
- created_at
- updated_at
```

## `paperless_links`

```sql
paperless_links
- id
- home_id
- linkable_type
- linkable_id
- type -- user_guide, warranty, invoice, receipt, certificate, service_report, quote, contract, declaration_of_performance, compliance_document, handover_document, other
- title
- url
- paperless_document_id nullable
- paperless_correspondent nullable
- paperless_created_at nullable
- description nullable
- comment nullable
- sort_order default 0
- created_at
- updated_at
```

## `external_links`

```sql
external_links
- id
- home_id
- linkable_type
- linkable_id
- type -- photo_album, manufacturer_page, product_page, support_page, user_guide_public, installation_guide, video, article, forum_thread, map, webshop, other
- title
- url
- comment nullable
- description nullable
- provider nullable -- Immich, iCloud, Google Drive, manufacturer, YouTube, webshop, other
- sort_order default 0
- created_at
- updated_at
```

## `expenses`

```sql
expenses
- id
- home_id
- work_item_id nullable
- asset_id nullable
- contact_id nullable
- type -- material, labor, service, replacement, tool, delivery, warranty, other
- amount
- currency default 'HUF'
- spent_at
- description nullable
- created_at
- updated_at
```

## `tags`

```sql
tags
- id
- home_id nullable
- name
- slug
- type -- season, system, area, topic, priority_theme, cost_category, custom
- description nullable
- comment nullable
- sort_order default 0
- color nullable
- created_at
- updated_at
```

## `taggables`

```sql
taggables
- tag_id
- taggable_type
- taggable_id
```

## `calendar_feeds`

```sql
calendar_feeds
- id
- home_id
- user_id nullable
- name
- token
- is_active
- include_open boolean
- include_blocked boolean
- include_failed boolean
- filter_types json nullable
- filter_tags json nullable
- filter_priorities json nullable
- lookahead_days default 180
- event_title_template
- event_description_template
- default_event_duration_minutes
- all_day_events boolean
- include_window_start_event boolean
- include_window_85_percent_event boolean
- include_window_end_event boolean
- include_overdue_event boolean
- created_at
- updated_at
```

## `users` and `home_users`

```sql
users
- id
- name
- email
- password
- created_at
- updated_at

home_users
- id
- home_id
- user_id
- role -- owner, adult, child, guest
- created_at
- updated_at
```

## Recommended indexes

```sql
locations: home_id, parent_id
assets: home_id, location_id, parent_id, status, condition
work_items: home_id/status, home_id/hard_due_at, home_id/due_window_start, home_id/due_window_end, asset_id, location_id, recurring_rule_id, type, priority
recurring_rules: home_id/is_active, home_id/next_due_at, asset_id, location_id
tags: unique(home_id, slug), type, sort_order
taggables: tag_id, taggable_type/taggable_id
photos: home_id, photoable_type/photoable_id, type, taken_at
paperless_links: home_id, linkable_type/linkable_id, type, paperless_document_id
external_links: home_id, linkable_type/linkable_id, type, provider
expenses: home_id/spent_at, work_item_id, asset_id
calendar_feeds: home_id, user_id, unique(token)
```
