# EventFlow Interview Playbook

This is the document I will use to prepare for the technical discussion. It is written around the application we actually built and the problems we actually faced. I should not memorize it word for word. I should practice saying the ideas naturally while pointing to the real files.

## What the assignment asked for

The assignment asked for a small event-registration application, not a complete event-management business.

The required parts were:

1. Event APIs to create, list, view, and update events.
2. A React frontend that lists available events and accepts first name, last name, email, and phone.
3. Capacity protection so registrations cannot exceed an event's limit.
4. `POST /api/webhooks/ticketing` to confirm a registration.
5. Webhook authentication and duplicate-delivery handling.
6. Relational tables for events, registrations, and webhook logs.
7. Six mandatory automated test scenarios.
8. A public HTTPS URL, local setup instructions, API documentation, migrations, seed data, `.env.example`, and `AI.md`.
9. An answer explaining the first three improvements for thousands of users.
10. The ability to demonstrate the application, explain decisions, and make a small change.

We deliberately did not add payments, accounts, roles, email, QR codes, WebSockets, queues, GraphQL, or microservices. Those may be useful in a larger product, but they were not required here.

## My opening explanation

If they say, "Tell us about the application," I will say:

> EventFlow is a Laravel API with a React and Vite frontend and a PostgreSQL database. A visitor sees active future events that still have capacity, selects one, and submits their contact details. Laravel validates the request and creates a pending registration with a public reference. The capacity check runs inside a database transaction while the event row is locked, which prevents two requests from taking the last seat. A ticketing system then sends an HMAC-signed webhook. Laravel verifies the exact raw body, processes the delivery idempotently, confirms the registration, and React displays the updated status. The application is deployed on Laravel Cloud and uses an isolated PostgreSQL schema hosted by Supabase.

That answer should take about one minute.

## How I approached the assignment

If they ask, "Where did you start, and what steps did you follow?" I will explain this sequence:

### 1. I converted the PDF into acceptance criteria

I read every page and separated explicit requirements from my own implementation decisions. I recorded the mapping in `docs/REQUIREMENTS.md` so I could see the route, model, table, React component, and test for every requirement.

Why: it prevented scope creep and made it difficult to accidentally miss a deliverable such as duplicate webhooks, `AI.md`, or the public URL.

### 2. I chose the smallest suitable architecture

I used one Laravel application serving both the API and compiled React assets. React Router provides the three public pages. PostgreSQL stores the relational data. I used normal Laravel controllers, Form Requests, API Resources, models, and two small services.

Why: it is easy to understand, deploy, test, and explain. Separate frontend/backend deployments, Redux, repositories, and microservices would add complexity without solving an assignment requirement.

### 3. I designed the data and integrity rules before the UI

I created:

- `events`
- `registrations`
- `webhook_events`

I added foreign keys, unique public references, unique ticket IDs, a unique webhook key, and query indexes. I decided that pending registrations reserve seats because registration has already succeeded even though ticket confirmation has not arrived.

### 4. I implemented event and registration APIs

I built event create/list/view/update endpoints, validation, resources, and tests. Then I added registration creation through `RegistrationService`, because the transaction and capacity rule were easier to understand there than in a controller.

### 5. I protected the last seat

I placed the availability check, capacity count, and insert inside `DB::transaction`. I reload the event using `lockForUpdate` before counting registrations.

Why: a normal count followed by an insert has a race condition.

### 6. I implemented the real webhook boundary

The webhook controller reads the exact raw request body and compares an HMAC-SHA256 signature using `hash_equals`. The service creates a deterministic event key and relies on a unique database constraint to avoid applying the same delivery twice.

The demo button does not fake the confirmed status. It asks Laravel to construct and sign a provider-style request that passes through the actual webhook route.

### 7. I built the React flow

I added event listing, registration, status display, error/loading states, and polling while a registration remains pending. The effect cleanup removes the timer when the page unmounts or confirmation finishes.

### 8. I tested, debugged, and simplified

