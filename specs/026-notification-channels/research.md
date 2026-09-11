# Research: More ways for a notification to reach somebody

Decisions taken before design. The specification says what; this says why, once.

| # | Decision | Rationale | Rejected |
| --- | --- | --- | --- |
| D1 | Every fact about a channel lives on `NotificationChannelEnum` | The enum already carries `preferenceColumn()` and `isDeliverable()`, so this is the existing pattern extended, and it adds no folder to a fixed layout | A `ChannelContract` with one class per service: six small classes and a resolver to say what a `match` already says (Principle II) |
| D2 | Delivery hands off to Laravel's own notification system, one Laravel channel per case | Principle IV. `toMail`, `toSlack`, `toTelegram` and `toVonage` are documented, maintained and already how `ReminderDue` sends mail | Writing HTTP clients per service by hand |
| D3 | Per-account channel settings become rows in `notification_channels`, replacing three boolean columns | FR-026 wants a new channel to need no migration. A column per channel needs one every time | Widening `notification_preferences` to six columns, then seven |
| D4 | Retention through Laravel's `Prunable` trait | `model:prune` is already scheduled daily at 01:05, so retention costs no new schedule entry. No model uses `Prunable` yet, so this sets the pattern | A bespoke job and a bespoke schedule entry |
| D5 | Credentials use the `encrypted` cast | Precedent in `User::two_factor_secret`. Satisfies FR-012 without new machinery | Plain text; a separate secrets table |
| D6 | The proof code is stored hashed, with an expiry, an attempt count and a resend count, on the channel row | It is verified, never read back, so hashing beats encryption. Folding it onto the row avoids a table holding at most one live row per channel (Principle II) | A `notification_channel_proofs` table |
| D7 | `ReminderDelivery`, `ReminderHistory` and `ReminderRow` stop matching on the channel and call `$channel->label()` | Three exhaustive `match` blocks over the same enum is exactly what FR-026 forbids: a seventh channel would need edits in three screens | Adding an arm to each of the three |
| D8 | Failure is classified temporary or permanent from what the service returns; permanent abandons at once | FR-029. Retrying a revoked token three times wastes two attempts and delays nothing useful | Treating every failure as retryable, which is what mail does today |
| D9 | The bar is one anonymous Blade component rendered inside `x-bar-links` | `x-bar-links` is already included by both `layouts/shell.blade.php` and `layouts/account.blade.php`, so one insertion covers every signed-in screen (FR-030) | Editing each layout, and forgetting one |
| D10 | The digest is produced per channel, keyed by channel on the summary row | FR-006. `SendNotificationDigest` currently hard-codes `NotificationChannelEnum::Email` in both the query and the row it writes | One summary carrying every channel, which cannot record what happened to each |
| D11 | Push keeps its case and stays undeliverable | The product committed to it (§3.8) and this feature only stops it being a switch that silently cannot work | Removing the case, which loses stored values on existing rows |

## Consequences to carry into design

- `app/Models/Notification.php` documents "Nothing here is deleted." D4 makes that false. The
  docblock is corrected in this feature, not left to rot.
- `NotificationPreference::allows()`, `enabledChannels()` and `preferenceColumn()` all disappear with
  D3, along with their tests. `NotificationPreference` keeps quiet hours, delivery mode, digest and
  timezone only.
- The `notifications_told_once_unique` index already includes `channel`, so more channels need no
  index change.
- `DeliverNotification::mayGoOut()` already refuses a notification whose channel cannot deliver only
  by never having written one. With D3 the check moves to whether the account's channel row is
  enabled and set up (FR-021).
- Vonage pulls a large tree (`vonage/client-core`, `lcobucci/jwt`, `laminas/laminas-diactoros`). If
  the dependency budget matters, text message is the channel to stage last.

## Verified, not assumed

- All three packages resolve against `laravel/framework ^13.8`, checked with `composer require
  --dry-run`: Slack `v3.10.0`, Vonage `v3.3.4`, Telegram `^8.0`.
- 31 locales are configured, so every new string is 31 translations.
- `routes/api.php` and `app/Http/Controllers/Api/` exist, so Principle X applies now rather than
  later. The CLAUDE.md note saying there is no API surface is stale.
