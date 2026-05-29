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

### Topic tags

```text
safety
warranty
annual-service
cleaning
inspection
documentation
urgent-risk
energy-efficiency
child-safe
contractor-needed
```

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
