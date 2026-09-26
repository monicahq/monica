# Data Model: Telegram Bot Setup

## `telegram_webhook_registrations` (new)

The last successful registration of the bot for this instance. At most one row: registering again
updates the existing row instead of adding one.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | id | primary key |
| `user_id` | unsigned big integer, nullable | who registered; null when the console did. Foreign key to `users`, null on delete |
| `url` | string | the incoming address sent to Telegram |
| `token_fingerprint` | string | one-way hash of the token in effect (research R3) |
| `secret_fingerprint` | string | one-way hash of the secret in effect |
| `registered_at` | datetime | when Telegram accepted it |
| `created_at`, `updated_at` | timestamps | |

Migration `2026_09_26_000000_create_telegram_webhook_registrations_table.php`, `up()` only, foreign
key declared at the bottom, no `constrained()`.

Model `App\Models\TelegramWebhookRegistration`, with factory. `user()` belongs to `User`. No logic of
its own: the helper `App\Helpers\TelegramWebhook` computes what follows.

## State derived for the screen

Computed by `TelegramWebhook` from the current configuration, never stored.

| State | When |
| --- | --- |
| unavailable | `missingPrerequisite()` returns a reason; the panel shows it |
| not registered | no row |
| out of date | a row, and at least one of: `url` differs from the current incoming address, the token fingerprint differs, the secret fingerprint differs |
| registered | a row and none of the above |

`TelegramWebhook::outOfDateReasons(TelegramWebhookRegistration): list<string>` names which of the three changed, so the screen can say why
(FR-009).

## Changes to existing structures

| Where | Change |
| --- | --- |
| `AdministratorActionEnum` | new case `TelegramWebhookRegistered` (parameters: `url`) |
| `InstanceSettingEnum` | `warning()` and `warnsWhen(): 'replaced'` for `TelegramBotToken` and `TelegramBotWebhookSecret` |
| `UpdateInstanceSetting` | `$administrator` becomes `?User` (research R8) |

No other table changes. The three Telegram settings stay where spec 036 put them.
