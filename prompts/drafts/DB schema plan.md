# Table of Contents

1. Goal
2. Core concepts
3. Tables
    - homes
    - locations
    - assets
    - work_items
    - work_item_steps
    - work_item_results
    - work_item_assets
    - recurring_rules
    - recurring_rule_steps
    - contacts
    - work_item_contacts
    - photos
    - paperless_links
    - external_links
    - expenses
    - tags
    - taggables
    - users
    - home_users
4. Recommended indexes
5. Recommended enums
6. Seed data
    - Seed tags
    - Seed locations
    - Seed assets
    - Seed recurring rules
7. Suggested dashboard views
8. Recommended MVP table list
9. Recommended Laravel model names
10. Final design principle
11. Work item lifecycle and status model
12. Computed statuses
13. Due date model
14. Calendar integration
15. Business logic separation guidelines

---

# Simple Home Maintenance Database Schema with Seed Data

## Goal

This schema is for a lightweight family-house maintenance system.

It is designed to answer these practical questions:

- What are the most pressing upcoming house chores?
- What is overdue?
- What happened in the past with a specific asset or location?
- When was something last maintained, repaired, cleaned, inspected, or tested?
- What is the expected maintenance cost for the next 3 months, 6 months, 12 months, or next calendar year?
- Which recurring chores should be created automatically?
- Which photos, Paperless-ngx links, warranty links, user guide links, or notes belong to a house asset or past work?

It intentionally avoids:

- stock/inventory tracking
- procurement workflows
- tool inventory
- ERP-like accounting
- product catalog complexity
- warehouse-style quantity management
- invoice/document storage, because documents live in Paperless-ngx

---

# Core concepts

```text
Home
├─ Locations
├─ Assets
├─ Work Items
│  ├─ Steps
│  ├─ Results
│  ├─ Contacts
│  └─ Photos
├─ Paperless Links
├─ External Links
├─ Recurring Rules
├─ Tags
├─ Contacts
├─ Expenses
└─ Users
```

## Main modeling rule

```text
Asset = durable thing that has history.

Work item = something that needs doing or was done.

Location = where something is or where work happens.

Recurring rule = repeatable chore definition and scheduling logic.

Tag = flexible categorization, including season, system, priority theme, safety, exterior, garden, warranty, etc.

Photo = uploaded image used for visual history.

Paperless link = external URL or document reference pointing to warranty papers, invoices, manuals, service reports, or other documents stored in Paperless-ngx.
```

Consumables, parts, and tools are stored as free-text fields on work items or recurring rules:

```text
materials_text = "1x CR2032 battery, silicone sealant"
tools_text = "ladder, screwdriver"
safety_notes = "Turn off power before opening cover"
```

---

# Tables

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

---

## `locations`

Hierarchical locations inside and around the property.

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

Example:

```text
Telek
├─ Ház
│  ├─ Belső terek
│  │  ├─ Előszoba
│  │  ├─ Nappali
│  │  └─ Fürdő
│  ├─ Tető
│  ├─ Padlástér
│  └─ Gépészet
├─ Kert
├─ Garázs
└─ Kerti tároló
```

---

## `assets`

Durable things worth tracking over time.

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

Examples:

```text
Hőszivattyú kültéri egység
HMV tároló
Elektromos főelosztó
FI relé / ÁVK
Túlfeszültség-védelem
Vízszűrő
Nyílászárók
Tető
Ereszcsatorna
SIP külső falszerkezet
```

Use `parent_id` only when it adds value.

Example:

```text
Elektromos rendszer
├─ Főelosztó
├─ FI relé / ÁVK
├─ Túlfeszültség-védelem
└─ H-tarifa mérőhely
```

---

## `work_items`

The central table.

A work item can be upcoming, overdue, in progress, completed, failed, skipped, cancelled, or blocked.

```sql
work_items
- id
- home_id
- location_id nullable
- asset_id nullable
- parent_id nullable
- recurring_rule_id nullable

- type -- task, maintenance, repair, test, inspection, cleaning, installation, replacement, upgrade, project
- status -- planned, due, in_progress, blocked, completed, skipped, cancelled, failed

- title
- description nullable

- priority -- low, normal, high, urgent
- severity nullable -- minor, moderate, major, critical

- due_at nullable
- started_at nullable
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

Important rule:

```text
Not every work item has a cost.
Inspection, testing, and cleaning may have zero cost.
```

---

## `work_item_steps`

Optional checklist-style steps.

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

Example:

```text
Test FI relé / ÁVK

1. Press test button.
2. Verify immediate trip.
3. Reset breaker.
4. Record result.
```

---

## `work_item_results`

Completion/result record.

This table is important for history.

```sql
work_item_results
- id
- work_item_id

- result -- done, passed, failed, partially_done, fixed, not_fixed, needs_followup, unknown
- summary nullable
- notes nullable

- measured_value nullable
- measured_unit nullable

- followup_needed boolean default false
- followup_due_at nullable

- created_at
- updated_at
```

Examples:

```text
Smoke detector test: passed
Water pressure test: 3.2 bar
Inspection: cracked silicone found
Repair: leak fixed
```

---

## `work_item_assets`

Optional table for work involving multiple assets.

Use this for jobs like:

```text
Test all smoke detectors
Inspect all windows
Clean all gutters and downpipes
```

```sql
work_item_assets
- id
- work_item_id
- asset_id

- role -- primary, affected, inspected, tested, repaired, replaced, installed, removed

