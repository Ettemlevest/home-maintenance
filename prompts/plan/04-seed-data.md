# Seed Data — "Hungarian family house" template

The opt-in template applied at home creation (Phase 16). It replaces the draft seed lists in `specs/06-seed-data.md`, which predate the model decisions in [`02-technical-decisions.md`](./02-technical-decisions.md). Beyond bootstrapping a home, this file is the **worked example of the data model**: every entry demonstrates the location/asset boundary rule, the four work item types, multi-asset schedule rows, and the doer/equipment fields.

Seeding principles:

- **Locations are floor-plan places; assets are serviceable units.** Draft entries that were really systems or structures ("Villamos rendszer", "SIP szerkezet" as locations) became assets placed in real locations.
- **Rules define a procedure once**; schedule rows attach them to one or more assets.
- **No fabricated contacts.** `preferred_contact_id` is left empty everywhere — households add their real plumber, not a placeholder.
- Names are Hungarian (the template targets `default_locale = hu`); the seeder reads from lang files so an English template is a translation, not a code change.

## Tags

### Seasons (`type = season`)

| Name | Slug | Sort |
|---|---|---|
| Tavasz | spring | 10 |
| Nyár | summer | 20 |
| Ősz | autumn | 30 |
| Tél | winter | 40 |

### Systems (`type = system`)

| Slug | Name | Sort |
|---|---|---|
| hvac | HVAC / hőszivattyú | 100 |
| plumbing | Vízrendszer | 110 |
| electrical | Villamos rendszer | 120 |
| low-voltage | Gyengeáram / hálózat | 130 |
| hot-water | HMV | 140 |
| sewage | Szennyvíz | 150 |
| rainwater | Csapadékvíz | 160 |
| building-envelope | Épületburok | 170 |
| roof | Tető | 180 |
| windows-doors | Nyílászárók | 190 |
| garden | Kert | 200 |
| security | Biztonságtechnika | 210 |

### Topics (`type = topic`)

| Slug | Name | Sort |
|---|---|---|
| safety | Biztonság | 300 |
| warranty | Garancia | 310 |
| annual-service | Éves szerviz | 320 |
| cleaning | Tisztítás | 330 |
| documentation | Dokumentáció | 340 |
| energy-efficiency | Energiahatékonyság | 350 |
| child-safe | Gyerekbiztonság | 360 |

