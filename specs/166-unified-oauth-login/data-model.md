# Data model: One OAuth sign-in for every client

Three migrations, `up()` only, as the migrations skill splits them: `rename_mobile_sessions_to_oauth_connections`
(table, `label`, nullable columns), `add_scopes_to_oauth_connections_table`, and
`backfill_oauth_connections` (query builder, idempotent).

## `oauth_connections` (was `mobile_sessions`)

Columns of `specs/165-mobile-app-sign-in/data-model.md`, with these changes:

| Column | Change |
| --- | --- |
| `device_name` | renamed `label` |
| `scopes` | new, JSON, nullable in the schema, cast to array; the backfill writes `["api"]` to every existing row |
| `access_token_id` | becomes nullable |
| `refresh_token_hash` | becomes nullable, stays unique |
| `authorization_code_hash` | becomes nullable, stays unique; null marks an earlier connection (R5) |

The backfill then inserts the earlier connections of assistants, one per `user_id` and `client_id`
that has none yet, found in `oauth_access_tokens` where the client is not the mobile one and the token, or
its refresh token, is neither revoked nor expired.

| Column | Value for an earlier connection |
| --- | --- |
| `scopes` | the scopes of the newest such token |
| `label`, `access_token_id`, the three hashes | null |
| `created_at` | the oldest such token's `created_at` ("Connected") |
| `last_used_at` | the newest such token's `created_at` |

- Active and prunable: unchanged rules, 30 days.
- Route binding: unchanged, scoped to the signed-in person.
- `User::mobileSessions()` becomes `User::oauthConnections()`.
- `OAuthConnection::name()`: the label, or else the client's name.

## `UserActionEnum`

| Case | Value | Label |
| --- | --- | --- |
| `OAuthConnectionCreation` | `oauth_connection_created` | Connected the app :name |
| `OAuthConnectionEnd` | `oauth_connection_ended` | Disconnected the app :name |

`:name` is the client's name, followed by the label in parentheses when there is one. The three
earlier cases stay (R13).
