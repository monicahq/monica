# Quickstart: Remove Copy Tests

## 1. No copy assertion remains (SC-001)

Rerun the audit matcher from the scratchpad against `tests/`. Expected: zero methods asserting a
multi-word catalogue string, zero `__()` or `trans_choice()` calls inside an assertion.

```bash
grep -rnE 'assert\w*\([^;]*\b(__|trans_choice)\(' tests
```

Expected: no output.

## 2. Rewording breaks nothing (SC-002)

Change five shipped strings locally, one per layer (a flash message, a view model label, a refusal,
a notification subject, a screen heading), run the suite, then revert them.

## 3. Nothing behavioural lost (SC-004)

Compare the per-file count of non-copy assertions with the count taken before the change. Review any
file whose drop is not explained by R3 or R4 of [research.md](research.md).

## 4. Done conditions

```bash
php artisan test --compact
```

```bash
vendor/bin/phpstan analyse --no-progress
```

```bash
vendor/bin/pint --dirty --format agent
```

```bash
php artisan monica:localize --check
```
