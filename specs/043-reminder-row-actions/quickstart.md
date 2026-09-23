# Quickstart: Reminder row actions

## Automated

```bash
php artisan test --compact tests/Unit/ViewModels/Vaults/ReminderRowTest.php
php artisan test --compact tests/Feature/Controllers/App/Vaults
php artisan test --compact
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

## Manual, on http://erica.test/vaults/personal/reminders

Needs one repeating and one one time reminder, one of them late.

| Step | Expected |
| --- | --- |
| Look at a row | Flap, stamp with cadence, name with chevron, text · relative time, Dismiss, More |
| Press Dismiss, then Cancel | Modal names the reminder; after Cancel the row is still there, focus on Dismiss |
| Press Dismiss on the one time reminder | The modal promises no next one |
| Press Dismiss, confirm | Row gone, "The reminder was dismissed." shown |
| Open More with Enter | Four entries as in the plan; `aria-expanded="true"` on More |
| Escape | Menu closes, focus on More |
| More, Send me a test, Escape | Nothing sent, focus on More |
| More, Send me a test, confirm | "On its way." shown, email in the mail catcher |
| More, Remind me later | Today's postpone modal; Escape returns focus to More |
| More, Go to the reminder | The reminder's screen, no modal |
| Switch grouping, repeat Dismiss | Works without reload; only one modal opens |
| On the reminder's screen, Dismiss on the next deadline card | Same modal |
| Row in that screen's history | Not a link; More has no Go to the reminder |
| Narrow the window to 375px | Actions wrap under the text; menu stays on screen |