- created_at
- updated_at
```

For simple cases, `work_items.asset_id` is enough.

---

# Recurring rules

## `recurring_rules`

Defines repeated household chores.

This table replaces fixed calendar-only repetition with flexible rolling recurrence.

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

- reschedule_from -- due_date, completed_at
- only_after_success boolean default true

- start_date
- end_date nullable
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

## Recommended recurrence behavior

For this project, the preferred behavior is:

```text
interval_unit = month or year
interval_count = N
reschedule_from = completed_at
only_after_success = true
```

Meaning:

```text
If a 3-month task was due on March 1,
but completed successfully on March 20,
the next due date becomes June 20.
```

This avoids schedules drifting into nonsense after late completion.

## `only_after_success`

If `only_after_success = true`, the next due date should only advance when the generated work item gets a successful result.

Successful result values:

```text
done
passed
fixed
```

Unsuccessful or incomplete values:

```text
failed
partially_done
not_fixed
needs_followup
unknown
```

If unsuccessful, the app should create or suggest a follow-up work item instead of advancing the recurring rule.

---

## `recurring_rule_steps`

Optional reusable checklist steps copied into generated work items.

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

---

# Contacts

## `contacts`

External people, companies, contractors, warranty partners, or service providers.

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

- profile_photo_id nullable -- optional reference to photos.id, or use morphMany photos with type = profile

- notes nullable

- created_at
- updated_at
```

---

## `work_item_contacts`

```sql
work_item_contacts
- id
- work_item_id
- contact_id

- role -- performer, inspector, supplier, warranty_contact, helper, quoted_by

- created_at
- updated_at
```

---

# Photos and Paperless-ngx links

## Design rule

This app stores **photos only**.

It does not store invoices, manuals, warranty documents, quotes, service reports, or other documents. Those should be stored in a central Paperless-ngx instance.

This app should only store lightweight external references to Paperless-ngx where useful.

---

## `photos`

Visual history for assets, work items, locations, and contacts.

Use this as a polymorphic table.

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

Useful photo targets:

```text
assets
work_items
locations
contacts
```

Examples:

```text
Asset condition photo
Before/after repair photo
Construction progress photo
Contact profile picture
Visible defect photo
```

---

## Paperless-ngx references

Use a lightweight polymorphic table for external Paperless-ngx document links.

This app should not store the documents themselves. It only stores references to documents stored in Paperless-ngx.

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

Recommended linkable targets:

```text
assets
work_items
contacts
expenses
locations
```

Common document link types:

```text
user_guide
warranty
invoice
receipt
certificate
service_report
quote
contract
declaration_of_performance
compliance_document
handover_document
other
```

Examples:

```text
Asset: Hőszivattyú beltéri egység
Paperless links:
- user_guide
- warranty
- invoice
- service_report

Asset: Nyílászárók
Paperless links:
- warranty
- declaration_of_performance
- invoice

Work item: Hőszivattyú éves szerviz
Paperless links:
- service_report
- invoice
```

This keeps the app simple while still allowing multiple document references per item.

---

# External links

## Design rule

External links are optional references to useful resources outside this app.

They are different from `paperless_links`:

```text
paperless_links = private document references in Paperless-ngx
external_links = general URLs such as photo albums, manufacturer pages, product pages, support pages, guides, public resources
```

## `external_links`

Use this as a lightweight polymorphic table.

```sql
external_links
- id
- home_id

- linkable_type
- linkable_id

- type -- photo_album, manufacturer_page, product_page, support_page, user_guide_public, installation_guide, video, article, forum_thread, map, webshop, other

- title
- url

- comment nullable -- why this link is useful, what to look for, caveats
- description nullable

- provider nullable -- Immich, iCloud, Google Drive, manufacturer, YouTube, webshop, other

- sort_order default 0

- created_at
- updated_at
```

Recommended linkable targets:

```text
assets
work_items
locations
contacts
recurring_rules
```

Common examples:

```text
Asset: Hőszivattyú kültéri egység
External links:
- manufacturer_page: gyártói termékoldal
- support_page: gyártói support oldal
- photo_album: Immich album a telepítésről

Asset: Nyílászárók
External links:
- photo_album: beépítéskori fotók
- manufacturer_page: profilrendszer oldala

Work item: Ereszcsatorna tisztítás
External links:
- video: biztonságos létrahasználat
- photo_album: korábbi tisztítás képei

Location: Padlástér
External links:
- photo_album: átadáskori padlástér fotók
```

Do not use external links for invoices, warranty papers, certificates, or service reports if those are already in Paperless-ngx. Use `paperless_links` for those.

---

# Expenses

## `expenses`

Simple cost history.

This is not full accounting.

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

## Cost forecasting

A separate `budget_forecasts` table is not needed for the MVP.

Forecasts should be calculated dynamically from:

```text
work_items.estimated_cost
work_items.due_at
recurring_rules.estimated_cost
recurring_rules.next_due_at
tags
asset type
asset tags
location tags
```

Example report windows:

```text
next 30 days
next 3 months
next 6 months
next 12 months
next calendar year
custom date range
```

---

# Tags

## `tags`

Flexible categorization.

Use tags for:

- seasons
- systems
- safety
- exterior/interior
- warranty
- cost grouping
- dashboard filters

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

---

## `taggables`

Polymorphic tag relationship.

```sql
taggables
- tag_id
- taggable_type
- taggable_id
```

Taggable targets:

```text
locations
assets
work_items
recurring_rules
contacts
expenses
```

## Cost category inference

Cost categories should be inferred in this priority order:

```text
1. Explicit work_item cost_category tag
2. Explicit recurring_rule cost_category tag
3. Asset tag
4. Asset type
5. Location tag
6. Contact type
```

Example:

