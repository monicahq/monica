# Data Model: Sign in from the mobile apps

## `mobile_sessions` (new table, model `MobileSession`, `Prunable`)

| Column | Type | Rules |
| --- | --- | --- |
| `id` | bigint, primary | route binding scoped to the signed-in user, like `SecurityKey` |
| `user_id` | `foreignIdFor(User::class)->constrained()->cascadeOnDelete()` | |
| `client_id` | `foreignIdFor(Client::class)->constrained()->cascadeOnDelete()` | always `MobileSession::CLIENT_ID` today; iOS may add another |
| `device_name` | string(255), nullable | cleaned with `TextSanitizer::plainText`; shown as "Unnamed phone" when null |
| `access_token_id` | string(80), index | `jti` of the current access token; no foreign key (`passport:purge`) |
| `refresh_token_hash` | char(64), unique | SHA-256 hex of the current refresh token |
| `previous_refresh_token_hash` | char(64), nullable, index | replaced refresh token, for replay detection |
| `authorization_code_hash` | char(64), unique | SHA-256 hex of the exchanged code |
| `last_used_at` | datetime | creation, then every renewal |
| `ended_at` | datetime, nullable | set by `EndMobileSession` only |
| `created_at`, `updated_at` | timestamps | `created_at` is "signed in" |

- Active: `ended_at` is null and `last_used_at` is within the last 30 days (`scopeActive`).
- Prunable: `ended_at` is not null, or `last_used_at` is more than 30 days ago.
- Each column carries a `->comment()`, like the other migrations.

## `oauth_clients`: one row, no schema change

| Column | Value |
| --- | --- |
| `id` | `MobileSession::CLIENT_ID` (fixed UUID) |
| `name` | `Monica for mobile` |
| `secret`, `provider`, `owner` | null |
| `redirect_uris` | `["com.monicahq.android:/oauth/callback"]` (`MobileSession::REDIRECT_URI`) |
| `grant_types` | `["authorization_code","refresh_token"]` |
| `revoked` | false |

## `UserActionEnum`: two cases

| Case | Value | Label |
| --- | --- | --- |
| `MobileSessionCreation` | `mobile_session_created` | Signed in from the mobile app on :name |
| `MobileSessionEnd` | `mobile_session_ended` | Ended the mobile app session on :name |
