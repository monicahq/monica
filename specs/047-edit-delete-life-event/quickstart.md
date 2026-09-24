# Quickstart: Edit and delete a life event

No schema change; the tables are those of 046.

## Automated

```bash
php artisan test --compact tests/Unit/Actions/UpdateLifeEventTest.php tests/Unit/Actions/DestroyLifeEventTest.php tests/Unit/Actions/RestoreLifeEventTest.php
php artisan test --compact tests/Unit/ViewModels/Vaults tests/Feature/Controllers/App/Vaults
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
```

## By hand

1. Hover an entry: Edit and Delete appear; Tab onto the entry: they appear too (US1.1).
2. Edit: the panel opens filled in, the entry reads Editing; change the date and add Pam; save: the
   entry moves, reads Edited, and shows on Pam’s page (US1.2 to 1.7).
3. Edit, remove a filled custom field and a file field, save: both are gone (US1.4).
4. Edit, change the type, save: the old custom values are gone, the new type’s fields are offered (US1.6).
5. Delete, Keep it: nothing changes. Delete, Delete life event: gone from both pages; Undo: back whole (US2).
6. Repeat 2 and 5 with the keyboard only and with VoiceOver.
