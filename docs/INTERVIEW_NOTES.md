# EventFlow Interview Notes

## One-minute explanation

React lists available events. Registration passes through a Form Request, controller, and `RegistrationService`, which opens a transaction and locks the event row before checking capacity. It creates a unique public reference with pending status. The ticket system signs exact JSON bytes with HMAC-SHA256. Laravel verifies the signature, validates the payload, uses a unique webhook key to prevent duplicate work, and confirms the registration. React polls the reference lookup until confirmed.

## Key questions

- A race condition occurs when two requests read the same old capacity. Count-then-insert alone is not atomic.
- `DB::transaction` commits all enclosed changes or rolls them back. `lockForUpdate` locks the event until commit/rollback, so the second final-seat request waits and then sees the new count.
- HMAC proves the sender knows a shared secret and detects body changes. Raw bytes matter because JSON reformatting changes the digest. `hash_equals` avoids timing-sensitive ordinary comparison.
- Provider retries are normal. Stable webhook fields form a SHA-256 key with a unique database constraint. Duplicates return success but do not repeat state changes.
- Event has many registrations; registration belongs to event. References and ticket IDs are unique; the event foreign key prevents orphan registrations.
- React Router owns three pages. `api.js` centralizes fetch errors. Polling schedules only while pending and effect cleanup clears its timer.

## Mandatory test map

- Success: HTTP 201, public reference, pending row.
- Validation: HTTP 422 and no row.
- Capacity: HTTP 409 and count remains at capacity.
- Webhook: real calculated signature, confirmation, ticket, and log.
- Invalid signature: HTTP 401 and no changes.
- Duplicate: two successful responses, one webhook record.

The capacity feature test proves the boundary. A true concurrency test needs two PostgreSQL connections because in-memory SQLite does not implement PostgreSQL row locking.

## Live-change practice

Before coding, identify layers. Adding company name touches migration, model, request, form, and test. Making phone optional touches validation, nullable schema, form, and test. Adding cancelled status touches event requests, availability, and tests. Changing polling touches the status page. Adding a webhook timestamp touches signing, validation, idempotency, sender, tests, and docs.

At scale, first add durable asynchronous provider processing, observability/rate protection, and measured worker/database scaling. Event API authentication is a documented production need but intentionally outside the assignment.
