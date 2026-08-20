# EventFlow — One-Day Interview Study Plan

This is a speaking-and-coding plan, not a reading checklist. For every block: read the named files, close them, explain the flow aloud, then reopen them and correct yourself.

Use `docs/INTERVIEW_PLAYBOOK.md` as the detailed explanation and question bank for each block.

## The story to know by heart

EventFlow is a Laravel API with a React/Vite frontend and PostgreSQL database. Users list available events, submit a registration, receive a public reference, and wait for a ticketing webhook to confirm the registration. Laravel protects the last seat with a database transaction and row lock. It protects the webhook with HMAC-SHA256 over the exact raw request body and handles provider retries idempotently.

Do not memorize every line. Memorize why each layer exists and be able to find the line quickly.

## Today’s schedule (about 9 focused hours)

### Block 1 — 45 minutes: requirements and demo

Read:

- `docs/REQUIREMENTS.md`
- `README.md`
- `routes/api.php`

Then run the app and perform the full demo: events list → choose event → register → copy reference → demo confirmation → confirmed status.

Be able to say what is intentionally absent: no delete endpoint, no admin UI, no authentication, and no WebSockets, because the PDF did not require them.

### Block 2 — 75 minutes: Laravel request lifecycle

Trace registration in this order:

1. `routes/api.php` matches `POST /api/events/{event}/registrations`.
2. Route model binding loads the `Event` model from `{event}`.
3. `StoreRegistrationRequest` authorizes and validates the input.
4. `RegistrationController@store` delegates business logic.
5. `RegistrationService::create` owns the transaction, availability, capacity, reference, and insert.
6. `RegistrationResource` controls the JSON response.
7. Laravel returns HTTP 201; invalid input returns 422; unavailable/full returns 409.

Practice question: “Why not put everything in the controller?”

Answer: the controller should translate HTTP into an application call and response. Capacity and transaction rules are business logic, so the service keeps them reusable and independently understandable. Validation belongs in a Form Request, and output formatting belongs in a Resource.

### Block 3 — 75 minutes: database and capacity safety

Read:

- all three migrations
- `Event.php`, `Registration.php`, and `WebhookEvent.php`
- `RegistrationService.php`
- `RegistrationTest.php`

Draw: `events 1 ─── * registrations` and explain the foreign key, unique registration reference, unique ticket ID, and unique webhook event key.

The critical explanation:

> A plain “count then insert” can oversell because two requests can read the same count. I start a database transaction and lock the selected event row with `lockForUpdate`. Requests for that event are serialized until commit, so the second request recounts after the first insert and cannot take the same final seat.

Know the limitation: the feature test proves the capacity boundary, but a true concurrency test should use two PostgreSQL connections. SQLite does not reproduce PostgreSQL row locking.

### Block 4 — 90 minutes: webhook security and idempotency

Read:

- `TicketingWebhookController.php`
- `TicketingWebhookService.php`
- `DemoTicketingController.php`
- `TicketingWebhookTest.php`
- `config/services.php`

Explain the real flow:

1. The provider serializes JSON.
2. It calculates `hash_hmac('sha256', exactRawBody, WEBHOOK_SECRET)`.
3. It sends the body plus `X-Webhook-Signature`.
4. Laravel reads the exact raw body and independently calculates the signature.
5. `hash_equals` safely compares expected and supplied signatures.
6. Only then does Laravel validate and process the payload.
7. A deterministic SHA-256 event key plus a unique database constraint prevents duplicate effects.
8. The registration is locked and changed from pending to confirmed with its ticket ID.

Likely questions:

- Why raw body? Parsing and re-encoding JSON can change whitespace or key formatting, producing a different HMAC.
- Why HMAC? It verifies integrity and that the sender knows the shared secret; it is not encryption.
- Why `hash_equals`? Ordinary string comparison can leak timing information.
- Why return success for a duplicate? Providers retry until success. A duplicate valid event is already handled and should not trigger endless retries.
- Is the application fully replay-proof? It prevents repeated state changes for the same deterministic event. For stronger production protection, require a provider event ID and signed timestamp, reject stale timestamps, and rotate secrets.

### Block 5 — 60 minutes: React data flow

Read:

- `App.jsx`
- `EventsPage.jsx`
- `RegistrationPage.jsx`
- `RegistrationStatusPage.jsx`
- `RegistrationForm.jsx`
- `services/api.js`

Explain:

