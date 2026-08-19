# EventFlow Requirements Traceability

## Source and scope

The sole product-requirements source is `Full-Stack Developer - Technical Assignment.pdf` (6 pages). This document separates explicit PDF requirements from implementation decisions needed to make them work. The assignment asks for a small event-registration application, not a general event-management platform.

Status values used during development:

- `NOT STARTED`: no implementation exists yet.
- `IN PROGRESS`: implementation exists but is incomplete or unverified.
- `DONE`: implementation is complete but has not passed its final check.
- `VERIFIED`: implementation and its relevant automated/manual checks pass.

Phase 0 contains planning only, so every product requirement remains `NOT STARTED`.

## Requirements traceability matrix

| ID | PDF requirement | Status | Planned Laravel file/location | Planned API endpoint | Database table/model | Planned React page/component | Planned automated test | Notes |
|---|---|---|---|---|---|---|---|---|
| EVT-01 | An event has name, description, date, capacity, and status. | VERIFIED | `app/Models/Event.php`; event requests; migration | Event endpoints below | `events` / `Event` | Event list and registration pages | Event API validation tests | No extra event attributes are planned. |
| EVT-02 | Create an event. | VERIFIED | `EventController@store`; `StoreEventRequest` | `POST /api/events` | `events` / `Event` | Not required in public registration UI | Create-event feature test | The PDF requires an API, not an event-admin UI. |
| EVT-03 | List events. | VERIFIED | `EventController@index` | `GET /api/events` | `events` / `Event` | `EventsPage` | List/availability feature test | Response will support the required event-selection flow. |
| EVT-04 | View an event. | VERIFIED | `EventController@show` | `GET /api/events/{event}` | `events` / `Event` | `RegistrationPage` | View-event feature test | Laravel route model binding is planned. |
| EVT-05 | Update an event. | VERIFIED | `EventController@update`; `UpdateEventRequest` | `PATCH /api/events/{event}` | `events` / `Event` | Not required in public registration UI | Update-event feature test | No delete endpoint because the PDF does not request one. |
| REG-01 | Users can view available events and select one. | VERIFIED | Event query/resource | `GET /api/events`; `GET /api/events/{event}` | `events`, `registrations` | `EventsPage`; `RegistrationPage` | Availability API test | “Available” is defined below as an implementation decision. |
| REG-02 | Users can submit first name, last name, email, and phone. | VERIFIED | `RegistrationController@store`; `StoreRegistrationRequest` | `POST /api/events/{event}/registrations` | `registrations` / `Registration` | `RegistrationForm` | Successful-registration test; validation-failure test | Backend validation is authoritative; basic client validation improves usability. |
| REG-03 | A successful registration/reference number is shown. | VERIFIED | Registration resource/reference generator | Same registration endpoint | Unique `registration_reference` | `RegistrationStatusPage` | Successful-registration test | The public reference will not expose the numeric primary key. |
| REG-04 | Backend validates registration data. | VERIFIED | `StoreRegistrationRequest` | Registration endpoint | Registration constraints | `RegistrationForm` field errors | Validation-failure test | Validation errors will use Laravel's standard 422 response. |
| REG-05 | An event cannot exceed its capacity. | VERIFIED | Transactional registration logic | Registration endpoint | `events`, `registrations` | Capacity error state | Event-capacity test | PostgreSQL transaction and row lock are planned to prevent concurrent overselling. |
| WH-01 | Provide `POST /api/webhooks/ticketing`. | VERIFIED | `TicketingWebhookController` | `POST /api/webhooks/ticketing` | `registrations`, `webhook_events` | No direct page | Webhook feature tests | Endpoint path will match the PDF exactly. |
| WH-02 | Accept the example ticket-confirmation fields: event, registration reference, ticket ID, and status. | VERIFIED | Webhook validation | Webhook endpoint | `registrations`, `webhook_events` | No direct page | Successful-webhook test | Only the supported `ticket.confirmed` / `confirmed` transition is planned. |
| WH-03 | A received webhook updates the registration status. | VERIFIED | Webhook controller/service transaction | Webhook endpoint | `registrations` / `Registration` | `RegistrationStatusPage` | Successful-webhook test | Ticket ID will also be retained to make the integration traceable. |
| WH-04 | Frontend demonstrates registration, backend creation, webhook receipt, confirmation, and updated status. | VERIFIED | Registration lookup plus demo ticketing action | Registration create/lookup; webhook; demo simulator route | All three tables | All three pages | Registration and webhook tests; manual end-to-end check | A small server-side simulator is an implementation aid for demonstrating the required external webhook. |
| SEC-01 | Use a basic trusted-source mechanism such as shared-secret/HMAC signing. | VERIFIED | Webhook middleware/controller verification; `config/services.php` | Webhook endpoint | None | No secret in React | Successful- and invalid-signature tests | Planned HMAC-SHA256 over the exact raw body using a server-only environment secret. |
| SEC-02 | Consider duplicate webhook delivery and explain the approach in README. | VERIFIED | Webhook processing logic | Webhook endpoint | `webhook_events` unique key | Status remains stable | Duplicate-webhook test | Duplicate valid deliveries will return success without repeating the state change. |
| DB-01 | Use a relational database. | VERIFIED | Laravel PostgreSQL configuration and migrations | All API endpoints | PostgreSQL | Indirect | Migration/test database checks | PostgreSQL is the selected relational database. |
| DB-02 | Create events, registrations, and webhook events/logs tables/models. | VERIFIED | Three migrations and models | Relevant endpoints | `events`, `registrations`, `webhook_events` | Indirect | Feature tests | Planned schema is below. |
| DB-03 | Use appropriate relationships, validation, indexes, and unique constraints. | VERIFIED | Models, Form Requests, migrations | Relevant endpoints | FK/index/unique constraints | Validation display | Feature tests | Constraints are detailed below and will be reviewed after migration creation. |
| AI-01 | Create `AI.md` explaining where AI was used, prompts/help requested, accepted code/suggestions, and changed/rejected suggestions with reasons. | VERIFIED | `AI.md` | N/A | N/A | N/A | Documentation review | It will be updated truthfully after each implementation phase. |
| TST-01 | Test successful registration. | VERIFIED | `tests/Feature/RegistrationTest.php` | Registration endpoint | Events/registrations | N/A | Mandatory test | Must assert stored data, pending state, and public reference. |
| TST-02 | Test validation failure. | VERIFIED | `tests/Feature/RegistrationTest.php` | Registration endpoint | Registrations | N/A | Mandatory test | Must assert 422 and no invalid row. |
| TST-03 | Test event capacity. | VERIFIED | `tests/Feature/RegistrationTest.php` | Registration endpoint | Events/registrations | N/A | Mandatory test | Must prove the capacity boundary; concurrency behavior will also be explained. |
| TST-04 | Test successful webhook. | VERIFIED | `tests/Feature/TicketingWebhookTest.php` | Webhook endpoint | Registrations/webhook events | N/A | Mandatory test | Must use a real calculated signature and assert confirmation. |
| TST-05 | Test invalid webhook/signature. | VERIFIED | `tests/Feature/TicketingWebhookTest.php` | Webhook endpoint | Registrations/webhook events | N/A | Mandatory test | Must assert rejection and no state change. |
| TST-06 | Test duplicate webhook. | VERIFIED | `tests/Feature/TicketingWebhookTest.php` | Webhook endpoint | Registrations/webhook events | N/A | Mandatory test | Must assert idempotent success and one meaningful processing record/change. |
| DEP-01 | Provide a working public URL accessible without local setup, throughout evaluation. | NOT STARTED | Production configuration | Public HTTPS application URL | Hosted PostgreSQL | Production React build | Remote smoke test | Actual provider/URL will be selected and verified in the deployment phase. |
| DEP-02 | Put the live/staging URL in README and submission message. | NOT STARTED | `README.md` | Public URL | N/A | N/A | Documentation review | Cannot be completed before deployment. |
| DEP-03 | Provide required restricted-function credentials, but never repository secrets; use HTTPS where possible. | NOT STARTED | README/environment configuration | Public URL | N/A | N/A | Security/repository audit | No authentication is currently planned because the PDF does not require restricted access. |
| SET-01 | README gives versions, clone/install steps, environment and database setup, migrations/seeds, start commands, tests, local URL, placeholders, credentials if needed, and troubleshooting. | DONE | `README.md`; `.env.example` | N/A | PostgreSQL setup | Vite/Laravel start instructions | Clean-clone rehearsal | Commands and exact versions will be recorded only after verification. |
| DOC-01 | README covers technologies, setup, environment, database, running, API docs, architecture/flow, decisions, limitations, live URL, and credentials if applicable. | VERIFIED | `README.md`; `docs/ARCHITECTURE.md` | API table in README | Schema summary | Frontend flow | Documentation review | Architecture planning begins in Phase 0; README is completed later. |
| SEC-03 | Do not commit keys, passwords, tokens, `.env`, or sensitive credentials. | VERIFIED | `.gitignore`; `.env.example`; repository audit | N/A | N/A | N/A | Git/status and secret review | Real secrets stay only in deployment/local environment configuration. |
| DEL-01 | Deliver complete source in a Git repository. | DONE | Entire repository | N/A | N/A | N/A | Clean-clone check | Git commits are not created automatically. |
| DEL-02 | Include migrations and seed/demo data. | VERIFIED | `database/migrations`; `database/seeders` | N/A | All tables | Demo events | Migration/seed check | Seed data will support the interview demo. |
| DEL-03 | Include automated tests and API documentation. | VERIFIED | `tests/Feature`; `README.md` | All documented endpoints | Relevant | N/A | Full test suite/docs review | Includes the six mandatory scenarios. |
| DEL-04 | Include `README.md`, `AI.md`, and an environment-variable example. | VERIFIED | Named root files | N/A | N/A | N/A | Documentation/repository review | Safe placeholders only in `.env.example`. |
| SCALE-01 | Answer the first three improvements for thousands of users and explain why. | VERIFIED | README technical discussion | N/A | N/A | N/A | Documentation review | The answer will reflect observed application bottlenecks, not speculative features. |
| DEMO-01 | Be ready to demonstrate the app, explain decisions, and make/explain a small change. | VERIFIED | Documentation and interview notes | Full flow | All | Full flow | Manual demo rehearsal | Code will favor standard, explainable Laravel and React patterns. |

