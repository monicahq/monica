# Contract: OAuth for the mobile apps

`{base}` is `APP_URL`, path included. What the app sends and expects is fixed in `monicahq/android`,
`specs/002-multi-instance-auth/contracts/monica-server.md`. This file states only what Monica answers.

## `GET {base}/.well-known/oauth-authorization-server` (throttle 60/min)

It returns the members MCP serves today, unchanged (`issuer`, `authorization_endpoint`,
`token_endpoint`, `registration_endpoint`, `response_types_supported`, `grant_types_supported`,
`code_challenge_methods_supported: ["S256"]`, `token_endpoint_auth_methods_supported`), plus:

```json
{
  "revocation_endpoint": "{base}/oauth/revoke",
  "revocation_endpoint_auth_methods_supported": ["none"],
  "scopes_supported": ["mcp:use"],
  "prompt_values_supported": ["none", "login", "consent", "create"]
}
```

## `GET {base}/oauth/authorize` (Passport + `BindOAuthScopes`, throttle 30/min)

| Request | Answer |
| --- | --- |
| mobile client, `scope=api`, S256 challenge, exact redirect URI | consent page, always (`prompt` gains `consent` and loses `none`) |
| mobile client, `scope` holds `mcp:use` | redirect with `error=invalid_scope` |
| any other client, `scope` holds `api` | redirect with `error=invalid_scope` |
| guest, `prompt` holds `create` | login → registration (or a notice when closed) → consent (R11) |
| Allow / Deny | `{redirect_uri}?code=…&state=…` / `?error=access_denied&state=…` |

## `POST {base}/oauth/token` (Passport + `BindOAuthScopes` + `TrackMobileSessions`)

- Code exchange (`device_name` optional) or renewal: `200 {token_type, expires_in: 3600, access_token, refresh_token}`.
- Errors per RFC 6749 §5.2, mostly `invalid_grant`.
- The client is read from `client_id` in the body, or else from HTTP Basic authentication.
- A code exchanged again, or a previous refresh token presented again more than 30 seconds after its renewal, ends its session, then `invalid_grant`. Within 30 seconds it is a second request sent at once: `invalid_grant`, and the session goes on.

## `POST {base}/oauth/revoke` (new, throttle 30/min)

- Form fields: `token` (required), `token_type_hint`, `client_id`.
- A matching active session of that client is ended (`EndMobileSession`, from the phone).
- Always `200` with an empty body, `400 {"error":"invalid_request"}` without `token`.