- React Router maps URLs to three pages.
- Pages own loading, success, and error state.
- `RegistrationForm` owns user input and displays Laravel 422 field errors.
- `api.js` centralizes JSON headers, response parsing, and thrown errors.
- The status page polls only while pending and clears its timer during effect cleanup.
- The browser never receives `WEBHOOK_SECRET`; demo confirmation asks Laravel to construct and sign the real webhook.

### Block 6 — 60 minutes: tests, HTTP, and error codes

Read the nine feature tests. For every test, say Arrange, Act, Assert aloud.

Know these responses:

| Scenario | Expected status |
|---|---:|
| Create registration | 201 |
| Invalid registration input | 422 |
| Full or unavailable event | 409 |
| Invalid webhook signature | 401 |
| Valid webhook | 200 |
| Duplicate valid webhook | 200 |

Run:

```bash
php artisan test
vendor/bin/pint --test
pnpm run build
```

Be ready to explain that tests use factories, `RefreshDatabase`, real calculated signatures, database assertions, and JSON response assertions.

### Block 7 — 45 minutes: Git, production, and Laravel Cloud

Know the distinction:

- Git records local versions.
- GitHub hosts the repository and lets Laravel Cloud pull it.
- Laravel Cloud builds the app, injects production environment variables, attaches PostgreSQL, runs migrations, and serves an HTTPS URL.
- `.env` and real secrets never go into Git.
- `APP_DEBUG=false` in production prevents sensitive exception details being exposed.
- Deployment migrations use `php artisan migrate --force` because production commands are non-interactive.

If asked how you would scale first: measure bottlenecks, move external ticket processing to a durable queue, add observability/rate limiting, then scale workers and PostgreSQL based on evidence. Do not say “microservices” without a measured reason.

### Block 8 — 90 minutes: live-edit rehearsal

For each exercise, first name every affected layer. Make the smallest change, add/update a test, then run the relevant test and formatter.

1. **Make phone optional.** Update the registration migration for a new installation, `StoreRegistrationRequest`, React required markers, and tests. Explain that an already-deployed database would need a new migration rather than editing an old one.
2. **Add an event location.** Add a migration, model fillable field, create/update validation, resource, React display, factories/seeder, and tests.
3. **Change polling from 3 to 5 seconds.** Find the timer in `RegistrationStatusPage`; keep cleanup behavior intact; build the frontend.
4. **Return remaining capacity.** Calculate it consistently with the same seat-holding rule, expose it through `EventResource`, render it, and test zero at capacity.
5. **Add a signed webhook timestamp.** Add payload validation, include it in signing/idempotency, reject stale delivery, update the simulator, and add valid/stale tests.
6. **Add cancellation.** First clarify whether cancellation frees a seat. This changes allowed statuses, capacity counting, endpoint/business rules, UI, and tests. State the ambiguity before coding.

## Rapid-fire questions and strong short answers

**Why PostgreSQL?** The assignment requires a relational database and PostgreSQL provides transactions, row locks, constraints, and production-grade concurrency.

**Why pending registrations count toward capacity?** A successful registration reserves the seat while external ticket confirmation is pending; otherwise registrations could exceed capacity before webhooks arrive.

**Why a public reference instead of the ID?** It avoids exposing predictable internal primary keys and gives the user a stable lookup value.

**Could the random reference collide?** Yes, with very low probability. The code checks before insert and the unique database constraint is the final integrity guarantee. In very high concurrency, retrying a unique-constraint failure would be stronger.

**Why database constraints if Laravel validates?** Validation gives friendly errors; constraints protect integrity against races, bugs, scripts, and other writers.

**Why store webhook payloads?** Audit and debugging. Do not store the shared secret, and production retention/redaction policies should protect personal data.

**What would you improve?** Authentication for event-management endpoints, queue-based external processing, timestamped/provider-ID webhooks, observability and rate limiting, and a real PostgreSQL concurrency test—prioritized by risk and load.

**Where did AI help?** Use `AI.md`. Be truthful: AI helped plan, generate drafts, debug failures, and prepare tests/docs; you reviewed decisions, rejected unnecessary scope, ran verification, and remain responsible for the result.

## Final 30-minute rehearsal

Without notes:

1. Give the one-minute application explanation.
2. Draw both request flows.
3. Explain the last-seat race condition.
4. Explain HMAC and duplicate handling.
5. Demonstrate the app once.
6. Run one targeted test.
7. Make a tiny polling or validation change, test it, and explain the files touched.

If you forget a line, do not bluff. Say: “I know this rule belongs in the service/request/resource; I would open that file and verify the exact implementation.” That is stronger than inventing an answer.