```text
Asset: Hőszivattyú kültéri egység
Asset tag: HVAC

Work item: Éves hőszivattyú szerviz
No explicit cost category

Inferred cost category: HVAC
```

---

# Users and household membership

## `users`

```sql
users
- id
- name
- email
- password
- created_at
- updated_at
```

---

## `home_users`

```sql
home_users
- id
- home_id
- user_id

- role -- owner, adult, child, guest

- created_at
- updated_at
```

---

# Recommended indexes

```sql
locations
- index(home_id)
- index(parent_id)

assets
- index(home_id)
- index(location_id)
- index(parent_id)
- index(status)
- index(condition)

work_items
- index(home_id, status)
- index(home_id, due_at)
- index(asset_id)
- index(location_id)
- index(recurring_rule_id)
- index(type)
- index(priority)

recurring_rules
- index(home_id, is_active)
- index(home_id, next_due_at)
- index(asset_id)
- index(location_id)

tags
- unique(home_id, slug)
- index(type)

taggables
- index(tag_id)
- index(taggable_type, taggable_id)

photos
- index(home_id)
- index(photoable_type, photoable_id)
- index(type)
- index(taken_at)

paperless_links
- index(home_id)
- index(linkable_type, linkable_id)
- index(type)
- index(paperless_document_id)

external_links
- index(home_id)
- index(linkable_type, linkable_id)
- index(type)
- index(provider)

expenses
- index(home_id, spent_at)
- index(work_item_id)
- index(asset_id)
```

---

# Recommended enums

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

## Work item statuses

```text
planned
due
in_progress
blocked
completed
skipped
cancelled
failed
```

## Work item result values

```text
done
passed
failed
partially_done
fixed
not_fixed
needs_followup
unknown
```

## Asset statuses

```text
active
needs_attention
broken
inactive
replaced
discarded
```

## Asset conditions

```text
excellent
good
fair
poor
broken
unknown
```

## Asset types

```text
building_part
appliance
fixture
device
system
structure
furniture
outdoor_equipment
utility_connection
other
```

## Priorities

```text
low
normal
high
urgent
```

## Severities

```text
minor
moderate
major
critical
```

## Recurrence interval units

```text
day
week
month
year
```

## Recurrence reschedule modes

```text
due_date
completed_at
```

---

# Seed data

The following seed data should be created automatically for a new home.

The examples below assume one home:

```text
Home: Családi ház
```

---

## Seed tags

### Season tags

```text
spring
summer
autumn
winter
```

Suggested display names:

```text
Tavasz
Nyár
Ősz
Tél
```

Suggested type:

```text
season
```

Suggested order:

```text
spring = 10
summer = 20
autumn = 30
winter = 40
```

---

### System tags

```text
hvac
plumbing
electrical
low-voltage
hot-water
sewage
rainwater
building-envelope
roof
windows-doors
sip-structure
garden
exterior
interior
security
internet-network
```

Suggested display names:

```text
HVAC / hőszivattyú
Vízrendszer
Villamos rendszer
Gyengeáram
HMV
Szennyvíz
Csapadékvíz
Épületburok
Tető
Nyílászárók
SIP szerkezet
Kert
Kültér
Beltér
Biztonságtechnika
Internet / hálózat
```

Suggested type:

```text
system
```

---

### Topic tags

```text
safety
warranty
annual-service
cleaning
inspection
documentation
before-covering
urgent-risk
energy-efficiency
child-safe
contractor-needed
```

Suggested display names:

```text
Biztonság
Garancia
Éves szerviz
Tisztítás
Ellenőrzés
Dokumentáció
Eltakarás előtti
Sürgős kockázat
Energiahatékonyság
Gyerekbiztonság
Szakember szükséges
```

Suggested type:

```text
topic
```

---

### Cost category tags

```text
cost-hvac
cost-plumbing
cost-electrical
cost-garden
cost-roof
cost-exterior
cost-appliance
cost-safety
cost-general-maintenance
```

Suggested display names:

```text
Költség: HVAC
Költség: Víz/gépészet
Költség: Villany
Költség: Kert
Költség: Tető
Költség: Kültér
Költség: Háztartási gép
Költség: Biztonság
Költség: Általános karbantartás
```

Suggested type:

```text
cost_category
```

---

## Seed locations

For an existing one-story family house with garden, garage, and garden storage.

```text
Telek
├─ Ház
│  ├─ Belső terek
│  │  ├─ Előszoba
│  │  ├─ Nappali
│  │  ├─ Konyha + étkező
│  │  ├─ Kamra
│  │  ├─ Fürdő
│  │  ├─ WC
│  │  ├─ Háztartási helyiség
│  │  ├─ Dolgozó
│  │  ├─ Szoba
│  │  └─ Közlekedő
│  ├─ Gépészet
│  ├─ Villamos rendszer
│  ├─ Gyengeáram / hálózat
│  ├─ Padlástér
│  ├─ Tető
│  ├─ Eresz / csatorna
│  ├─ Homlokzat
│  ├─ Lábazat
│  ├─ Nyílászárók
│  └─ SIP szerkezet
│     ├─ Külső SIP falak
│     ├─ Belső előtétfalak
│     ├─ Födém / padlásfödém
│     ├─ Nyílászáró csatlakozások
│     ├─ Légzárási kritikus pontok
│     ├─ Gépészeti áttörések
│     └─ Villamos áttörések
├─ Kert
│  ├─ Előkert
│  ├─ Hátsókert
│  ├─ Oldalkert
│  ├─ Terasz
│  ├─ Járdák
│  ├─ Bejáró / gépkocsi beálló
│  ├─ Kerítés
│  ├─ Kapu
│  ├─ Kerti csapok
│  ├─ Csapadékvíz-elvezetés
│  └─ Szikkasztó / esővízkezelés
├─ Garázs
└─ Kerti tároló
```