The first availability query used a method Laravel did not provide. A replacement using `HAVING` then failed in SQLite tests. We replaced it with a correlated count subquery that works in the test environment and PostgreSQL.

This is a useful interview story because it shows that we used failing evidence, not assumptions.

### 9. I deployed and tested the public system

Laravel Cloud hosts the application. A paid Cloud database was outside my budget, so we reused an existing free Supabase PostgreSQL project without mixing applications: EventFlow has its own `eventflow` schema and a restricted non-admin database role.

The deployment also exposed real configuration problems:

- the first Cloud account was the wrong account;
- no production database was attached;
- the shared pooler rejected the custom role, so we used the direct PostgreSQL endpoint;
- environment changes needed a new deployment before commands saw them;
- Laravel needed a valid private `APP_KEY`.

We fixed each problem and reran the public flow instead of declaring deployment complete early.

## The files I must know

### Routing and request entry

- `routes/api.php`: all API endpoints.
- `routes/web.php`: serves the React application.
- `app/Http/Requests`: backend validation and authorization decisions.
- `app/Http/Controllers/Api`: converts HTTP requests into service/model calls and responses.

### Business rules

- `app/Services/RegistrationService.php`: event availability, capacity transaction, row lock, reference generation, and registration insert.
- `app/Services/TicketingWebhookService.php`: idempotency key, webhook log, locked registration lookup, confirmation, and ticket ID.

### Database and responses

- `app/Models`: relationships, fillable fields, casts, and route key.
- `database/migrations`: tables, foreign keys, unique constraints, and indexes.
- `database/seeders/DatabaseSeeder.php`: the two demonstration events.
- `app/Http/Resources`: the public JSON shape.

### React

- `resources/js/App.jsx`: routes and page structure.
- `resources/js/pages/EventsPage.jsx`: fetches and displays available events.
- `resources/js/pages/RegistrationPage.jsx`: loads one event and submits registration.
- `resources/js/pages/RegistrationStatusPage.jsx`: shows the reference and polls until confirmed.
- `resources/js/components/RegistrationForm.jsx`: fields, client validation hints, server validation errors, and submit state.
- `resources/js/services/api.js`: shared fetch/JSON/error behavior.

### Verification and explanation

- `tests/Feature`: event, registration, webhook, signature, duplicate, and demo-flow tests.
- `README.md`: setup, API, architecture decisions, limitations, deployment, and scaling answer.
- `AI.md`: honest description of shared human/AI work.
- `docs/REQUIREMENTS.md`: PDF traceability.

## Registration request flow

I should be able to draw and explain this without notes:

```text
React form
  -> POST /api/events/{event}/registrations
  -> route model binding loads Event
  -> StoreRegistrationRequest validates input
  -> RegistrationController
  -> RegistrationService
  -> DB transaction + event row lock
  -> availability and capacity checks
  -> create pending Registration
  -> RegistrationResource
  -> HTTP 201 with EVT reference
  -> React status page
```

Likely follow-up: "Why use a Form Request?"

Answer:

> It keeps validation and authorization out of the controller, gives Laravel's standard 422 response, and makes the accepted input obvious in one place.

Likely follow-up: "Why use a service?"

Answer:

> The transaction and capacity rule are business logic. The controller remains responsible for HTTP, while the service owns an atomic use case.

## Capacity and concurrency

The unsafe version is:

```text
count registrations
if count < capacity
insert registration
```

Two requests can both count 39 when capacity is 40, and both can insert.

Our version is:

```text
begin transaction
lock the event row
check active status and date
count registrations again
insert only when count < capacity
commit
```

The second request waits for the first transaction. When it obtains the lock, it sees the new count.

### Questions they may ask

**Does a transaction alone solve the race?**

No. A transaction groups changes atomically, but without an appropriate lock or isolation strategy both transactions may still read the same old count. `lockForUpdate` serializes registration decisions for the same event.

**Why lock the event rather than the registration rows?**

The event row always exists and represents the capacity being protected. When there are zero registrations, there are no child rows to lock.