Removed from the draft: `contractor-needed` (now the `doer` field), `inspection` (now a work item type), `urgent-risk` (priority + due state already express urgency), `sip-structure`/`sip` system tags (house-specific; belongs in a household's own tags, not a template).

### Cost categories (`type = cost_category`)

| Slug | Name | Sort |
|---|---|---|
| cost-hvac | Költség: HVAC | 500 |
| cost-plumbing | Költség: Víz/gépészet | 510 |
| cost-electrical | Költség: Villany | 520 |
| cost-garden | Költség: Kert | 530 |
| cost-roof | Költség: Tető | 540 |
| cost-appliance | Költség: Háztartási gép | 550 |
| cost-safety | Költség: Biztonság | 560 |
| cost-general-maintenance | Költség: Általános karbantartás | 570 |

## Locations

Floor-plan places only — you could point at each on a site plan:

```text
Telek
├─ Ház
│  ├─ Előszoba
│  ├─ Nappali
│  ├─ Konyha + étkező
│  ├─ Kamra
│  ├─ Fürdő
│  ├─ WC
│  ├─ Háztartási helyiség
│  ├─ Dolgozó
│  ├─ Szoba
│  ├─ Közlekedő
│  ├─ Gépészeti helyiség      (type: technical_area)
│  ├─ Padlástér
│  ├─ Tető                     (type: roof)
│  └─ Homlokzat                (type: facade)
├─ Kert
│  ├─ Előkert
│  ├─ Hátsókert
│  ├─ Terasz
│  └─ Bejáró / gépkocsi beálló
├─ Garázs
└─ Kerti tároló
```

Gone from the draft tree (they were systems or structures, not places): "Villamos rendszer", "Gyengeáram / hálózat", "Eresz / csatorna", "Lábazat", "Nyílászárók", the whole "SIP szerkezet" subtree. Their serviceable parts reappear below as assets.

## Assets

Serviceable units — each gets installed, degrades, and could be replaced:

| Asset | Type | Location |
|---|---|---|
| Tetőhéjazat | building_part | Tető |
| Ereszcsatorna rendszer | building_part | Tető |
| Homlokzati hőszigetelés és vakolat | building_part | Homlokzat |
| Lábazat | building_part | Homlokzat |
| Nyílászárók | building_part | Ház |
| Hőszivattyú kültéri egység | appliance | Hátsókert |
| Hőszivattyú beltéri egység | appliance | Gépészeti helyiség |
| HMV tároló | appliance | Gépészeti helyiség |
| Vízszűrő / nyomáscsökkentő | fixture | Gépészeti helyiség |
| Elektromos főelosztó | system | Háztartási helyiség |
| FI relé / ÁVK | device | Háztartási helyiség |
| Túlfeszültség-védelem | device | Háztartási helyiség |
| Router / hálózati központ | device | Dolgozó |
| Kerti csap — elülső | fixture | Előkert |
| Kerti csap — hátsó | fixture | Hátsókert |
| Füstérzékelő — nappali | device | Nappali |
| Füstérzékelő — közlekedő | device | Közlekedő |
| Füstérzékelő — szoba | device | Szoba |
| Terasz burkolat | outdoor_equipment | Terasz |
| Kerítés és kapu | structure | Kert |

Model notes the table demonstrates:

- **Overlap rule**: location "Tető" holds assets "Tetőhéjazat" and "Ereszcsatorna rendszer" — the place vs the serviceable units on it. Asset names are things, never activities ("Tetőhéjazat", not "Tetőfedés"): the covering material itself (cserép, pala, lemez) goes in the asset's `manufacturer`/`model`/`notes` fields.
- **Multi-unit assets**: three smoke detectors are individual assets (each has its own QR slug, condition, and schedule state). More units of the same kind are added with "Duplicate N times", which attaches clones to the same rules.
- **Two garden taps** instead of one collective "Kerti csapok": winterization is completed per tap.

## Recurring rules

One row per *procedure*; the "Attached to" column becomes `recurring_rule_schedules` rows:

| Rule | Type | Schedule | Window | Doer | Equip. | Attached to | Tags |
|---|---|---|---|---|---|---|---|
| FI relé teszt | inspection | monthly, from completed_at | rolling 7d | diy | – | FI relé / ÁVK | safety, electrical |
| Túlfeszültség-védelem ellenőrzése | inspection | 6 months, from completed_at | rolling 14d | diy | – | Túlfeszültség-védelem | safety, electrical |
| Füstérzékelő teszt | inspection | 3 months, from completed_at | rolling 14d | diy | – | all 3 smoke detectors | safety, child-safe |
| Füstérzékelő elemcsere | replacement | yearly, fixed anchor | calendar month (June) | diy | – | all 3 smoke detectors | safety |
| Hőszivattyú kültéri egység szemle | inspection | 3 months, from completed_at | rolling 14d | diy | – | Hőszivattyú kültéri egység | hvac |
| Hőszivattyú éves szerviz | task | yearly, from previous_due_at | rolling 30d | contractor | – | both heat pump units | hvac, annual-service |
| HMV biztonsági szelep ellenőrzése | inspection | 6 months, from completed_at | rolling 14d | diy | – | HMV tároló | hot-water, plumbing |
| Vízszűrő betét csere | replacement | 3 months, from completed_at | rolling 14d | diy | – | Vízszűrő / nyomáscsökkentő | plumbing |
| Kerti csapok téliesítése | task | yearly, fixed anchor | calendar season (autumn) | diy | – | both garden taps | garden, plumbing, winter |
| Ereszcsatorna tisztítás | task | 6 months, fixed anchor | calendar seasons (spring + autumn)¹ | diy | **yes** (létra) | Ereszcsatorna rendszer | roof, rainwater, cleaning |
| Tető szemrevételezés | inspection | yearly, fixed anchor | calendar season (spring) | diy | **yes** (létra) | Tetőhéjazat | roof |
| Padlástér szemrevételezés | inspection | 6 months, from completed_at | rolling 30d | diy | – | *(none — location Padlástér)* | building-envelope |
| Nyílászárók átvizsgálása, vasalat kenés | task | yearly, fixed anchor | calendar season (autumn) | diy | – | Nyílászárók | windows-doors |

¹ Modeled as one rule, `interval_count = 6 months` from a spring anchor — occurrences alternate spring/autumn.

Model notes the table demonstrates:

- **Multi-asset rules (S12)**: "Füstérzékelő teszt" is one rule with three schedule rows; each detector generates and advances independently. Same for the garden taps and heat pump units.
- **Asset-less rules**: "Padlástér szemrevételezés" targets a location only — a single schedule row with `asset_id = null`.
- **The four types in action**: inspections dominate (pass/fail chores), replacements for consumables, tasks for everything else. No rule needed `repair` — repairs are born from failed inspections via the follow-up flow, which is the point.
- **Equipment batching**: both ladder-dependent rules carry `needs_special_equipment`, so their work items can be filtered into the same weekend.
- **Doer**: only the annual heat pump service defaults to `contractor`; the template assumes a hands-on household, and flipping a rule to `contractor` is one field.

## What the seeder does *not* create

- Work items — the daily scheduler materializes them from the schedule rows immediately after seeding, so the dashboard fills itself.
- Contacts, expenses, photos, links — real-world data with no sensible defaults.
- House-specific assets (SIP structure elements, EPH, napelem előkészítés from the draft) — a template must fit a generic family house; owners add specifics. The asset form takes seconds per entry, and `04`'s structure shows the pattern to follow.
