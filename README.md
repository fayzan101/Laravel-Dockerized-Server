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

### Local development (bundled PostgreSQL)

```bash
cp .env.example .env
php artisan key:generate   # or generate after first container start
docker compose up --build
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

- **app** — API at [http://localhost:8000](http://localhost:8000)
- **queue** — `php artisan queue:work` for async data migrations
- **db** — PostgreSQL 15 (`pgdata` volume); overrides `DB_HOST` from `.env` to use the local container

### Production (Neon + Hetzner VPS)

Stack: **Neon** (PostgreSQL) + **Hetzner Cloud** (API + queue) + **Caddy** (HTTPS).

**1. Hetzner server** — Ubuntu 24.04, e.g. CX22 (2 vCPU / 4 GB). Add your SSH key. In **Firewalls**, allow inbound `22` (your IP), `80`, `443`.

**2. On the server**

```bash
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER
# log out and back in

mkdir -p ~/faizan
cd ~/faizan
git clone https://github.com/YOUR_USER/YOUR_REPO.git .
cp .env.production.example .env
nano .env   # Neon credentials, APP_URL=http://YOUR_HETZNER_IP:8000
```

Or from your PC: `scp .env.production root@YOUR_IP:~/faizan/.env`

**3. Start containers**

```bash
docker compose -f docker-compose.prod.yml up -d --build
docker compose -f docker-compose.prod.yml exec app php artisan key:generate
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
curl http://127.0.0.1:8000/api/health
```

**4. HTTPS (Caddy on host)** — point DNS `A` record to the server IP, then:

```bash
sudo apt install -y caddy
```

`/etc/caddy/Caddyfile`:

```caddy
api.yourdomain.com {
    reverse_proxy 127.0.0.1:8000
}
```

```bash
sudo systemctl reload caddy
```

**5. Scheduler** — included as the `scheduler` service (`php artisan schedule:work`). No host cron required for `schedule:run`.

**Redeploy:** `git pull && docker compose -f docker-compose.prod.yml up -d --build && docker compose -f docker-compose.prod.yml exec app php artisan migrate --force`

The API listens on `127.0.0.1:8000` only; Caddy handles public HTTPS. `EXPORTS_DISK=local` is fine on a single VPS.

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
