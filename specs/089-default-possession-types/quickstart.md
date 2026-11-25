# Quickstart: Default Possession Types

## Automated

```bash
php artisan test --compact tests/Unit/Actions/CreateDefaultPossessionTypesTest.php
php artisan test --compact tests/Unit/Jobs/SeedPossessionTypesTest.php
php artisan test --compact tests/Unit/Migrations/BackfillPossessionTypesTest.php
php artisan test --compact tests/Unit/Actions/CreateAccountTest.php
php artisan test --compact tests/Unit/Enums/PossessionTypeEnumTest.php
```

| Test | Proves |
| --- | --- |
| Action | 25 types; Comics order and kinds (US1.2); Coins grades (US1.3); every select offers a choice (US1.4); Other empty (US1.5); French names with codes kept (US1.6); nothing added when a type exists, including a second run (US2.2, SC-003); one journal entry (FR-008) |
| Action, every case | `ExportPossessionType` of each seeded type matches the case's `blocks()` (SC-002) |
| Job | Calls the action for its user |
| Migration | Seeds an account with none, leaves one with a type and one with no user, a second run adds nothing (US3) |
| `CreateAccount` | The chain holds `SeedPossessionTypes` |
| Enum | `translationKeys()` holds no code and no duplicate |

Then `php artisan monica:localize --check`, `vendor/bin/phpstan analyse --no-progress`,
`vendor/bin/pint --dirty --format agent`, and the whole suite.

## By hand, on monica.test

1. Sign up a new account in French with the queue worker running.
2. Settings, Possession types: 25 types, French names, colours by family.
3. Open Coins, Wine and CD; compare with the spec's catalogue.
4. On a copy of a database whose accounts predate the release, never the local one, run
   `php artisan migrate`: each account holds 25 types, and one that already held a type is unchanged.