## Minimal implementation decisions for PDF ambiguities

These are not additional product requirements.

1. **Stack:** Laravel/PHP + React/Vite + PostgreSQL because the PDF prefers Laravel with React or Vue and requires a relational database.
2. **Event availability:** an event is available when its status is `active`, its date has not passed, and confirmed plus pending registrations are below capacity. Pending registrations reserve seats because they are already successful registrations awaiting ticket confirmation.
3. **Statuses:** events use `active` and `inactive`; registrations use `pending` and `confirmed`. These are the smallest values needed for the required flow.
4. **Registration reference:** `EVT-` plus an uppercase random token, protected by a database unique constraint and regenerated on the rare collision.
5. **Capacity concurrency:** lock the selected event row inside `DB::transaction`, count seat-holding registrations, then insert. This prevents two requests from taking the final seat.
6. **Webhook signature:** `X-Webhook-Signature` carries a lowercase hexadecimal HMAC-SHA256 of the exact raw request body using `WEBHOOK_SECRET` from server configuration.
7. **Duplicate webhook identity:** a deterministic SHA-256 key from `event + registration_reference + ticket_id + status`, enforced by a unique constraint in `webhook_events`.
8. **Duplicate response:** an already-processed valid event receives a successful idempotent response and does not repeat the update.
9. **Frontend status refresh:** short-interval polling of a public-reference lookup endpoint, stopped after confirmation or when the page unmounts. This is simpler than adding WebSockets, which the PDF does not require.
10. **Demo webhook:** React requests a server-side demo action; Laravel constructs, signs, and sends the payload to the real required webhook endpoint. The secret never reaches the browser.
11. **Event-management access:** the PDF requires event APIs but no authentication. They remain unauthenticated for assignment scope, with that limitation documented.