**Why do pending registrations consume capacity?**

The registration endpoint has already told the user they successfully reserved a place. If pending did not count, many users could reserve the same seats before ticket webhooks arrived.

**What is the testing limitation?**

The feature test proves the capacity boundary. A real lock-concurrency test should run two PostgreSQL connections because in-memory SQLite does not reproduce PostgreSQL row locks.

**Can capacity be reduced below current registrations?**

No. `UpdateEventRequest` rejects that update, and a feature test checks the rule.

## Webhook flow

```text
ticketing provider creates JSON
  -> HMAC-SHA256(exact raw JSON, shared secret)
  -> POST /api/webhooks/ticketing
  -> controller reads exact raw body
  -> calculates expected signature
  -> hash_equals(expected, provided)
  -> validates event/reference/ticket/status
  -> TicketingWebhookService transaction
  -> unique deterministic event key
  -> lock registration
  -> set confirmed + ticket ID
  -> record processed webhook
  -> HTTP 200
```

### Questions they may ask

**What is HMAC?**

It is a keyed message authentication code. It proves that the sender knows the shared secret and that the body was not modified. It does not encrypt the body.

**Why sign the raw body?**

Parsing and recreating JSON can change whitespace, escaping, or key formatting. HMAC must run over the exact same bytes on both sides.

**Why use `hash_equals`?**

It compares in a timing-safe way instead of exiting at the first different character.

**Why verify the signature before validation?**

Untrusted callers should not reach application processing. Verification also avoids giving detailed payload feedback to unauthenticated senders.

**What happens when the provider retries?**

The same stable fields produce the same SHA-256 event key. A database unique constraint allows only one webhook log/effect. A duplicate valid delivery receives HTTP 200 with `duplicate: true`, which stops needless provider retries.

**Why not send the secret to React?**

Anything shipped to the browser is public. The demo sender stays in Laravel and calls the real webhook route.

**What would make webhook security stronger?**

Use a provider event ID, include a signed timestamp, reject stale timestamps, support secret rotation, rate-limit the endpoint, and process provider work through a durable queue.

## Database questions

**Describe the relationships.**

An event has many registrations. A registration belongs to one event. Webhook logs keep external delivery evidence and reference the public registration value for audit, but they are not modeled as a destructive cascading relationship.

**Why use both validation and database constraints?**

Validation produces friendly errors. Constraints are the final integrity boundary against concurrency, bugs, scripts, or another writer.

**Important constraints and indexes:**

- unique registration reference;
- foreign key from registration to event with restricted deletion;
- unique ticket ID when present;
- unique webhook event key;
- event status/date index for availability;
- registration event index for relationships and capacity counts;
- webhook registration-reference index for audit lookup.

**Why store phone as a string?**

Phone numbers may contain `+`, spaces, punctuation, country codes, and leading zeroes. They are identifiers, not numbers used in arithmetic.

**Why store webhook payload JSON?**

It helps audit and diagnose integrations. In a real system I would also define retention and redaction policies for personal data.

## React questions

**Why React Router?**

It cleanly separates event list, registration, and status URLs without needing server page templates for each state.

**Why no Redux?**

The application has three small pages with local request state. Redux would add ceremony without a shared-state problem.

**How are API errors handled?**

`api.js` applies JSON headers, parses the response, throws a normal error for non-success responses, and retains Laravel's field-error object. Components show field or page-level messages.

**How does polling work?**

The status page requests the reference while it is pending, schedules the next request after a short interval, and clears the timer in the effect cleanup. Polling stops after confirmation.

**Why polling instead of WebSockets?**

The PDF only requires the updated status to appear. Polling is smaller, reliable for a demo, and avoids additional infrastructure. At scale, WebSockets or server-sent events could improve latency and request volume.

## HTTP status codes to remember

| Situation | Status |
|---|---:|
| Registration created | 201 |
| Successful GET/update/webhook | 200 |
| Invalid form or webhook payload | 422 |
| Invalid or missing webhook signature | 401 |
| Event unavailable/full or state conflict | 409 |
| Missing route-bound model | 404 |