Suggested `location.type` values:

```text
Telek = property
Ház = house
Belső terek = area
Rooms = room
Gépészet = technical_area
Villamos rendszer = system_area
Gyengeáram / hálózat = system_area
Padlástér = area
Tető = roof
Eresz / csatorna = roof
Homlokzat = facade
Lábazat = facade
Nyílászárók = area
SIP szerkezet = system_area
Kert = garden
Garázs = garage
Kerti tároló = storage
```

---

## Seed assets

These are known or strongly expected from the current house project.

### Building envelope / SIP structure

```text
Asset: SIP külső falszerkezet
Location: Ház / SIP szerkezet / Külső SIP falak
Type: structure
Status: active
Condition: unknown
Tags: sip-structure, building-envelope, exterior

Asset: Belső előtétfalak
Location: Ház / SIP szerkezet / Belső előtétfalak
Type: building_part
Status: active
Condition: unknown
Tags: sip-structure, interior

Asset: Födém / padlásfödém
Location: Ház / SIP szerkezet / Födém / padlásfödém
Type: structure
Status: active
Condition: unknown
Tags: sip-structure, building-envelope

Asset: Homlokzati hőszigetelés és vakolat
Location: Ház / Homlokzat
Type: building_part
Status: active
Condition: unknown
Tags: building-envelope, exterior

Asset: Lábazat
Location: Ház / Lábazat
Type: building_part
Status: active
Condition: unknown
Tags: building-envelope, exterior

Asset: Nyílászárók
Location: Ház / Nyílászárók
Type: building_part
Status: active
Condition: unknown
Tags: windows-doors, building-envelope
```

---

### Roof and rainwater

```text
Asset: Tetőfedés
Location: Ház / Tető
Type: structure
Status: active
Condition: unknown
Tags: roof, exterior

Asset: Szeglemezes tetőszerkezet
Location: Ház / Tető
Type: structure
Status: active
Condition: unknown
Tags: roof

Asset: Ereszcsatorna rendszer
Location: Ház / Eresz / csatorna
Type: building_part
Status: active
Condition: unknown
Tags: roof, rainwater, exterior

Asset: Csapadékvíz-elvezetés
Location: Kert / Csapadékvíz-elvezetés
Type: system
Status: active
Condition: unknown
Tags: rainwater, garden, exterior

Asset: Szikkasztó / esővízkezelés
Location: Kert / Szikkasztó / esővízkezelés
Type: system
Status: active
Condition: unknown
Tags: rainwater, garden
```

---

### HVAC and hot water

```text
Asset: Hőszivattyú kültéri egység
Location: Kert
Type: appliance
Status: active
Condition: unknown
Tags: hvac, electrical, exterior, cost-hvac

Asset: Hőszivattyú beltéri egység
Location: Ház / Gépészet
Type: appliance
Status: active
Condition: unknown
Tags: hvac, electrical, cost-hvac

Asset: HMV tároló
Location: Ház / Gépészet
Type: appliance
Status: active
Condition: unknown
Tags: hot-water, plumbing, hvac, cost-plumbing

Asset: Osztó-gyűjtő
Location: Ház / Gépészet
Type: system
Status: active
Condition: unknown
Tags: hvac, plumbing

Asset: Elektromos törölközőszárító
Location: Ház / Fürdő
Type: appliance
Status: active
Condition: unknown
Tags: electrical, bathroom, cost-electrical
```

---

### Plumbing and sewage

```text
Asset: Vízbekötés
Location: Telek
Type: utility_connection
Status: active
Condition: unknown
Tags: plumbing

Asset: Vízszűrő / nyomáscsökkentő
Location: Ház / Gépészet
Type: system
Status: active
Condition: unknown
Tags: plumbing, cost-plumbing

Asset: Kerti csapok
Location: Kert / Kerti csapok
Type: fixture
Status: active
Condition: unknown
Tags: plumbing, garden, exterior

Asset: Szennyvíz bekötés
Location: Telek
Type: utility_connection
Status: active
Condition: unknown
Tags: sewage, plumbing

Asset: Belső szennyvízhálózat
Location: Ház / Gépészet
Type: system
Status: active
Condition: unknown
Tags: sewage, plumbing
```

---

### Electrical

```text
Asset: Villamos mérőhely
Location: Telek
Type: utility_connection
Status: active
Condition: unknown
Tags: electrical

Asset: H-tarifa mérőhely
Location: Telek
Type: utility_connection
Status: active
Condition: unknown
Tags: electrical, hvac

Asset: Elektromos főelosztó
Location: Ház / Villamos rendszer
Type: system
Status: active
Condition: unknown
Tags: electrical, cost-electrical

Asset: FI relé / ÁVK
Location: Ház / Villamos rendszer
Type: device
Status: active
Condition: unknown
Tags: electrical, safety, cost-safety

Asset: Túlfeszültség-védelem
Location: Ház / Villamos rendszer
Type: device
Status: active
Condition: unknown
Tags: electrical, safety, cost-safety

Asset: EPH rendszer
Location: Ház / Villamos rendszer
Type: system
Status: active
Condition: unknown
Tags: electrical, safety

Asset: Napelem előkészítés
Location: Ház / Villamos rendszer
Type: system
Status: active
Condition: unknown
Tags: electrical, energy-efficiency
```

---

### Low-voltage / network / security

