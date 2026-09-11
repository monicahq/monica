# Data model

Rationale is in [research.md](research.md); requirements are in [spec.md](spec.md).

## `notification_channels` (new)

One row per account per channel. Replaces the three boolean columns so a new channel needs no
migration (D3).

| Column | Type | Note |
| --- | --- | --- |
| `id` | id | |
| `account_id` | unsignedBigInteger | FK `accounts`, cascade on delete |
| `channel` | string(32) | a `NotificationChannelEnum` value |
| `is_enabled` | boolean, default false | |
| `destination` | string, nullable | the room, the conversation or the number |
| `credential` | text, nullable | cast `encrypted:array`, so a channel needing a key and a secret fits |
| `proven_at` | timestamp, nullable | set when a code was typed back (FR-042) |
| `proof_code_hash` | string, nullable | hashed, never read back (D6) |
| `proof_expires_at` | timestamp, nullable | |
| `proof_attempts` | unsignedInteger, default 0 | wrong guesses, bounded by FR-045 |
| `proof_sent_count` | unsignedInteger, default 0 | resends, bounded by FR-045 |
| `proof_last_sent_at` | timestamp, nullable | |
| `consecutive_failures` | unsignedInteger, default 0 | reset on any success, threshold in FR-013 |
| `disabled_reason` | string, nullable | why the product switched it off |
| `created_at` / `updated_at` | timestamps | Unique on (`account_id`, `channel`) |

**States**: `absent` (no row) → `incomplete` (missing destination or credential) → `unproven` (only
where the channel proves silently) → `ready` → `enabled`. `disabled` is reachable from `enabled`
either by the account or by `consecutive_failures` passing the threshold, and keeps the row.

## `NotificationChannelEnum` (extended)

| Case | Value | Destination | Credential | Wrong destination | Limit | Laravel channel |
| --- | --- | --- | --- | --- | --- | --- |
| `InApp` | `in_app` | no | no | n/a | none | none, the row is the delivery |
| `Email` | `email` | the user's address | no | n/a | none | `mail` |
| `Push` | `push` | yes | yes | n/a | n/a | none, still undeliverable (D11) |
| `Slack` | `slack` | channel | bot token | refused loudly | 3000 | `slack` |
| `Telegram` | `telegram` | chat identifier | bot token | refused loudly | 4096 | `telegram` |
| `Sms` | `sms` | phone number | key and secret | **accepted silently** | 140 | `vonage` |

Methods: `label()`, `deliveredSentence()`, `needsDestination()`, `needsCredential()`,
`provesSilently()`, `maximumLength()`, `laravelChannel()`, `isDeliverable()`.
`preferenceColumn()` is removed with the columns it named.

## Edited

- **`notification_preferences`**: drops `in_app_is_enabled`, `email_is_enabled` and
  `push_is_enabled`. Keeps quiet hours, delivery mode, digest and timezone. Its backfill migration
  seeds `notification_channels` instead.
- **`notifications`**: no column changes. Gains `Prunable`, pruning on `created_at` older than
  `monica.notification_retention_months`. The "Nothing here is deleted" docblock is corrected.

## Config (`config/monica.php`)

`notification_retention_months` (12), `notification_channel_failure_threshold` (5),
`notification_proof_code_minutes` (10), `notification_proof_maximum_attempts` (5),
`notification_proof_maximum_sends` (3).
