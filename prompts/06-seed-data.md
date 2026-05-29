# Seed Data

## Purpose

The app should be useful immediately after creating a home. Seed data should create basic tags, initial locations, known or expected major assets, and starter recurring rules.

## Seed tags

### Seasons

| Name | Slug | Type | Sort order |
|---|---|---|---|
| Tavasz | spring | season | 10 |
| Nyár | summer | season | 20 |
| Ősz | autumn | season | 30 |
| Tél | winter | season | 40 |

### System tags

| Slug | Hungarian display name | Type | Sort order |
|---|---|---|---|
| hvac | HVAC / hőszivattyú | system | 100 |
| plumbing | Vízrendszer | system | 110 |
| electrical | Villamos rendszer | system | 120 |
| low-voltage | Gyengeáram | system | 130 |
| hot-water | HMV | system | 140 |
| sewage | Szennyvíz | system | 150 |
| rainwater | Csapadékvíz | system | 160 |
| building-envelope | Épületburok | system | 170 |
| roof | Tető | system | 180 |
| windows-doors | Nyílászárók | system | 190 |
| sip-structure | SIP szerkezet | system | 200 |
| garden | Kert | system | 210 |
| exterior | Kültér | system | 220 |
| interior | Beltér | system | 230 |
| security | Biztonságtechnika | system | 240 |
| internet-network | Internet / hálózat | system | 250 |

### Topic tags

| Slug | Hungarian display name | Type | Sort order |
|---|---|---|---|
| safety | Biztonság | topic | 300 |
| warranty | Garancia | topic | 310 |
| annual-service | Éves szerviz | topic | 320 |
| cleaning | Tisztítás | topic | 330 |
| inspection | Ellenőrzés | topic | 340 |
| documentation | Dokumentáció | topic | 350 |
| urgent-risk | Sürgős kockázat | topic | 360 |
| energy-efficiency | Energiahatékonyság | topic | 370 |
| child-safe | Gyerekbiztonság | topic | 380 |
| contractor-needed | Szakember szükséges | topic | 390 |

### Cost category tags

| Slug | Hungarian display name | Type | Sort order |
|---|---|---|---|
| cost-hvac | Költség: HVAC | cost_category | 500 |
| cost-plumbing | Költség: Víz/gépészet | cost_category | 510 |
| cost-electrical | Költség: Villany | cost_category | 520 |
| cost-garden | Költség: Kert | cost_category | 530 |
| cost-roof | Költség: Tető | cost_category | 540 |
| cost-exterior | Költség: Kültér | cost_category | 550 |
| cost-appliance | Költség: Háztartási gép | cost_category | 560 |
| cost-safety | Költség: Biztonság | cost_category | 570 |
| cost-general-maintenance | Költség: Általános karbantartás | cost_category | 580 |

## Seed locations

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

## Seed assets

```text
SIP külső falszerkezet
Belső előtétfalak
Födém / padlásfödém
Homlokzati hőszigetelés és vakolat
Lábazat
Nyílászárók
Tetőfedés
Szeglemezes tetőszerkezet
Ereszcsatorna rendszer
Csapadékvíz-elvezetés
Szikkasztó / esővízkezelés
Hőszivattyú kültéri egység
Hőszivattyú beltéri egység
HMV tároló
Osztó-gyűjtő
Elektromos törölközőszárító
Vízbekötés
Vízszűrő / nyomáscsökkentő
Kerti csapok
Szennyvíz bekötés
Belső szennyvízhálózat
Villamos mérőhely
H-tarifa mérőhely
Elektromos főelosztó
FI relé / ÁVK
Túlfeszültség-védelem
EPH rendszer
Napelem előkészítés
Router / hálózati központ
Riasztóközpont előkészítés
Kamerarendszer előkészítés
Terasz
Kerítés
Kapu
Bejáró / gépkocsi beálló
Kerti tároló
```

## Seed recurring rules

```text
FI relé / ÁVK teszt: monthly, rolling window 7 days, safety/electrical
Túlfeszültség-védelem ellenőrzése: every 6 months, rolling window 14 days
Hőszivattyú kültéri egység szemrevételezése: every 3 months, rolling window 14 days
Hőszivattyú éves szerviz: yearly, rolling window 30 days
HMV biztonsági szelep ellenőrzése: every 6 months, rolling window 14 days
Vízszűrő ellenőrzése / csere szükség szerint: every 3 months, rolling window 14 days
Kerti csapok téliesítése: yearly, autumn season
Ereszcsatorna tisztítás és ellenőrzés: every 6 months, rolling window 30 days
Tető szemrevételezés: yearly, rolling window 30 days
Padlástér és födém szemrevételezés: every 6 months, rolling window 30 days
Nyílászáró csatlakozások ellenőrzése: yearly, rolling window 30 days
```

## Multi-unit assets (smoke detectors, RCDs, identical fixtures)

A household typically has several identical or near-identical units of the same asset type — for example 9 smoke detectors from 3 different manufacturers, each requiring different maintenance intervals.

The recommended workflow does not bake these into seed data directly. Instead:

1. Seed creates **one** prototype asset per manufacturer (e.g. `Füstérzékelő — gyártó A`, `Füstérzékelő — gyártó B`, `Füstérzékelő — gyártó C`).
2. Each prototype has its manufacturer-specific recurring rules attached (battery replacement, test interval, replace-after-years).
3. The user runs the **"Duplicate N times"** Filament admin action on each prototype to expand it into the actual count of units installed (e.g. duplicate `Füstérzékelő — gyártó A` 4 times → 4 numbered instances). The action clones the source asset's recurring rules so each new asset has its own schedule and can be tested/serviced independently.

This keeps the seed data lean while supporting the real-world case of many identical units without manual repetition.
