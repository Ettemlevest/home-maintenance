# Home Maintenance App: Initial Plan

High level goals:
- application should help track home maintenance work and house chores
- optimize for timeline/history, upcoming due work, and simple ownership/location context — not inventory accounting
- budget forecasting (OPEX), szezonális feladatok, garanciák, kivitelező kapcsolatok és checklist sablonok
- most valuable thin is answering:
  - What should I do now?
  - What should have already been done?
  - What was completed?
  - What needs attention?

## Context

Building a self-hosted home maintenance web app for a family house. The goal is to replace scattered notes/spreadsheets with a structured system for managing home assets, tracking maintenance history, and staying on top of recurring tasks. Multi-user (family members), mobile-first (used during actual maintenance work), i18n from day one. Tech stack: PHP/Laravel + FilamentPHP + PostgreSQL, shipped as a Docker container for Unraid.

---

## App Evaluation: What's Out There

| App | Tech | Self-hosted | Multi-user | Recurring tasks | History | Mobile | i18n | Notes |
|---|---|---|---|---|---|---|---|---|
| **HomeLogger** | Next.js + Go + SQLite | Yes | No | No | Yes | Yes (web) | ? | File attachments, REST API. Active (v0.4, May 2026). Missing scheduling. |
| **HomeBox** | Go + Vue + SQLite | Yes | No | No | Yes | Yes (web) | ? | 6.2k stars. Best for inventory; weak on scheduling. |
| **DumbAssets** | Node.js | Yes | ? | Yes | Yes | ? | ? | 1.1k stars. Easier UI than HomeBox, recurring maintenance. |
| **Homekeep** | Next.js + PocketBase | Yes | Yes | Yes | ? | PWA | ? | Calm/family-focused. Docker, ntfy notifications. Active May 2026. |
| **HA Maintenance Tracker** | Python + JS | Yes (HA required) | Yes (via HA) | Yes | Yes | HA app | ? | Requires Home Assistant ecosystem. Smart visibility filtering. |
| **Household Chores** | Python + MQTT | Yes | Yes | Yes (daily) | Scores | HA app | ? | Gamified chores, not home systems maintenance. |
| **Mainty** | PHP + SQLite | Yes | ? | ? | Yes | Responsive | ? | Vehicle-focused. Simple. Docker. v1.2 Dec 2025. |
| **Atlas CMMS (Grashjs)** | Java + React + RN | Yes | Yes (RBAC) | Yes | Yes | iOS+Android | 15+ langs | Enterprise CMMS. Overkill for home, but most feature-complete open source option. |
| **Homer** | Proprietary | No | Yes | Yes | Yes | iOS+Android | 50+ langs | Cloud-only. AI assistant. Award-winning UX. Good reference for UX patterns. |
| **HomeChart** | Proprietary | Yes | Yes | Yes | ? | iOS+Android | ? | Family mission control. Budget + meals + tasks. Good reference. |
| **openMAINT** | Java + PostgreSQL | Yes | Yes | Yes | Yes | iOS+Android | Yes | Enterprise facility management. Feature reference, not a model to follow for home use. |

**Key gap in the market**: Nothing combines all three pillars (asset registry + maintenance history + scheduled recurring tasks) in a lightweight, mobile-friendly, multi-user, self-hosted package with good i18n. That's the opportunity.

**Best UX references**: Homer (smart task UX), HA Maintenance Tracker (visibility filtering), Homekeep (calm family-focused design).

---

## Feature Set

The three core pillars are equally important — none alone is sufficient:

### 1. Asset Registry
- Locations (rooms, outdoor areas): kitchen, bathroom, basement, garden, roof, garage, etc.
- Assets per location: boiler, HVAC, washing machine, gutters, smoke detectors, etc.
- Asset details: manufacturer, model, serial number, purchase date, warranty expiry
- Documents attached to assets: manuals, warranties, purchase receipts, photos

### 2. Maintenance History
- Log entries per asset: date, description, performed by (user), cost, time spent
- Attachments on log entries: receipts, photos of work done
- Notes/comments field
- Filter/search history by asset, location, date range, user, cost

### 3. Scheduled & Recurring Tasks
- Maintenance schedules per asset: "replace HVAC filter every 3 months", "inspect gutters every April"
- Interval types: every N days/weeks/months/years, or fixed calendar date (e.g., first Monday of October)
- "Next due" auto-calculated from last completion + interval
- Status: upcoming / due soon / overdue
- Completing a scheduled task creates a maintenance history record and resets the schedule

### Supporting Features
- **Multi-user**: family accounts, activity visible to all (who did what, when)
- **Dashboard**: overdue tasks, due this week/month, recent activity
- **Notifications**: in-app + optional email when tasks become due/overdue
- **Mobile-first**: all actions doable on phone (log maintenance, check upcoming, attach photo)
- **i18n**: all UI strings externalized from day one, at least English + Hungarian to start
- **Docker**: single `docker-compose.yml` for Unraid deployment

