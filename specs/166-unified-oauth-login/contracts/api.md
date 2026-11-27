# Contract: what changes for clients

Everything in `specs/165-mobile-app-sign-in/contracts/` stands except what is listed here.

## Authorization server

| Request | Before | After |
| --- | --- | --- |
| Any client, already allowed | assistants approved silently | consent page, always |
| `prompt=none` | honoured for assistants | dropped, consent page |
| `scope` holds `mcp:use`, MCP server off for the account | not found for assistants | not found, whoever asks |
| `.well-known/oauth-authorization-server` | `prompt_values_supported` holds `none` | `["login", "consent", "create"]`, nothing else changes |
| `POST /oauth/revoke` with an assistant's refresh token | 200, nothing ends | 200, its connection ends; nothing ends for an assistant connected before this change (R5) |
| Token request with `device_name`, any client | read for the mobile client only | stored as the label |

## `GET /api/v1/oauth-connections`, `GET /api/v1/oauth-connections/{oauthConnection}` (new)

`OAuthConnectionResource`, paginated like `api-keys`, newest first, active connections only:

```json
{ "type": "oauth_connection", "id": "12",
  "attributes": { "client_name": "Monica for mobile", "label": "Pixel 9", "scopes": ["api"],
    "is_current": true, "last_used_at": 1756339200, "created_at": 1756252800, "updated_at": 1756339200 },
  "links": { "self": "{base}/api/v1/oauth-connections/12" } }
```

## `DELETE /api/v1/oauth-connections/{oauthConnection}` (new)

`204`; `404` when it is not one of the caller's or has already ended.

## Removed

| Address | Replaced by |
| --- | --- |
| `GET`, `DELETE /api/v1/mobile-sessions…` | `oauth-connections`, noted in `resources/docs/api-changelog` |
| `DELETE settings/account/security/mobile-sessions/{mobileSession}` | `DELETE settings/account/security/connections/{oauthConnection}`, `settings.oauthConnections.destroy` |
| `DELETE settings/account/security/assistants/{client}` | the same |

`DELETE /api/v1/logout` and `GET /api/v1/status` answer as before.
