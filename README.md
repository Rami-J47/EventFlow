# EventFlow

EventFlow is the focused event-registration application requested by the Full-Stack Developer assignment. Visitors browse available events, register, receive a public reference, and see the registration move from `pending` to `confirmed` after a signed ticketing webhook.

## Live URL

[https://eventflow-production-hfo70t.laravel.cloud/](https://eventflow-production-hfo70t.laravel.cloud/)

No login or test credentials are required. The public registration flow and server-side webhook demonstration are available from the homepage.

## Verified technology versions

- PHP 8.4.24; Laravel 13.26.1; Composer 2.10.2
- Node.js 24.19.0; pnpm 11.19.0; Vite 8.2.1
- React 19.2.8; React Router 7.18.2
- PostgreSQL 18.1 client; PHPUnit 12.5

Laravel requires PHP 8.3+. PostgreSQL is the application database. Automated tests use isolated in-memory SQLite.

## Installation

```bash
git clone <repository-url> eventflow
cd eventflow
composer install
corepack enable
pnpm install --frozen-lockfile
cp .env.example .env
php artisan key:generate
```

On PowerShell, use `Copy-Item .env.example .env`. Create a PostgreSQL database named `eventflow`, then configure `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=eventflow
DB_USERNAME=postgres
DB_PASSWORD=your-local-password
DB_SCHEMA=public
WEBHOOK_SECRET=use-a-long-random-local-secret
```

Initialize and run:

```bash
php artisan migrate --seed
php artisan serve
pnpm dev
```

Keep the final two commands in separate terminals and open [http://localhost:8000](http://localhost:8000).

Quality commands:

```bash
php artisan test
vendor/bin/pint --test
pnpm run build
```

## Demonstration

1. Select an event and submit first name, last name, email, and phone.
2. The success page shows an `EVT-XXXXXXXX` reference with `pending` status.
3. Click **Demonstrate ticket confirmation**.
4. Laravel constructs the provider payload, signs the exact JSON body server-side, and dispatches it through the real webhook endpoint.
5. The webhook verifies HMAC, prevents duplicate processing, confirms the registration, and React shows `confirmed`.

The status page also polls every three seconds for an external confirmation. `WEBHOOK_SECRET` never reaches React.

## API

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/events` | List active future events with free capacity. |
| `POST` | `/api/events` | Create an event. |
| `GET` | `/api/events/{id}` | View an event. |
| `PATCH` | `/api/events/{id}` | Update an event. |
| `POST` | `/api/events/{id}/registrations` | Create a pending registration. |
| `GET` | `/api/registrations/{reference}` | Look up public registration status. |
| `POST` | `/api/webhooks/ticketing` | Receive the required signed webhook. |
| `POST` | `/api/registrations/{reference}/demo-confirmation` | Run the server-side demo sender. |

Event body:

```json
{"name":"Developer Conference","description":"A practical event.","event_date":"2026-10-20T18:00:00+03:00","capacity":50,"status":"active"}
```

Registration body:

```json
{"first_name":"Salma","last_name":"Haddad","email":"salma@example.com","phone":"+961 70 123 456"}
```

Registration success is HTTP 201. Validation is 422. Full or unavailable events return 409. Statuses are deliberately limited to event `active|inactive` and registration `pending|confirmed`.

Webhook body:

```json
{"event":"ticket.confirmed","registration_reference":"EVT-A7F4K2P9","ticket_id":"TCK-98765","status":"confirmed"}
```

`X-Webhook-Signature` is the lowercase hexadecimal result of `hash_hmac('sha256', exact_raw_body, WEBHOOK_SECRET)`. Laravel uses `hash_equals` and rejects missing/invalid signatures with 401 before validation or writes.

## Architecture and decisions

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for diagrams, [docs/REQUIREMENTS.md](docs/REQUIREMENTS.md) for PDF traceability, and [docs/INTERVIEW_PLAYBOOK.md](docs/INTERVIEW_PLAYBOOK.md) for the technical-discussion walkthrough.

`RegistrationService` opens `DB::transaction`, reloads the event with `lockForUpdate`, checks availability/capacity, and inserts. A plain count/insert has a race: two requests can see the last seat. PostgreSQL makes the second transaction wait; after the first commits, it recounts and receives 409. Pending registrations reserve seats because registration already succeeded.

Webhook stable fields form a SHA-256 `event_key`. Its database unique constraint is the concurrency-safe duplicate guard. The first valid delivery confirms the registration; identical deliveries return success with `duplicate: true` without a second state change.

Important code: `routes/api.php`, `app/Http/Controllers/Api`, `app/Http/Requests`, `app/Services`, `database/migrations`, `resources/js`, and `tests/Feature`.

## Environment and secrets

`APP_KEY` protects Laravel internals, `APP_URL` defines the origin, `DB_*` configures PostgreSQL, and `WEBHOOK_SECRET` authenticates ticket webhooks. `.env` is ignored; `.env.example` contains placeholders only.

## Deployment

Use one HTTPS Laravel service with a writable `storage` directory and managed PostgreSQL. Point the web root to `public/`; set secrets in the host. Verified project commands are:

```bash
composer install --no-dev --optimize-autoloader
corepack enable
pnpm install --frozen-lockfile
pnpm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Laravel Cloud hosts the application over HTTPS. The deployed assignment uses an isolated `eventflow` schema in a Supabase-hosted PostgreSQL database; database credentials remain server-only Laravel Cloud variables. After every deployment, run the migrations, seed demo events once when needed, and smoke-test the full flow in a private browser.

## Known limitations

- Event-management write APIs intentionally have no production authentication because it is outside the assignment scope; add authentication before exposing them in a real product.
- Pending reservations do not expire; React polls rather than using WebSockets.
- Only `ticket.confirmed` is supported.
- The demo sender is an interview aid, not a real provider account.
- The free Supabase project may pause after one week without activity; keep it active throughout the evaluation period.

Troubleshooting: enable `pdo_pgsql` if PHP reports a missing driver; check PostgreSQL and `.env` on connection refusal; clear config with `php artisan config:clear`; run `pnpm run build` if the Vite manifest is missing; and ensure both webhook sides sign identical raw bytes.

## First three improvements for thousands of users

1. Move provider work to a durable queue for fast acknowledgment and controlled retries.
2. Add structured observability, alerts, and endpoint rate limits.
3. Scale stateless Laravel workers and PostgreSQL connections/indexes based on measured query plans and lock contention.
