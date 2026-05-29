# Deployment

This document is a placeholder for the operational deployment guide. It is **not required** to complete the MVP planning set; it is called out so the deployment story is not lost.

The intended deployment target is a single Docker container running on an Unraid OS home server.

## Deployment target

- One Docker image containing PHP-FPM, an HTTP server (nginx or Caddy), the Laravel app, and a Supervisor configuration for the queue worker.
- PostgreSQL runs as a separate container (the user is expected to operate one already on Unraid).
- Distribution as an Unraid Community Applications template so installation is a few clicks.
- Single `docker-compose.yml` for non-Unraid self-hosters.

## Queue worker and scheduler

The app dispatches recurrence generation and reminder work as queued jobs (see [`08-architecture-and-business-logic.md`](./08-architecture-and-business-logic.md) → "Background-job model").

- A cron entry inside the container runs `php artisan schedule:run` every minute. Laravel's scheduler then dispatches the daily jobs.
- One Supervisor-managed queue worker process handles dispatched jobs.
- Queue driver: Redis if a Redis container is present, otherwise the database driver.

## Photo storage

Photos and other uploaded media are persisted to a host-mounted volume so they survive container restarts and rebuilds.

- Container path: `/var/www/html/storage/app/` (Laravel's default storage path).
- Host mount: a dedicated Unraid share, e.g. `/mnt/user/appdata/home-maintenance/storage`.
- `spatie/laravel-medialibrary` writes originals and generated conversions / thumbnails under that path.

## Backup direction

A user-facing artisan export command is **not** part of MVP. The recommended backup approach for a self-hosted Unraid setup:

- **Database**: schedule `pg_dump` from the host or a sidecar container; place the dump under an Unraid share that is included in the user's existing backup routine.
- **Photos and other uploaded files**: snapshot the mounted storage volume (CA Backup / Appdata Backup plugin on Unraid handles this cleanly).
- **App configuration**: include `.env` and any `config/` overrides alongside the DB dump.

A user-facing `home:export` artisan command (a single zip with JSON of all tables plus the photos folder) can be added later if there is demand; it is not blocking MVP.