## Tests I must explain

The PDF requires six scenarios:

1. Successful registration.
2. Registration validation failure.
3. Event capacity.
4. Successful webhook.
5. Invalid signature.
6. Duplicate webhook.

We also test event create/list/view/update, unavailable event filtering, the server-side demo sender, and preventing capacity from being reduced below existing registrations.

If asked how a test is structured:

> I use Arrange, Act, Assert. A factory creates the starting records, the test sends a JSON request, then it checks both the HTTP response and the database state. The webhook success test calculates a real HMAC instead of bypassing signature verification.

If asked why `RefreshDatabase`:

> Each test starts from a predictable schema and does not depend on data created by another test.

## Deployment story

**Git versus GitHub versus Laravel Cloud:**

- Git records local history.
- GitHub stores the public repository and main branch.
- Laravel Cloud pulls the commit, installs/builds the application, serves it over HTTPS, and stores private environment variables.
- Supabase hosts PostgreSQL in an isolated `eventflow` schema.

**Secrets:**

`APP_KEY`, database credentials, and `WEBHOOK_SECRET` exist only in Laravel Cloud environment configuration. `.env` is ignored. `.env.example` contains placeholders.

**Why `migrate --force` in production?**

Laravel requires explicit confirmation for production migrations; `--force` makes the automated/non-interactive deployment command intentional.

**What failed during deployment?**

I will not hide the failures. The wrong account, missing database, unsupported pooler login, unapplied environment change, and invalid/missing app key were found one by one. We read the actual command and application logs, changed one cause at a time, and reran migrations and the public flow.

That is a strong problem-solving answer because it is real.

## How we used AI

If asked, I will answer truthfully:

> I used ChatGPT/Codex as a pair-programming and review tool. I supplied the assignment, kept the PDF as the source of truth, made the scope, account, cost, and approval decisions, and I am responsible for the final submission. AI helped map requirements, draft conventional Laravel and React code, run commands, investigate test and deployment failures, and prepare documentation. We did not accept every suggestion. We rejected unnecessary architecture and changed code when tests or production logs showed a problem. I reviewed the important flows and practiced changes so I can explain and modify the result myself.

If they ask what AI got wrong:

> The first availability-query suggestion used a Laravel method that did not exist. The next query used `HAVING` in a way SQLite rejected. During deployment, the first suggested pooler format did not support the restricted custom role. In each case we used the error evidence to choose a simpler working solution.

If they ask what I rejected:

> I rejected authentication, payments, email, Redis, queues, WebSockets, repositories, CQRS, and microservices for this assignment because they were outside the PDF. I also rejected client-side webhook signing because it would expose the secret.

Do not say that AI "made everything." Do not say that no AI was used. Both statements are inaccurate. The strong answer is that we collaborated, tested the work, recorded mistakes, and I own the result.

## The final question: first three improvements for thousands of users

I will give exactly three prioritized improvements:

1. **Durable asynchronous webhook/provider processing.** Acknowledge valid webhooks quickly, enqueue processing, retry transient failures, and use a dead-letter path. This protects provider delivery and application response time.
2. **Observability and traffic protection.** Add structured logs, metrics, tracing, alerts, rate limits, and abuse monitoring. I need evidence before choosing what to scale.
3. **Scale stateless workers and PostgreSQL from measurements.** Tune indexes and queries, monitor connection and row-lock contention, add appropriate pooling, and scale Laravel workers/database resources based on real load tests.

If they ask, "Why not microservices first?"

> Microservices add network, deployment, consistency, and operational costs. I would first measure the modular monolith and solve demonstrated bottlenecks.

## Likely live changes

Before editing, I will repeat the requirement in my own words and identify affected layers.

### Make phone optional

Affected layers:

- new production migration to make `phone` nullable;
- `StoreRegistrationRequest` validation;
- React form required behavior;
- registration resource only if output changes;
- success and validation tests;
- documentation if the API contract changes.

