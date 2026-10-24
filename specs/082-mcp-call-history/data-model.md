# Data Model: MCP Call History

## `mcp_calls` (model `App\Models\McpCall`, `HasUuids`, `MassPrunable`)

| Column | Type | Notes |
| --- | --- | --- |
| `id` | id | |
| `uuid` | uuid, unique | The call identifier the screen and the API show |
| `account_id` | `foreignIdFor(Account)`, cascade | |
| `user_id` | `foreignIdFor(User)`, cascade | |
| `connection_type` | string | `McpConnectionTypeEnum`: `api_key`, `oauth_client` |
| `connection_id` | string | Sanctum token id or Passport client id; no foreign key, survives revocation |
| `connection_name` | string | Snapshot at call time |
| `tool` | string | Name as the client sent it (`get-contact`) |
| `operation` | string, nullable | `McpOperationEnum`: `read`, `create`, `update`, `delete`; null for an unknown tool |
| `vault_id` | `foreignIdFor(Vault)`, nullable, `nullOnDelete` | Only when found inside the account |
| `vault_name` | string, nullable | Snapshot |
| `subject_type`, `subject_id` | string, nullable | Morph alias and key of the record found; no constraint |
| `subject_label` | string, nullable | Snapshot, used by the short description |
| `result` | string | `McpCallResultEnum`: `succeeded`, `refused`, `invalid_parameters`, `error`, `interrupted` |
| `error_code` | integer, nullable | JSON-RPC code |
| `error_message` | text, nullable | The reason sent to the client |
| `parameters` | text, nullable | Masked JSON, at most 16 384 bytes |
| `parameters_truncated` | boolean, default false | |
| `response` | text, nullable | Masked JSON, at most 16 384 bytes |
| `response_truncated` | boolean, default false | |
| `json_rpc_id` | string, nullable | Cast to string whether the client sent a number or a string |
| `ip_address` | string(45), nullable | |
| `started_at` | `dateTime(precision: 3)` | UTC |
| `duration_in_milliseconds` | unsigned integer, nullable | Null while interrupted |
| `created_at`, `updated_at` | timestamps | |

Indexes: (`account_id`, `started_at`), (`account_id`, `result`), (`account_id`, `tool`),
(`account_id`, `connection_type`, `connection_id`). All three drivers accept them.

## Lifecycle

```text
written (result = interrupted, duration null)
  -> closed once (result = succeeded | refused | invalid_parameters | error, duration set)
```

A row is updated exactly once, by `RecordedCallTool`. Nothing else writes it, and no route edits or
deletes one. `prunable()` selects rows with `started_at` older than the retention.

## Relations

- `Account::mcpCalls()` HasMany, `McpCall::account()`, `user()`, `vault()` BelongsTo.
- No relation to the connection: it can be gone.

## Enums (`app/Enums`)

`McpCallResultEnum`, `McpOperationEnum`, `McpConnectionTypeEnum`, each with a `label()` returning
translated copy, the way `ApiKeyScopeEnum` does. The result also gives the badge tone.

## Factory

`McpCallFactory` with states `refused()`, `invalidParameters()`, `failed()`, `interrupted()` and
`overOAuth()`, using The Office for labels (Jim Halpert, Dunder Mifflin).
