# Quickstart: Default Locations

## Automated

```bash
php artisan test --compact tests/Unit/Actions/CreateDefaultLocationsTest.php
php artisan test --compact tests/Unit/Jobs/SeedLocationsTest.php
php artisan test --compact tests/Unit/Migrations/BackfillLocationsTest.php
php artisan test --compact tests/Unit/Actions/CreateAccountTest.php
php artisan test --compact tests/Unit/Enums/DefaultLocationEnumTest.php
```

| Test | Proves |
| --- | --- |
| Action | Six locations, My house at the top, the five rooms inside it, each emoji, no address (US1.1); French names (US1.2); one journal entry (US1.3); nothing added when a location exists, including a second run (US2.3, SC-002) |
| Job | Calls the action for its user |
| Migration | Seeds an account with none, leaves one holding a location, a second run adds nothing (US3) |
| `CreateAccount` | The chain holds `SeedLocations` |
| Enum | Every `emoji()` passes `SingleEmoji` (R8) |

Then `php artisan monica:localize --check`, `vendor/bin/phpstan analyse --no-progress`,
`vendor/bin/pint --dirty --format agent`, and the whole suite.

## By hand, on monica.test

1. Sign up a new account in French with the queue worker running.
2. Settings, Locations: "Maison" at the top level, the five rooms inside it, each with its emoji.
3. Rename "Rangement", move "Chambre" to the top level, try to delete "Maison" while it holds rooms.
4. Never run the migration on the local database. US3 is proven by its test.
