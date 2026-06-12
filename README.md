# Multi Tenant SaaS API

REST API backend for a multi-tenant SaaS platform built with Laravel 12, Sanctum, and PostgreSQL.

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

API documentation: [http://127.0.0.1:8000/api/documentation](http://127.0.0.1:8000/api/documentation)

## Docker

```bash
docker-compose up --build
```

- **app** — API at [http://localhost:8000](http://localhost:8000)
- **queue** — `php artisan queue:work` for async data migrations
- **db** — PostgreSQL 15 (`pgdata` volume)

Set `EXPORTS_DISK=s3` and AWS credentials in `.env` for cloud export storage in production.

## Seed accounts

| Email | Password | Role |
|-------|----------|------|
| `superadmin@platform.local` | `password` | Super-admin |
| `admin@acme.local` | `password` | Tenant admin (Acme Corp) |
| `member@acme.local` | `password` | Tenant member |

## Modules

- **Auth** — register, login, tokens, password reset
- **Tenant** — settings, members, ownership transfer, soft delete
- **IAM** — system + custom roles/permissions, middleware enforcement
- **Admin** — tenant CRUD, dashboard, platform settings, impersonation
- **Features** — catalog management, tenant overrides, usage limits
- **Usage** — reporting with feature limit enforcement
- **Integrations** — webhook CRUD and connectivity test
- **Data** — export/import, queued migrations, cross-tenant migration
- **Audit & Compliance** — logs, GDPR, bulk export, retention archive
- **Observability** — health, status, metrics

## Testing

Copy `phpunit.xml.dist` to `phpunit.xml` for local overrides (gitignored). DB credentials come from your `.env` file.

```bash
composer test
```

Tests use `DatabaseTransactions` against the configured PostgreSQL database.

## Queue & scheduled tasks

Migrations run asynchronously via the database queue:

```bash
php artisan queue:work
```

Audit log retention (default 90 days):

```bash
php artisan audit:archive
```

Schedule in production via cron: `* * * * * php artisan schedule:run`

## Environment

Key variables in `.env`:

```
DB_CONNECTION=pgsql
QUEUE_CONNECTION=database
EXPORTS_DISK=local
AUDIT_RETENTION_DAYS=90
```

## API auth

Send `Authorization: Bearer {token}` on protected routes. Tokens are issued via `/api/auth/login` or `/api/auth/register`.
