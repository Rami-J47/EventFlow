# AI Usage

AI assisted with reading the six-page PDF, building traceability, planning, routine Laravel/React scaffolding, security design, tests, debugging, and documentation. The PDF and user decisions remained authoritative.

Accepted suggestions included standard Form Requests, two small transaction-focused services, PostgreSQL event-row locking, raw-body HMAC-SHA256 with `hash_equals`, a unique deterministic webhook key, native React `fetch`, local state, and polling. Each was checked through tests or a production build.

Authentication, repositories, CQRS, Redis, queues, WebSockets, payments, email, and broader event-SaaS features were rejected as outside scope. A non-portable `HAVING` alias query was also rejected after tests failed and replaced with a correlated capacity subquery. Client-side signing was rejected because it exposes the secret; the demo signer stays in Laravel and uses the real webhook route.

Human review remains essential. The owner should explain the transaction lock, HMAC, idempotency, migrations, React effect cleanup, and tests. Hosting credentials and the public URL require the owner's deployment account and cannot be safely invented by AI.
