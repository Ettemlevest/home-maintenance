# Home Maintenance

Home maintenance tracker for the family house: assets, recurring upkeep, and who-did-what history. Built with Laravel 13, FilamentPHP, and PostgreSQL 18.

The implementation plan and product vision live in [`prompts/`](./prompts/README.md); repository conventions in [`AGENTS.md`](./AGENTS.md).

## Requirements

- PHP 8.5+ (locally via [Laravel Herd](https://herd.laravel.com): `php85`)
- Composer, Node.js + npm
- Docker (only for PostgreSQL — the app itself runs natively)

## Getting started

```bash
composer install
composer run setup   # .env + app key, starts Postgres 18 in Docker, migrates, builds assets
composer run dev     # server + queue + logs + vite
```

## Quality toolchain

```bash
composer run test      # Pest (local default: sqlite in-memory; CI runs against Postgres 18)
composer run format    # Pint
composer run analyse   # PHPStan + Larastan, level 6
composer run refactor  # Rector
composer run check     # pint --test + phpstan + pest — same as CI
```

CI runs `check` on every push via GitHub Actions.