## Planned database schema

### `events`

| Column | Purpose / integrity |
|---|---|
| `id` | Internal primary key. |
| `name` | Required event name. |
| `description` | Required text description. |
| `event_date` | Required timestamp used for display and availability. |
| `capacity` | Required positive integer, validated by Laravel and protected by a database check if supported cleanly by the final migration. |
| `status` | Required small string constrained by application validation to `active` or `inactive`; indexed with date for availability queries if query evidence justifies it. |
| timestamps | Creation/update audit timestamps. |

### `registrations`

| Column | Purpose / integrity |
|---|---|
| `id` | Internal primary key. |
| `event_id` | Required foreign key to `events.id`; indexed; restrictive deletion behavior to avoid accidental history loss. |
| `registration_reference` | Required unique public reference. |
| `first_name`, `last_name` | Required registrant names. |
| `email` | Required validated email; indexed only if an implemented query needs it. |
| `phone` | Required string, preserving punctuation and leading zeroes. |
| `status` | Required `pending` or `confirmed`. |
| `ticket_id` | Nullable until confirmation; unique when present to prevent one external ticket being attached twice. |
| timestamps | Creation/update audit timestamps. |

### `webhook_events`

| Column | Purpose / integrity |
|---|---|
| `id` | Internal primary key. |
| `event_key` | Required deterministic unique idempotency key. |
| `event_type` | Required external event name. |
| `registration_reference` | Required indexed lookup/audit value. |
| `ticket_id` | Required ticket identifier from the supported event. |
| `payload` | Required JSONB copy for diagnosis/audit; never contains the secret. |
| `processing_status` | Small value such as `processed` or `failed` to make outcomes explainable. |
| `processed_at` | Nullable timestamp set after successful processing. |
| timestamps | Receipt/update audit timestamps. |

## Phase gates

At the end of every later phase, update this matrix, run the relevant tests/build/formatter, document actual AI assistance in `AI.md`, explain the changed files and request flow, and stop for approval. No requirement becomes `VERIFIED` without evidence.
