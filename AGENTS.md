# EventFlow Development Rules

## Source of Truth

The original technical assignment PDF and `docs/REQUIREMENTS.md` define application scope. PDF requirements override supporting prompts and assumptions.

## Stack

- Laravel
- PHP
- React
- Vite
- PostgreSQL

## Priorities

1. Assignment compliance
2. Correct behavior
3. Understandable Laravel code
4. Database integrity
5. Security
6. Tests
7. Documentation
8. UI quality

## Development Process

- Work one approved phase at a time.
- Before substantial implementation, show the plan.
- After each code phase, run relevant tests, frontend build, and formatter/linter.
- Update `docs/REQUIREMENTS.md` with evidence-based status.
- Update `AI.md` truthfully with actual AI assistance and human decisions.
- Explain what changed, why, request/data flow, security, tests, interview concepts, and a suggested commit message.
- Stop for approval before beginning the next major phase.
- Do not make Git commits unless the user explicitly asks.

## Never

- Do not invent features.
- Do not introduce unnecessary dependencies or architectural layers.
- Do not expose or commit secrets or `.env` files.
- Do not bypass the real webhook flow in the demo.
- Do not skip the six mandatory tests.
- Do not overengineer.
- Do not claim that unrun code or commands work.
- Do not replace PostgreSQL with SQLite for production.

## Before Completing Work

- Run `php artisan test`.
- Run the relevant frontend build/lint commands.
- Run the available Laravel formatter.
- Check `docs/REQUIREMENTS.md` against the original PDF.
- Inspect Git status for accidental secrets and generated dependencies.
- Explain all changed files and verification results.

Exact commands may be recorded only after the actual project dependencies and versions have been inspected.
