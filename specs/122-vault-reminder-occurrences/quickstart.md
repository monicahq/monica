# Quickstart: validating vault reminder occurrences

## Automated

```bash
php artisan test --compact tests/Unit/Helpers/VaultReminderDatesTest.php
php artisan test --compact tests/Feature/Controllers/Api/VaultReminderOccurrenceControllerTest.php
php artisan test --compact tests/Unit/Mcp/Tools/ListVaultReminderOccurrencesTest.php
php artisan test --compact tests/Unit/ViewModels/Vaults/VaultDeadlinesTest.php
php artisan test --compact --filter=RouteProtection
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
```

Expected: all green. `VaultDeadlinesTest` proves the dashboard did not move when its query moved.

## By hand

1. In a vault, give Michael a birthday with a year and tick "Remind me about this date"; give Dwight a
   birthday falling in three days without ticking it; give a group a reminder for tomorrow.
2. `GET /api/v1/vaults/{vault}/reminder-occurrences` with an API key: Michael's occurrence and the
   group's, not Dwight's. Compare with the dashboard's "Next seven days".
3. Same call with `kind=birthdays`: Michael's occurrence with his age, Dwight as `type: birthday` with
   his age, no group.
4. Mark the birthday field sensitive in the template, repeat with a key lacking `sensitive-fields`:
   Dwight is gone, Michael has no age.
5. Over MCP, ask "whose birthday is it this week?": the assistant calls
   `list-vault-reminder-occurrences` once with `kind=birthdays`.

See [contracts/vault-reminder-occurrences.md](contracts/vault-reminder-occurrences.md) for the shape.
