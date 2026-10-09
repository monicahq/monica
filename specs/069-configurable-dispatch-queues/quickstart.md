# Quickstart: Configurable dispatch queues

What proves the feature end to end. The variables are in [plan.md](plan.md); why the worker commands stay as they are is [research.md](research.md) R3.

## Automated

```bash
php artisan test --compact tests/Unit/Providers/AppServiceProviderTest.php
```

Expected: the forwarded name is stored and popped, and a blank name stays on `high`. The full suite stays green with no `assertPushedOn()` changed (R5).

## By hand, on the shipped stack

Prerequisite: `QUEUE_CONNECTION=database` in `.env`, the stack started with `docker compose up -d`, and both queues empty.

1. **Defaults (SC-001).** Set nothing. Sign up as Jim Halpert. The `jobs` table shows rows on `high` and `low` only, and they empty within seconds.
2. **Custom names (User Story 1, SC-002).** Set `MONICA_QUEUE_HIGH=monica-high` and `MONICA_QUEUE_LOW=monica-low`, then run `docker compose restart queue`. Sign up as Pam Beesly, rename a contact. Rows appear on `monica-high` and `monica-low`, none on `high` or `low`, and the shipped worker empties both with no edit to its command (User Story 3).
3. **One worker (User Story 2, SC-004).** Set both variables to `default`, then run `php artisan queue:work --stop-when-empty` with no `--queue`. Sign up as Dwight Schrute: the verification email is sent.
4. **Blank (FR-006).** Set `MONICA_QUEUE_HIGH=`. The next sign up queues on `high`.
5. **Scheduler (FR-004).** With the names of step 2, run `php artisan schedule:test --name="App\Jobs\DeliverDueNotifications"`. Its row lands on `monica-high`.

Afterwards, put `.env` back and run `php artisan queue:restart`.
