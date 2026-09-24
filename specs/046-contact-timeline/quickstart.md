# Quickstart: Contact timeline and adding a life event

The two new tables are created migrations, so a database that predates them is rebuilt with
`php artisan migrate:fresh --seed`, never patched.

## Automated

```bash
php artisan test --compact tests/Unit/Actions/CreateLifeEventTest.php
php artisan test --compact tests/Unit/ViewModels/Vaults/ContactTimelineBlockTest.php
php artisan test --compact tests/Unit/ViewModels/Vaults/LifeEventFormBlockTest.php
php artisan test --compact tests/Feature/Controllers/App/Vaults
php artisan test --compact tests/Unit/Actions/SetFieldValuesTest.php
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
```

## By hand, in a seeded account

1. Open a contact with no life event: "You added …" and **Add the first life event** show (US4).
2. Open the panel, switch categories, search "mov": Move is listed under its category (US1.2, 1.3).
3. Pick Move, add a second person, open Summary and a custom field, add it: the toast names both
   people, the entry tops the timeline marked Just added, and it shows on the second person’s page
   (US1.9, SC-003).
4. Pick Call, open a custom field, press Change type, pick Meal: the date and people stay, the
   custom value is gone (US2).
5. Post a duration of 0: the panel reopens at step 2 with the error on Duration (US1.11).
6. Repeat steps 2 to 4 with the keyboard only and with VoiceOver (FR-007, SC-004).