---

## Data Model

```
Location
  id, name, description, icon, translations (json), timestamps

Asset
  id, location_id, name, description, brand, model, serial_number
  purchase_date, warranty_expires_at, notes
  timestamps

AssetDocument  (media: manuals, warranties, receipts, photos)
  id, asset_id, type (enum: manual|warranty|receipt|photo|other)
  file_path, filename, notes, timestamps

MaintenanceSchedule
  id, asset_id, title, description
  interval_value (int), interval_unit (enum: day|week|month|year)
  last_completed_at (nullable), next_due_at (computed/stored)
  is_active (bool), reminder_days_before (int, default 7)
  translations (json for title/description), timestamps

MaintenanceRecord  (log of completed work)
  id, asset_id, maintenance_schedule_id (nullable, if triggered from a schedule)
  performed_by (user_id), performed_at, title, description
  cost (decimal), duration_minutes (nullable)
  timestamps

MaintenanceRecordAttachment
  id, maintenance_record_id, file_path, filename, type, timestamps

User  (Laravel default + Filament Shield roles)
  id, name, email, password, locale (for i18n), timestamps
```

---

## FilamentPHP Structure

**Resources** (one per major model):
- `LocationResource` — list/create/edit/delete locations
- `AssetResource` — list/create/edit with related schedules and records inline (RelationManagers)
- `MaintenanceScheduleResource` — manage all schedules, filter by asset/location/status
- `MaintenanceRecordResource` — full history log, global view across all assets

**Widgets** (dashboard):
- `OverdueTasksWidget` — tasks past their due date (red)
- `UpcomingTasksWidget` — tasks due in next 14 days
- `RecentActivityWidget` — last 10 maintenance records
- `StatsOverviewWidget` — total assets, overdue count, tasks this month, total spend YTD

**Custom Pages**:
- Dashboard (widget grid)
- Timeline view (optional phase 2): chronological view of all maintenance

**Filament Plugins to use**:
- `filament/spatie-laravel-media-library-plugin` — file attachments
- `bezhansalleh/filament-shield` — role-based access control (admin vs family member)
- `filament/notifications` — in-app notifications
- `solution-forest/filament-translation-manager` — manage i18n strings in UI

---

## Implementation Phases

### Phase 1: Foundation
- `laravel new home-maintenance --pest` (Pest for testing)
- Install FilamentPHP v3, PostgreSQL driver, Spatie Media Library, Shield
- Set up i18n: `resources/lang/en/` and `hu/` from the start; all UI strings via `__()` helper
- Docker: `Dockerfile` + `docker-compose.yml` with PostgreSQL, Redis (queues), app
- CI: basic GitHub Actions running `pest` and `pint`

### Phase 2: Asset Registry
- Migrations and models: `Location`, `Asset`, `AssetDocument`
- Filament resources: `LocationResource`, `AssetResource` with document upload
- Seed file with common location names and asset types

### Phase 3: Maintenance History
- Migrations and models: `MaintenanceRecord`, `MaintenanceRecordAttachment`
- `MaintenanceRecordResource` with full CRUD, file upload, cost field
- `AssetResource` gets `MaintenanceRecordsRelationManager` (inline records tab)

### Phase 4: Scheduling
- Migration and model: `MaintenanceSchedule`
- `MaintenanceScheduleResource`
- `next_due_at` calculation logic (service class `MaintenanceScheduler`)
- Artisan command + scheduled job: `schedule:check-due` runs daily, updates statuses
- "Complete task" action on schedule → creates `MaintenanceRecord`, updates `last_completed_at`

### Phase 5: Dashboard & Notifications
- Dashboard widgets (overdue, upcoming, stats)
- Filament in-app notifications when task becomes overdue
- Optional email notification via Laravel Mail + queued jobs

### Phase 6: Docker & Unraid Deployment
- Finalize `Dockerfile` (PHP 8.4-fpm, nginx, Supervisor for queue worker)
- `docker-compose.yml`: app, postgres, redis
- Unraid Community Applications template format
- `.env.example` with all required vars documented

---

## Verification

1. `php artisan test` — all Pest tests pass (model factories + schedule calculation unit tests)
2. `docker compose up` — app starts, migrations run, Filament admin panel accessible at `/admin`
3. Manual: create a Location → add an Asset → attach a manual PDF → add a MaintenanceSchedule → complete it → verify a MaintenanceRecord was created and `next_due_at` updated
4. Set `next_due_at` to yesterday → dashboard shows task as overdue
5. Switch locale to Hungarian → all UI strings translated
6. Mobile: open `/admin` on iPhone, verify all core actions (log maintenance, attach photo) are usable without zoom