```text
Asset: Router / hálózati központ
Location: Ház / Gyengeáram / hálózat
Type: device
Status: active
Condition: unknown
Tags: internet-network, low-voltage

Asset: Riasztóközpont előkészítés
Location: Ház / Gyengeáram / hálózat
Type: system
Status: active
Condition: unknown
Tags: security, low-voltage

Asset: Kamerarendszer előkészítés
Location: Ház / Gyengeáram / hálózat
Type: system
Status: active
Condition: unknown
Tags: security, low-voltage, exterior
```

---

### Exterior

```text
Asset: Terasz
Location: Kert / Terasz
Type: structure
Status: active
Condition: unknown
Tags: exterior, garden

Asset: Kerítés
Location: Kert / Kerítés
Type: structure
Status: active
Condition: unknown
Tags: exterior, garden

Asset: Kapu
Location: Kert / Kapu
Type: structure
Status: active
Condition: unknown
Tags: exterior, garden

Asset: Bejáró / gépkocsi beálló
Location: Kert / Bejáró / gépkocsi beálló
Type: structure
Status: active
Condition: unknown
Tags: exterior, garden

Asset: Kerti tároló
Location: Kerti tároló
Type: structure
Status: active
Condition: unknown
Tags: exterior, garden
```

---

# Seed recurring rules

These are suggested starter rules. They should be easy to disable.

## Safety and electrical

```text
Title: FI relé / ÁVK teszt
Type: test
Asset: FI relé / ÁVK
Interval: every 1 month
Reschedule from: completed_at
Only after success: true
Priority: high
Estimated cost: 0 HUF
Tags: safety, electrical
Steps:
1. Nyomd meg a teszt gombot.
2. Ellenőrizd, hogy az áramvédő kapcsoló azonnal leold.
3. Kapcsold vissza.
4. Rögzítsd az eredményt.
```

```text
Title: Túlfeszültség-védelem állapotának ellenőrzése
Type: inspection
Asset: Túlfeszültség-védelem
Interval: every 6 months
Reschedule from: completed_at
Only after success: true
Priority: normal
Estimated cost: 0 HUF
Tags: safety, electrical
Steps:
1. Ellenőrizd a túlfeszültség-védelmi eszköz állapotjelzőjét.
2. Ha hibát jelez, hozz létre javítási feladatot villanyszerelővel.
```

---

## HVAC / heat pump

```text
Title: Hőszivattyú kültéri egység szemrevételezése
Type: inspection
Asset: Hőszivattyú kültéri egység
Interval: every 3 months
Reschedule from: completed_at
Only after success: true
Priority: normal
Estimated cost: 0 HUF
Tags: hvac, exterior, inspection
Steps:
1. Ellenőrizd, hogy nincs-e falevél, sár, hó vagy akadály a kültéri egység körül.
2. Ellenőrizd, hogy nincs-e szokatlan zaj, rezgés vagy sérülés.
3. Készíts fotót, ha eltérést látsz.
```

```text
Title: Hőszivattyú éves szerviz
Type: maintenance
Asset: Hőszivattyú beltéri egység
Interval: every 1 year
Reschedule from: completed_at
Only after success: true
Priority: high
Estimated cost: nullable
Tags: hvac, annual-service, contractor-needed, cost-hvac
Steps:
1. Egyeztess szakszervizzel.
2. Kérj szervizjegyzőkönyvet.
3. Töltsd fel a jegyzőkönyvet mellékletként.
4. Rögzítsd a tényleges költséget.
```

---

## Hot water / plumbing

```text
Title: HMV biztonsági szelep ellenőrzése
Type: test
Asset: HMV tároló
Interval: every 6 months
Reschedule from: completed_at
Only after success: true
Priority: normal
Estimated cost: 0 HUF
Tags: hot-water, plumbing, safety
Steps:
1. Ellenőrizd a biztonsági szelep környezetét szivárgásra.
2. Ha a gyártói utasítás engedi, teszteld a működést.
3. Ha folyamatos csöpögés vagy rendellenesség van, hozz létre javítási feladatot.
```

```text
Title: Vízszűrő ellenőrzése / csere szükség szerint
Type: inspection
Asset: Vízszűrő / nyomáscsökkentő
Interval: every 3 months
Reschedule from: completed_at
Only after success: true
Priority: normal
Estimated cost: nullable
Tags: plumbing, inspection, cost-plumbing
Steps:
1. Ellenőrizd a vízszűrő állapotát.
2. Ha elszíneződött vagy eltömődött, cseréld vagy hozz létre cserefeladatot.
3. Rögzítsd, hogy történt-e csere.
```

```text
Title: Kerti csapok téliesítése
Type: maintenance
Asset: Kerti csapok
Interval: every 1 year
Reschedule from: completed_at
Only after success: true
Priority: high
Estimated cost: 0 HUF
Tags: autumn, winter, plumbing, garden, exterior
Steps:
1. Zárd el a kerti csapok vízellátását.
2. Ürítsd le a csapokat.
3. Ellenőrizd, hogy nincs-e fagyveszélyes szakasz vízzel telve.
```

---

## Roof / rainwater / exterior

```text
Title: Ereszcsatorna tisztítás és ellenőrzés
Type: cleaning
Asset: Ereszcsatorna rendszer
Interval: every 6 months
Reschedule from: completed_at
Only after success: true
Priority: normal
Estimated cost: 0 HUF
Tags: autumn, spring, roof, rainwater, exterior
Safety notes: Magasban végzett munka; stabil létra szükséges.
Steps:
1. Távolítsd el a leveleket és lerakódásokat.
2. Ellenőrizd a lefolyók átjárhatóságát.
3. Ellenőrizd, hogy nincs-e sérült vagy elmozdult csatornaelem.
4. Készíts fotót problémás részekről.
```

