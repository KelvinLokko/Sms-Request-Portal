# SMS Client Portal

A Laravel + Vue (Inertia) portal for businesses to **submit SMS campaign requests**, upload recipient lists, receive invoices, pay via Mobile Money, and track fulfilment.

The portal does **not** send SMS itself. Operations staff review campaigns, issue invoices, verify payments, and fulfil requests manually.

## Stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 13, PHP 8.3+ |
| Frontend | Vue 3, Inertia.js, Tailwind CSS |
| Auth | Laravel Fortify |
| Roles / permissions | Spatie Laravel Permission |
| Database | PostgreSQL 16+ |
| Cache / queues | Redis + Laravel Horizon |
| PDFs | DomPDF |
| Web server (containers) | Nginx + PHP-FPM |

## Features

**Client companies**
- Register and manage company profile
- Request / manage Sender IDs
- Create SMS campaigns (bulk or personalised bulk)
- Upload recipient files (CSV / XLSX)
- View invoices and submit MoMo payment proofs
- Track campaign status through review → invoice → paid → fulfilled

**Platform staff**
- Analytics dashboard (live SQL aggregates)
- Company approval workflow
- Sender ID and campaign review
- Invoice issuing, payment verification, payment history
- Fulfilment tools
- User management and role/permission management
- Audit log

## Requirements (local)

- PHP 8.3+ with extensions: `pdo_pgsql`, `pgsql`, `redis`, `gd`, `bcmath`, `intl`, `pcntl`, `zip`
- Composer 2
- Node.js 22+ and npm
- PostgreSQL 16+
- Redis 7+

## Quick start (local)

```bash
git clone https://github.com/KelvinLokko/Sms-Request-Portal.git
cd Sms-Request-Portal

cp .env.example .env
composer install
php artisan key:generate

# Point .env at your local Postgres, then:
createdb sms_client_portal   # or create via your Postgres UI
php artisan migrate
php artisan db:seed

npm install
npm run build                # or: npm run dev
php artisan serve
```

In another terminal, run queue workers (required for recipient validation, PDFs, notifications):

```bash
php artisan horizon
# or:
php artisan queue:work redis --queue=validation,notifications,pdf,default
```

App URL: `http://localhost:8000` (or your `APP_URL`).

### Important `.env` values

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sms_client_portal
DB_USERNAME=sms
DB_PASSWORD=secret
DB_SSLMODE=prefer

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
```

Do not leave `DB_SSLMODE` empty — Postgres connections will fail.

## Demo accounts

After `php artisan db:seed`:

| Role | Email | Password |
| --- | --- | --- |
| Platform admin | `admin@example.com` | `password` |
| Company owner | `owner@example.com` | `password` |

Both accounts are email-verified by the seeder.

## Docker (Nginx + Postgres + Redis)

The project ships with a production-oriented Compose stack:

- `nginx` — HTTP
- `app` — PHP-FPM
- `postgres` — PostgreSQL 16
- `redis` — cache / queues
- `horizon` — queue workers
- `scheduler` — Laravel scheduler

```bash
cp .env.example .env
php artisan key:generate   # or set APP_KEY in .env first

docker compose up -d --build
docker compose exec app php artisan db:seed --force
```

- App: `http://localhost` (override with `APP_PORT`)
- Compose overrides `DB_HOST=postgres` and `REDIS_HOST=redis` inside containers
- Migrations run automatically on app start when `RUN_MIGRATIONS=true`

Useful commands:

```bash
docker compose logs -f app nginx horizon
docker compose exec app php artisan migrate --force
docker compose down
```

## Roles and permissions

Platform access is controlled by Spatie permissions (not hardcoded role names alone).

Built-in system roles:

- `super-admin` / `admin` — full platform access
- `finance` — payments, rates, tax, invoicing
- `support` — companies (view), sender IDs, campaign review, fulfilment

Admins can create custom roles and assign permissions under **Administration → Roles**.

## Development scripts

```bash
composer test              # Pint + Pest (see composer.json)
vendor/bin/pest            # tests only (sqlite in-memory via phpunit.xml)
vendor/bin/pint            # code style
vendor/bin/phpstan analyse # static analysis
npm run lint:check
npm run types:check
npm run build
```

## Project layout (high level)

```text
app/                  Domain logic, policies, jobs, services
database/migrations/  Schema
database/seeders/     Roles, permissions, demo data
resources/js/         Vue + Inertia pages/components
routes/web.php        App + admin routes
docker/               Nginx, PHP, entrypoint
Dockerfile            Multi-stage app + nginx images
docker-compose.yml    Full local/prod-like stack
```

## Deployment notes

Before going live:

1. Use **PostgreSQL** (MySQL is no longer the project default)
2. Set a strong `APP_KEY`, `APP_DEBUG=false`, and production `APP_URL`
3. Run `php artisan migrate --force` (seed only if you want demo data)
4. Keep Redis + Horizon running for queues
5. Build frontend assets (`npm run build`) or deploy via Docker images
6. Protect Horizon (`/horizon`) — only staff with `horizon.view` should access it

## License

Proprietary / project use unless otherwise stated by the repository owner.
