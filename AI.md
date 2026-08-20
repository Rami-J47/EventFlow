# AI Usage

## Summary

I used ChatGPT/Codex as a development partner while building EventFlow. I did not use AI as the source of the product requirements. The original technical-assignment PDF remained the source of truth, and I deliberately kept the application limited to events, registration, webhook confirmation, tests, documentation, and deployment preparation.

The work was shared. I supplied the assignment, selected the Laravel/PHP, React/Vite, and PostgreSQL direction, approved the work, made the GitHub account and repository decisions, and retained responsibility for understanding and presenting the result. AI helped analyze the assignment, propose a small architecture, generate implementation drafts, run tools, diagnose failures, and prepare explanations. I am reviewing the important code and practicing changes before the interview rather than presenting unreviewed generated code as my own expertise.

## Where I used AI

### Requirements and planning

I asked AI to read all six pages of the assignment and produce a traceability matrix without inventing features. AI helped map each requirement to a planned route, model, table, React page, and test. This became `docs/REQUIREMENTS.md` and `docs/ARCHITECTURE.md`.

### Laravel initialization

AI inspected the available development tools instead of assuming versions. It helped initialize Laravel, configure React/Vite, preserve the existing planning documents, and record the actual framework/runtime versions.

### Backend implementation

I asked AI to help implement conventional Laravel code for:

- event creation, listing, viewing, and updating;
- Form Request validation;
- Eloquent models and relationships;
- PostgreSQL-oriented migrations and constraints;
- registration references;
- transactional capacity protection;
- registration lookup by public reference;
- HMAC webhook verification;
- duplicate webhook handling;
- a server-side demonstration sender.

### Frontend implementation

AI helped draft three React views: available events, event registration, and registration status. It also helped create a small native-`fetch` API module, form error display, loading/empty/error states, pending-status polling, timer cleanup, and responsive styling.

### Testing and debugging

AI helped write and run Laravel feature tests for all six mandatory scenarios plus event APIs and the demonstration sender. It also ran Composer validation, Laravel Pint, and the Vite production build.

The first implementation did not pass every check. AI analyzed the actual failures instead of claiming the code should work:

- Laravel did not provide the attempted `havingColumn` method.
- Replacing it with a `HAVING` expression then failed on the SQLite test database because SQLite rejected `HAVING` on that non-aggregate query.
- The query was changed to a correlated registration-count subquery that works in the test environment and PostgreSQL.
- The first frontend build could not find Node on `PATH`; the verified bundled Node runtime was then supplied and the build was rerun.
- A generated local server PID file was accidentally staged; the staged file list exposed it, so it was removed and added to `.gitignore` before the commit.

These corrections are important examples of using AI to investigate evidence rather than blindly accepting its first output.

### Documentation and Git

AI helped create the README, architecture diagrams, interview notes, API documentation, environment example, and deployment instructions. It also checked ignored files and secrets before the commit. I supplied my Git author identity and completed GitHub's browser authentication; AI created and pushed the public repository after I authorized it.

## Suggestions I accepted

I accepted the following suggestions because they directly support assignment requirements and remain understandable:

- Standard Laravel controllers, Form Requests, models, migrations, relationships, and API Resources.
- A small `RegistrationService` because the transaction and row-lock rule is clearer outside the controller.
- A small `TicketingWebhookService` because idempotent database processing belongs in one transaction-focused place.
- `DB::transaction` and `lockForUpdate` to prevent two concurrent requests from taking the last seat.
- Pending registrations reserve capacity because the registration has succeeded and is only waiting for ticket confirmation.
- A public `EVT-XXXXXXXX` reference with a database unique constraint instead of exposing numeric IDs.
- HMAC-SHA256 over the exact raw request body with the secret stored only in server configuration.
- `hash_equals` for signature comparison.
- A deterministic SHA-256 webhook event key plus a database unique constraint for idempotency.
- React polling every three seconds because the assignment needs updated status but does not require WebSockets.
- Native `fetch` and local React state because Redux would be unnecessary for three pages.
- SQLite for isolated tests while PostgreSQL remains the application and production database.

## Suggestions I changed or rejected

I rejected features and architecture that were not requested, including:

- authentication and user accounts;
- roles and permissions;
- payments;
- email and SMS;
- QR codes and ticket PDFs;
- Redis;
- queues in the assignment implementation;
- WebSockets;
- GraphQL;
- repository pattern, CQRS, microservices, and other unnecessary layers.

I also rejected putting webhook signing in React because that would expose `WEBHOOK_SECRET` to every browser. The demonstration sender is server-side and submits a signed request through the real webhook route.

I changed AI's initial availability query after real tests proved it was not portable. I also kept event administration as APIs only because the PDF requires event-management APIs but does not require an event-admin interface.

## How I verified AI-assisted work

I did not treat generated code as correct merely because it looked plausible. The final checks were:

- Composer configuration validation;
- Laravel Pint formatting check;
- 9 passing Laravel feature tests with 41 assertions;
- successful Vite production build;
- route-list inspection;
- database migration and seed execution;
- a real local HTTP flow from event listing to pending registration to signed webhook confirmation;
- a duplicate webhook check;
- Git ignored-file and secret checks;
- comparison against every page of the original PDF.

The application was later deployed to Laravel Cloud over HTTPS. A production smoke test found that the page loaded but the event API initially returned HTTP 500, so deployment was not treated as complete until its database configuration, migrations, seed data, and full registration/webhook flow could be verified.

## Interview explanation

If asked how I used AI, I would say:

> I used AI as a pair-programming and review tool. I gave it the assignment and strict scope, and it helped me turn the requirements into a traceability matrix, draft conventional Laravel and React code, and run the project checks. I did not accept the first output blindly. For example, an event-availability query failed first because of a nonexistent Laravel method and then because the replacement was not portable to SQLite. I used the test evidence to replace it with a correlated subquery and reran the full suite. I also rejected unnecessary authentication, repositories, WebSockets, queues, and client-side webhook signing. I am reviewing each important file and practicing changes so I can explain and modify the application myself.

## Responsibility

AI accelerated implementation, but I remain responsible for the submitted repository, technical decisions, security, deployment configuration, and my ability to explain or change the code. Before the interview I will continue reviewing the request lifecycle, migrations, relationships, transaction lock, HMAC verification, idempotency, React polling, and feature tests.