```text
Title: Tető szemrevételezés
Type: inspection
Asset: Tetőfedés
Interval: every 1 year
Reschedule from: completed_at
Only after success: true
Priority: normal
Estimated cost: 0 HUF
Tags: roof, exterior, inspection
Steps:
1. Nézd át földről vagy biztonságos helyről a tetőfedést.
2. Keress elmozdult, törött vagy hiányzó elemeket.
3. Ellenőrizd az áttörések és bádogozások környezetét.
4. Hiba esetén hozz létre javítási feladatot.
```

```text
Title: Homlokzat és lábazat ellenőrzése
Type: inspection
Asset: Homlokzati hőszigetelés és vakolat
Interval: every 1 year
Reschedule from: completed_at
Only after success: true
Priority: normal
Estimated cost: 0 HUF
Tags: exterior, building-envelope, inspection
Steps:
1. Ellenőrizd a repedéseket, sérüléseket, elszíneződéseket.
2. Ellenőrizd a lábazat környékét felverődő víz és mechanikai sérülés miatt.
3. Készíts fotót az eltérésekről.
```

---

## SIP / building envelope

```text
Title: Padlástér és födém szemrevételezés
Type: inspection
Asset: Födém / padlásfödém
Interval: every 6 months
Reschedule from: completed_at
Only after success: true
Priority: high
Estimated cost: 0 HUF
Tags: sip-structure, building-envelope, inspection, safety
Steps:
1. Ellenőrizd, hogy látható-e beázás, nedvesedés vagy elszíneződés.
2. Ellenőrizd, hogy nincs-e sérült hőszigetelés.
3. Ellenőrizd a gépészeti/villamos áttörések környezetét.
4. Fotózd le a gyanús helyeket.
```

```text
Title: Nyílászáró csatlakozások ellenőrzése
Type: inspection
Asset: Nyílászárók
Interval: every 1 year
Reschedule from: completed_at
Only after success: true
Priority: normal
Estimated cost: 0 HUF
Tags: windows-doors, building-envelope, sip-structure
Steps:
1. Ellenőrizd a belső páralecsapódás, elszíneződés vagy penész nyomait.
2. Ellenőrizd a külső tömítések és párkányok állapotát.
3. Ellenőrizd a nyitás-zárást és vasalatot.
4. Hiba esetén hozz létre javítási feladatot.
```

---

## Garden / exterior

```text
Title: Kerítés és kapu ellenőrzése
Type: inspection
Asset: Kapu
Interval: every 1 year
Reschedule from: completed_at
Only after success: true
Priority: low
Estimated cost: 0 HUF
Tags: garden, exterior, inspection
Steps:
1. Ellenőrizd a kapu záródását és mechanikai állapotát.
2. Ellenőrizd a kerítésszakaszokat sérülésre.
3. Rögzítsd, ha javítás szükséges.
```

```text
Title: Kerti tároló ellenőrzése
Type: inspection
Asset: Kerti tároló
Interval: every 1 year
Reschedule from: completed_at
Only after success: true
Priority: low
Estimated cost: 0 HUF
Tags: garden, exterior, inspection
Steps:
1. Ellenőrizd a tetőt és ajtót.
2. Ellenőrizd, hogy nincs-e beázás.
3. Ellenőrizd, hogy nincs-e kártevő vagy penésznyom.
```

---

# Suggested dashboard views

## 1. Most pressing upcoming chores

Filter:

```text
status in planned, due, blocked
order by priority, due_at
```

Show:

```text
title
asset
location
due date
priority
estimated cost
tags
```

---

## 2. Overdue chores

Filter:

```text
due_at < today
status not in completed, skipped, cancelled
```

---

## 3. Upcoming seasonal chores

Based on season tags:

```text
Tavaszi teendők
Nyári teendők
Őszi teendők
Téli teendők
```

---

## 4. Expected costs

Calculated dynamically:

```text
Next 30 days
Next 3 months
Next 6 months
Next 12 months
Next calendar year
```

Include:

```text
estimated cost from work_items
estimated cost from recurring_rules next_due_at
group by inferred cost category
```

---

## 5. Asset history

For an asset, show:

```text
past work items
results
expenses
photos
condition changes
warranty documents
photos
```

---

## 6. Follow-up required

Filter:

```text
work_item_results.followup_needed = true
```

Order by:

```text
followup_due_at asc
```

---

# Recommended MVP table list

Start with:

```text
homes
locations
assets
work_items
work_item_steps
work_item_results
work_item_assets
recurring_rules
recurring_rule_steps
tags
taggables
contacts
work_item_contacts
photos
paperless_links
external_links
expenses
users
home_users
```

Do not add unless there is real need:

```text
inventory_items
inventory_movements
product_categories
products
tools
tool_assignments
budget_forecasts
asset_events
document_storage
```

---

# Recommended Laravel model names

```text
Home
Location
Asset
WorkItem
WorkItemStep
WorkItemResult
WorkItemAsset
RecurringRule
RecurringRuleStep
Tag
Contact
Photo
PaperlessLink
ExternalLink
Expense
User
```

Recommended pivot models:

```text
HomeUser
Taggable
WorkItemContact
WorkItemAsset
```

---

# Final design principle

Keep the system centered on:

```text
what needs doing
what was done
where it happened
which asset it affected
what the result was
what it cost or is expected to cost
what comes next
```

Avoid modeling things that create data-entry burden without direct household value.



Recommended polymorphic models:

```text
Photo
PaperlessLink
ExternalLink
Tag
```

`PaperlessLink` should use a polymorphic `morphTo` relation, similar to `Photo`, but it stores only external Paperless-ngx document references, not uploaded documents.

