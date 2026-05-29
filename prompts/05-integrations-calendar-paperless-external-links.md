# Integrations: Calendar, Paperless-ngx, External Links

## Integration philosophy

Integrations should consume maintenance data but should not own maintenance logic.

Maintenance decides what is due, overdue, what recurrence generates next, and what needs attention.

Integrations decide how external systems display or reference that data.

## Calendar integration

Use read-only ICS calendar feeds. Do not implement CalDAV write sync for the MVP.

Default:

```text
one ICS feed per home
```

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
