# Contract: JSON API and web routes

## Authentication on `/api/v1`

- `Authorization: Bearer` takes an API key or a Passport access token carrying `api`.
- A Passport token without `api` (an assistant's) gets `403`.
- A mobile token has the rights of a key without scopes (R3).

## `GET /api/v1/status` (existing, gains `mobile`)

```json
"mobile": {
  "api_version": 1,
  "client_id": "<MobileSession::CLIENT_ID>",
  "scope": "api",
  "registration_enabled": true,
  "minimum_android_version": null
}
```

## `DELETE /api/v1/logout` (existing)

- With an API key: unchanged.
- With a mobile token: ends its session, `204`.

## `GET /api/v1/mobile-sessions`, `GET /api/v1/mobile-sessions/{mobileSession}` (new)

`MobileSessionResource`, paginated like `api-keys`, newest first:

```json
{ "type": "mobile_session", "id": "12",
  "attributes": { "device_name": "Pixel 9", "is_current": true,
    "last_used_at": 1756339200, "created_at": 1756252800, "updated_at": 1756339200 },
  "links": { "self": "{base}/api/v1/mobile-sessions/12" } }
```

- Only active sessions; `is_current` is true for the session of the calling mobile token.

## `DELETE /api/v1/mobile-sessions/{mobileSession}` (new)

- Ends that session (`EndMobileSession`): `204`.
- `404` when the session is not one of the caller's, or has already ended.

## Web

| Route | Name |
| --- | --- |
| `DELETE settings/account/security/mobile-sessions/{mobileSession}` | `settings.mobileSessions.destroy` |

- Same rules as the API.
- Back to the security page with "The session on {device} has ended."