`ExternalLink` should also use a polymorphic `morphTo` relation and stores general-purpose URLs such as photo albums, manufacturer pages, product pages, support resources, and useful public references.


---

# Work item lifecycle and status model

## Design goal

This application is not a project management tool.

The status system is intentionally optimized for household maintenance where most tasks are completed in a few minutes and users typically do not want to mark tasks as "started" before marking them as completed.

Examples:

```text
Replace smoke detector battery
Test FI relay / ÁVK
Inspect roof
Clean gutter
Check heat pump outdoor unit
```

For these types of activities, an `in_progress` state creates additional user interaction without providing much value.

Therefore the workflow is intentionally simplified.

---

## Stored statuses

Only the following statuses should be persisted in the database.

### Overview table

| Status | Meaning | Visible in normal dashboards | Typical next state |
|----------|----------|----------|----------|
| draft | Idea or future task that is not active yet | No | open, cancelled |
| open | Active task waiting to be completed | Yes | completed, failed, blocked, cancelled |
| completed | Successfully finished | History only | N/A |
| failed | Attempted but unsuccessful | Yes (attention required) | open, completed |
| blocked | Cannot currently be completed | Yes (attention required) | open, completed, cancelled |
| cancelled | No longer relevant | History only | N/A |

---

### draft

The task exists but should not yet participate in maintenance planning.

Examples:

```text
Future solar installation
Replace fence next year
Install irrigation system someday
```

Typical characteristics:

```text
Not visible in upcoming maintenance dashboards
May not have a due date yet
May still be under consideration
```

---

### open

The only active work status.

Examples:

```text
Annual heat pump service
Smoke detector test
Gutter cleaning
Roof inspection
```

Whether the task is upcoming, due, or overdue is determined dynamically from:

```text
status = open
due_at
current date
```

---

### completed

The work was successfully completed.

Examples:

```text
Battery replaced
Heat pump serviced
Gutter cleaned
Roof inspected
```

Usually accompanied by a WorkItemResult such as:

```text
done
passed
fixed
```

---

### failed

The work was attempted but did not achieve the intended result.

Examples:

```text
FI relay test failed
Leak still present after repair
Heat pump service revealed unresolved fault
```

These items should appear in "needs attention" dashboards.

---

### blocked

The task cannot currently be completed.

Examples:

```text
Waiting for contractor
Waiting for spare part
Waiting for warranty response
Waiting for suitable weather
```

These items should appear in "needs attention" dashboards.

---

### cancelled

The task is no longer relevant.

Examples:

```text
Replace boiler
(cancelled because a heat pump was installed instead)

Replace fence
(cancelled because fence was fully rebuilt during another project)
```

Cancelled items remain available for historical reference.

---

## Work item result values

Status describes the lifecycle of the task.

Result describes the outcome.

Recommended values:

| Result | Meaning |
|----------|----------|
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

Reason:
Roof replacement project already included gutter cleaning.
```

This preserves useful history without introducing a separate `skipped` status.

---

# Computed statuses

## Design rule

The following states should NOT be stored in the database.

They should always be calculated dynamically.

Benefits:

```text
No synchronization problems
No scheduled status updates required
Simpler business logic
Simpler queries
```

---

## upcoming

Definition:

```text
status = open
AND due_at > current_date
```

Purpose:

```text
Upcoming work list
Maintenance planning
Expected cost forecasting
```

Typical dashboard:

```text
Next 30 days
Next 3 months
Next 6 months
```

---

## due_today

Definition:

```text
status = open
AND due_at = current_date
```

Purpose:

```text
Today's chores
Daily dashboard
Notifications
```

---

## overdue

Definition:

```text
status = open
AND due_at < current_date
```

Purpose:

```text
Highest priority maintenance view
Escalation reporting
Missed recurring maintenance
```

Recommended sorting:

```text
Priority DESC
Due date ASC
```

Oldest overdue items first.

---

## needs_attention

Definition:

```text
status IN (
    failed,
    blocked
)
```

Purpose:

```text
Identify unresolved issues
Identify tasks waiting on third parties
Identify maintenance risks
```

Recommended dashboard:

```text
Failed repairs
Failed tests
Warranty issues
Contractor dependencies
```

---

## completed_recently

Definition:

```text
status = completed
AND completed_at >= NOW() - configurable_window
```

Examples:

```text
Last 7 days
Last 30 days
Last 90 days
```

Purpose:

```text
Activity feed
Maintenance history overview
Family visibility
```

---

# Recommended dashboard implementation

## Primary dashboard

Sections:

```text
Needs Attention
Overdue
Due Today
Upcoming Next 30 Days
Expected Cost Next 3 Months
Recently Completed
```

---

## Asset detail page

Show:

```text
Asset details
Current condition
Warranty links
User guide links
Photo timeline
Upcoming work
Past work
Related expenses
```

---

## Recurring maintenance workflow

Recurring rules should create work items in:

```text
status = open
```

When successfully completed:

```text
status = completed
result = done | passed | fixed
```

The recurring rule then calculates:

```text
next_due_at =
completed_at + interval
```

When failed:

```text
status = failed
```

The recurring rule should not advance until a successful completion is recorded if:

```text
only_after_success = true
```


---

# Business logic separation guidelines

## Architecture recommendation

Use a **modular monolith**.

This means:

```text
One Laravel application
One shared database
Separated business logic modules
Clear module boundaries
```

Do **not** split this into separate applications or separate databases for the MVP.

The goal is to keep deployment and development simple while avoiding a tangled codebase.

---

## Recommended module structure

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
        Models/
          PaperlessLink.php
        Services/
          PaperlessLinkService.php

      ExternalLinks/
        Models/
          ExternalLink.php
        Services/
          ExternalLinkService.php

      Calendar/
        Models/
          CalendarFeed.php
        Services/
          IcsFeedGenerator.php
          CalendarEventBuilder.php
          CalendarFeedFilterService.php

      Photos/
        Models/
          Photo.php
        Services/
          PhotoTimelineService.php
```

