# Integrations: Calendar, Paperless-ngx, External Links

## Integration philosophy

Integrations should consume maintenance data but should not own maintenance logic.

Maintenance decides what is due, overdue, what recurrence generates next, and what needs attention.

Integrations decide how external systems display or reference that data.

## Calendar integration

Use read-only ICS calendar feeds. Do not implement CalDAV write sync for the MVP.

Feed configuration lives in the `calendar_feeds` table (see [`02-database-schema.md`](./02-database-schema.md) for column definitions). Each home gets one feed by default:

```text
one ICS feed per home
```

Per-user feeds are supported via `calendar_feeds.user_id` but are optional.

### Feed URL rotation

Each feed has a unique random `token` that appears in its URL. If a token leaks (shared accidentally, posted online), the home owner can rotate it from a Filament action on the `CalendarFeed` record — the action generates a new token, invalidates the old URL immediately, and the owner re-subscribes with the new URL in their calendar client.

Avoid default-per-category calendars. Use emojis and title prefixes instead.

Examples:

```text
🧹 Ereszcsatorna tisztítás
🧪 FI relé teszt
📋 Tető ellenőrzés
🔧 Csaptelep javítás
🌿 Kerti csap téliesítés
⚠️ Hőszivattyú éves szerviz
🔴 Garanciális határidő
```

### Event generation

Hard due date:

```text
single event on hard_due_at
```

Due window default events:

```text
window start
85% through window
window end
overdue
```

### Event description placeholders

```text
title
type
status
priority
asset
location
due window
hard deadline
estimated cost
materials
tools
safety notes
paperless links
external links
application URL
```

## Paperless-ngx integration

Documents live in Paperless-ngx. This app stores only references.

Examples:

```text
user guide
warranty document
invoice
receipt
certificate
service report
quote
contract
declaration of performance
compliance document
handover document
```

## External links

External links point to useful resources outside this app and outside Paperless-ngx.

Examples:

```text
Immich photo album
iCloud album
Google Drive folder
manufacturer page
product page
support page
public user guide
installation video
forum thread
map
webshop
```

Each external link should have title, URL, type, optional provider, optional comment, optional description, and sort order. The comment should explain why the link is useful.

## Photos

Photos are stored directly in this application because photo timelines are part of maintenance history.

Use cases:

```text
asset condition timeline
before/after repair
construction progress
defect photos
contact profile photos
```

## Future integrations (out of MVP scope)

The following integrations are intentionally deferred. The data model permits them later without rework — they are listed here so they aren't forgotten:

- **Home Assistant**: this app could expose REST endpoints / webhooks so HA can show due maintenance, trigger automations when a task becomes overdue, or push completion events back. No design work in MVP.
- **Paperless-ngx webhook receiver**: Paperless-ngx can fire a webhook when a new document is filed. A receiver in this app could match the document against assets/work items and auto-suggest a `paperless_link` row. Useful, but manual link creation works for MVP.
- **Push notification channels**: ntfy, email, in-app — `recurring_rules.reminder_days_before` and `auto_create_days_before_due` already exist in the schema, but no delivery channel is committed to in the planning docs. ICS feed is the only notification surface in MVP.
