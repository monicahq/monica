# Implementation Plan: Telegram Bot Setup From the Configuration Screen

**Branch**: `2026-09-26-telegram-bot-setup` | **Date**: 2026-09-26 | **Spec**: [spec.md](spec.md)

## Summary

Move the webhook registration that `monica:setup-telegram` performs into an action, and put it
behind a Telegram panel on the admin configuration screen, together with a read-only check, a secret
generator and a registration state derived from a one-row record. Fix the incoming address so it
exists whatever the token's source (research R1) and refuses a blank secret (R2).

## Technical Context

| | |
| --- | --- |
| Language/Version | PHP 8.4, Laravel 13 |
| Primary Dependencies | Laravel HTTP client; `laravel-notification-channels/telegram` stays optional |
| Storage | one new table, `telegram_webhook_registrations` ([data-model.md](data-model.md)) |
| Testing | PHPUnit, `Http::fake()`, `RefreshDatabase` |
| Target Platform | the shipped Docker image, routes cached in production |
| Project Type | server-rendered web application (Blade, Alpine) |
| Performance Goals | a registration answers in under 15 seconds (research R5) |
| Constraints | no secret or token in clear anywhere but `instance_settings`; saving never calls Telegram |
| Scale/Scope | one panel, three routes, one console command, one table |

No open unknowns: [research.md](research.md) settles R1 to R9.

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. `RegisterTelegramWebhook`, `CheckTelegramWebhook`, `GenerateTelegramWebhookSecret`; the command and three thin controllers call them |
| II. Simplicity | Pass. No service class, no policy (R4); one helper method holds the prerequisite rule |
| III. Tested | Pass. Every new class has a test (see Structure) |
| IV. Laravel conventions | Pass. HTTP client, `up()` only migration, camelCase route names |
| V. Localized, warns first | Pass. New copy through `__()` and `monica:localize`; replacing the token or the secret warns while a registration is current (R7) |
| VI, VII | Not concerned |
| VIII. Nothing disappears silently | Pass. The panel says "out of date" and why instead of breaking quietly |
| IX. Accessible | Pass. An unavailable control stays, `aria-disabled` with `aria-describedby` to its reason; results announced with `role="status"` / `role="alert"` |
| X. Through the API | **Deviation**, see Complexity Tracking |
| XI. Short record | **Deviation**: `spec.md` is about 260 lines against 120, see Complexity Tracking |

Re-checked after Phase 1: unchanged.

## Project Structure

```text
app/
├── Actions/RegisterTelegramWebhook.php          new, from SetupTelegramCommand
├── Actions/CheckTelegramWebhook.php             new, read-only
├── Actions/GenerateTelegramWebhookSecret.php    new, delegates to UpdateInstanceSetting
├── Actions/UpdateInstanceSetting.php            administrator becomes ?User
├── Console/Commands/SetupTelegramCommand.php    calls RegisterTelegramWebhook
├── Enums/AdministratorActionEnum.php            + TelegramWebhookRegistered
├── Enums/InstanceSettingEnum.php                warning + 'replaced' for token and secret
├── Http/Controllers/App/Admin/TelegramRegistrationController.php        new
├── Http/Controllers/App/Admin/TelegramRegistrationCheckController.php   new
├── Http/Controllers/App/Admin/TelegramWebhookSecretController.php       new
├── Http/Controllers/App/Settings/Account/Notifications/TelegramWebhookController.php  404, blank secret
├── Helpers/TelegramWebhook.php                  new, prerequisites, fingerprints, out-of-date reasons
├── Models/TelegramWebhookRegistration.php       new
├── ViewModels/Admin/ConfigurationViewModel.php  telegram(), warning blanked when not current
└── ViewModels/Admin/TelegramRegistrationPanel.php  new, the panel's state and copy
database/migrations/2026_09_26_000000_create_telegram_webhook_registrations_table.php
database/factories/TelegramWebhookRegistrationFactory.php
resources/views/app/admin/configuration/index.blade.php    includes the panel; 'replaced' mode
resources/views/app/admin/configuration/_telegram.blade.php  new
routes/web.php                                   three routes, webhook route unconditional on token
lang/*.json                                      through monica:localize
tests/Unit/Actions/{RegisterTelegramWebhook,CheckTelegramWebhook,GenerateTelegramWebhookSecret}Test.php
tests/Unit/Helpers/TelegramWebhookTest.php
tests/Unit/ViewModels/Admin/TelegramRegistrationPanelTest.php
tests/Unit/Console/Commands/SetupTelegramCommandTest.php
tests/Feature/Controllers/App/Admin/{TelegramRegistration,TelegramRegistrationCheck,TelegramWebhookSecret}ControllerTest.php
tests/Feature/Controllers/App/Settings/Account/Notifications/TelegramWebhookControllerTest.php  extended
(RouteProtectionTest covers the new routes on its own; AdminAccessTest lists GET screens only)
```

**Structure Decision**: the existing Laravel layout. The panel is an `@include` partial fed by a
small object next to the main view model, the way `MarketplaceStatistics` feeds the marketplace.
Routes and responses are in [contracts/routes.md](contracts/routes.md); validation steps in
[quickstart.md](quickstart.md).

## Complexity Tracking

| Deviation | Why | Simpler alternative rejected because |
| --- | --- | --- |
| X: no API endpoint | the configuration screen has none; spec 036 removed it on the author's instruction | adding one for three buttons would reopen that decision alone |
| XI: `spec.md` over its cap | written before the cap was checked; the clarifications added to it | trimming is owed before the pull request, or the pull request states it |