I will not edit an old production migration after it has run.

### Add event location

Affected layers:

- new migration;
- `Event::$fillable`;
- store/update Form Requests;
- `EventResource`;
- factory and seeder;
- event cards/pages;
- API tests.

### Change polling from three to five seconds

Affected layers:

- `RegistrationStatusPage.jsx` timer only;
- check cleanup still works;
- run the frontend build.

### Add cancellation

First I must clarify:

- Who can cancel?
- Can confirmed registrations cancel?
- Does cancellation release capacity?
- Does the ticketing provider receive a message?

Then I would change statuses, service rules, capacity counting, endpoint/controller, UI, and tests.

### Add webhook timestamp/replay protection

Affected layers:

- provider/demo payload;
- HMAC raw body;
- validation;
- stale-time rule;
- idempotency fields;
- webhook tests and README.

## How I make a change during the interview

1. Restate the requested behavior.
2. Ask one clarification if the behavior is ambiguous.
3. Find the route and trace the current flow.
4. Name the affected database/backend/frontend/test layers.
5. Make the smallest coherent change.
6. Add or update the narrowest relevant test.
7. Run the targeted test first.
8. Run formatter and relevant build.
9. Explain what changed, why, and any limitation.

I should talk while working. Silence makes it difficult for interviewers to see my reasoning.

## Demo script

1. Open the public URL.
2. Point out active future events and remaining capacity.
3. Register using clearly fake demo data.
4. Show the public `EVT-...` reference and `pending` status.
5. Explain that the secret never enters React.
6. Click **Demonstrate ticket confirmation**.
7. Show `confirmed`.
8. Explain HMAC, the webhook log, and duplicate handling.
9. Open one backend file and one test file if asked.

The normal demo should take three to five minutes.

## Study schedule for today

### Session 1 - 60 minutes: understand the assignment and demo

- Read `docs/REQUIREMENTS.md`.
- Perform the live demo three times.
- Practice the one-minute explanation until it sounds natural.

### Session 2 - 90 minutes: registration and capacity

- Read the registration route, request, controller, service, resource, model, migration, and tests in that order.
- Draw the request flow twice without notes.
- Explain the race condition aloud.

### Break - 15 minutes

### Session 3 - 90 minutes: webhook security

- Read the webhook controller, service, demo controller, config, migration, and tests.
- Explain raw-body HMAC, `hash_equals`, idempotency, and duplicate HTTP 200 without notes.

### Session 4 - 60 minutes: React

- Trace list, register, and status pages.
- Explain local state, API errors, polling, and cleanup.
- Practice finding where to change the polling interval.

### Break - 15 minutes

### Session 5 - 60 minutes: database, tests, and HTTP

- Draw the three tables and constraints.
- Recite the six mandatory tests.
- Practice the HTTP status table.

### Session 6 - 45 minutes: deployment, Git, and AI

- Explain Git/GitHub/Cloud/Supabase.
- Tell the real deployment-debugging story.
- Practice the AI answer honestly.

### Session 7 - 90 minutes: live-edit practice

- Change polling locally, then revert or keep it only if requested.
- Practice making phone optional on paper by naming all affected files.
- Practice adding location on paper, including the migration and test.
- Practice cancellation clarification questions.

### Final rehearsal - 45 minutes

- Full demo once.
- One-minute introduction.
- Capacity explanation.
- Webhook explanation.
- Scaling answer.
- AI explanation.
- One mock small-change discussion.

## Final reminders

- Do not bluff exact code. Open the file and verify.
- Do not claim the production image ran tests; it excludes development dependencies. Say the local suite was verified and the production flow was smoke-tested.
- Do not expose or paste environment secrets.
- Do not apologize for keeping the solution small. Explain that scope control was deliberate.
- Use "I decided" for decisions I own and "we investigated" when describing the collaborative debugging process.
- The interviewers care about how I think more than whether I memorized every method name.
