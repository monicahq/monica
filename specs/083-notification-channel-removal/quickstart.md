# Quickstart: Remove a Telegram or text message setup, and ask for another code

## Automated

```bash
php artisan test --compact tests/Unit/Actions/RemoveChannelSetupTest.php tests/Unit/Actions/SendChannelProofCodeTest.php tests/Unit/Policies tests/Unit/Models/NotificationChannelTest.php tests/Unit/ViewModels/Settings/Account/Notifications
```

```bash
php artisan test --compact tests/Feature/Controllers/App/Settings/Account/Notifications tests/Feature/Controllers/Api/ChannelSetupControllerTest.php tests/Feature/Controllers/Api/NotificationChannelControllerTest.php tests/Feature/RouteProtectionTest.php
```

Then the gates in `CLAUDE.md`: `monica:localize --check`, PHPStan, Pint.

## What each scenario proves

| Spec | Test | Expected |
| --- | --- | --- |
| US1.1, FR-003 | `RemoveChannelSetupTest` | Every field of FR-003 null or zero, `is_enabled` false, one `NotificationChannelSetupRemoved` with only `name` |
| US1.2, FR-005 | `ConfirmChannelProofCode` after removal | Refused: no code was sent |
| US2.2, FR-005 | `CompleteChannelConnection` with the old token after removal | Returns null, row unchanged |
| FR-005 | `DeliverNotification` on a cleared channel | Nothing sent |
| FR-006 | Remove `email` | `ChannelIsNotValid`; 422 over the API |
| Edge: nothing set up | Remove a fresh `sms` row | No write, nothing journalled |
| US5, FR-008 | Web and API removal with `sms-notifications` off | 200, number erased; `GET .../sms` still 404 |
| US3.1, FR-010 | Second send after the wait | New hash, `proof_attempts` 0, full expiry, `proof_sent_count` + 1 |
| US3.2 | Old code after a new send | 422 on `code` |
| US3.3, FR-011 | `sendProofCode` 30 seconds after a send | Refused, reason says 30 seconds |
| US3.4, FR-012 | Send at the limit | Refused, reason says no more codes |
| Edge: race | Two sends with `proof_last_sent_at` set between `authorize()` and the write | One send |
| Policy | `NotificationChannelPolicyTest` | Permitted, each refusal, `nobody_is_signed_in` |
| FR-013 | View model on a refused resend | Refusal text present; controller test sees `aria-disabled` and `aria-describedby` |

## By hand

1. `php artisan monica:switch-flag sms-notifications on --email=<a test account>`, with Vonage test keys in `.env`.
2. Settings, Notifications: type a number, ask for a code, wait a minute, ask for another, type the
   first code (refused), then the second (proven).
3. Remove the text message setup: the warning names the number; confirm; the number field is back.
4. Connect Telegram, remove it: the warning names Telegram; the connect button is back.
5. Switch `sms-notifications` off with a number kept: the notice offers the removal.
