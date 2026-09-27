# Quickstart: Reminder detail page redesign

## Automated

```bash
php artisan test --compact tests/Unit/ViewModels/Vaults/ReminderViewModelTest.php
php artisan test --compact tests/Feature/Controllers/App/Vaults/ReminderControllerTest.php
php artisan test --compact
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

## By hand, in the example account

| Open | Expect |
| --- | --- |
| A manual monthly reminder | Name, contact, "Every month on day …", Edit and More actions; no source line |
| A birthday reminder | "Based on … · Edit the date"; no Edit button |
| A reminder whose date was cleared | Warning notice with the reason; no Upcoming row; "No upcoming date until this is resolved." |
| An overdue date, press "I forgot" | Row leaves the list and heads History |
| History with more than four answers | "Load more" appends four more |
| More actions › Delete reminder › confirm | Lands on the contact's reminders with the flash |
| Tab through the page | Menu, dialog and "Delivery details" work from the keyboard |