Alternative Laravel naming is fine, but the ownership boundaries should stay clear.

---

## Shared database, separated ownership

The database remains shared.

Example tables:

```text
homes
locations
assets
work_items
work_item_steps
work_item_results
recurring_rules
recurring_rule_steps
tags
taggables
photos
paperless_links
external_links
calendar_feeds
contacts
expenses
users
home_users
```

But each module owns its own behavior.

---

# Maintenance module

## Responsibility

The Maintenance module is the core domain.

It owns:

```text
What needs doing
What was done
What is due next
What is overdue
What recurrence generates next
What the expected cost is
Which asset or location is affected
```

## It should contain logic for

```text
Creating work items
Completing work items
Marking work items as failed
Marking work items as blocked
Calculating computed states
Generating next recurring work item
Calculating due windows
Calculating hard deadline urgency
Cost forecasting
Dashboard queries
Asset history
```

## It should not contain logic for

```text
ICS formatting
Calendar feed tokens
Paperless-ngx URL handling
External public link handling
Photo storage internals
Email notification formatting
Home Assistant integration
```

The Maintenance module should expose clean data that integrations can consume.

---

# Calendar integration module

## Responsibility

The Calendar module turns maintenance data into read-only ICS feeds.

It owns:

```text
Calendar feed configuration
Feed tokens
ICS generation
Event title templates
Event description templates
Emoji prefixes
Calendar event timing
Feed-level filtering
```

## It consumes from Maintenance

```text
Open work items
Blocked work items
Failed work items
Hard deadlines
Due windows
Computed urgency
Work item tags
Assets
Locations
Estimated costs
```

## It should not decide

```text
Whether a task is overdue
Whether recurrence should advance
Whether a work item is successful
What the next due window is
```

Those decisions belong to the Maintenance module.

---

# Paperless integration module

## Responsibility

The Paperless module stores references to documents living in Paperless-ngx.

It owns:

```text
Paperless document URLs
Paperless document IDs
Document link type
Display metadata for Paperless links
```

Examples:

```text
user_guide
warranty
invoice
receipt
certificate
service_report
quote
contract
declaration_of_performance
compliance_document
handover_document
other
```

## It should not store

```text
PDF files
Invoices
Warranty documents
Manual files
Certificates
```

Those remain in Paperless-ngx.

This app only stores references.

---

# External links module

## Responsibility

The External Links module stores general-purpose URLs.

It owns links to:

```text
Immich photo albums
iCloud albums
Google Drive folders
Manufacturer pages
Product pages
Support pages
Installation guides
Videos
Public web resources
Forum threads
Maps
Webshops
```

Each external link should have:

```text
title
url
type
provider
optional comment
optional description
sort_order
```

The comment should explain why the link is useful.

Example:

```text
Title:
Hőszivattyú gyártói termékoldal

Comment:
Useful for checking official specs, documentation, and compatibility notes.
```

---

# Photos module

## Responsibility

The Photos module stores uploaded image files and provides visual timelines.

It owns:

```text
Asset photo timeline
Work item before/after photos
Issue photos
Condition photos
Contact profile pictures
Photo captions
Photo sorting
```

It does not store documents.

---

# Contacts

Contacts can remain in the Maintenance domain or become their own small shared module later.

For the MVP, it is acceptable to keep them as shared supporting data.

Important feature:

```text
contacts.profile_photo_id nullable
```

or alternatively:

```text
photos.photoable_type = Contact
photos.type = profile
```

This helps quickly identify contractors, service providers, and other people.

---

# Module boundary rule

A good rule of thumb:

```text
Maintenance decides what something means.
Integrations decide how it is shown elsewhere.
```

Examples:

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

---

# Recommended service boundaries

## `WorkItemDueStateService`

Calculates dynamic states:

```text
upcoming
due_today
overdue
needs_attention
completed_recently
```

No persisted `due` status is needed.

---

## `RecurrenceService`

Responsible for:

```text
Generating next work item
Calculating next due window
Respecting only_after_success
Handling rolling windows
Handling calendar-month windows
Handling hard deadlines when needed
```

---

## `CostForecastService`

Responsible for:

```text
Expected cost next 30 days
Expected cost next 3 months
Expected cost next 6 months
Expected cost next 12 months
Expected cost next calendar year
Grouping by inferred cost category
```

---

## `IcsFeedGenerator`

Responsible for:

```text
Building valid ICS output
Applying calendar feed filters
Generating events from hard deadlines
Generating events from due windows
Applying title and description templates
Adding emoji prefixes
```

It should call Maintenance services for due-state and urgency calculations instead of duplicating that logic.

---

# Implementation principle

Avoid placing integration-specific fields or decisions into the maintenance core unless they are truly part of the household maintenance domain.

Good:

```text
work_items.due_window_start
work_items.due_window_end
work_items.hard_due_at
work_items.estimated_cost
work_items.status
```

Not ideal:

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

---

# Why this separation is useful

It keeps the application simple now and flexible later.

Future integrations can be added without rewriting the maintenance core:

```text
Home Assistant notifications
Email reminders
Push notifications
Telegram bot
UniFi camera references
Immich album integration
Paperless-ngx API sync
```

The maintenance core remains focused on:

```text
what needs doing
what was done
what is due next
what needs attention
```
