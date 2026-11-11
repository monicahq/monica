# Contract: what an operator supplies

Every provider is addressed by one name, which is its URL segment, its stored value and its
`services.*` key.

## Addresses

| Purpose | Address |
| --- | --- |
| Start signing in | `GET /sso/{name}/redirect` |
| Return from the provider | `GET /sso/{name}/callback` (the address to register at the provider) |
| Start connecting | `GET /settings/account/security/sso/{name}/redirect` |
| Disconnect | `DELETE /settings/account/security/sso/{name}` |

`{name}` is one of `github`, `google`, `microsoft`, `facebook`, `keycloak`, `auth0`, `okta`,
`openidconnect`. The routes exist already; only the last four names are new. A name that is not
configured answers not found.

## Settings

Each line is an environment variable and an instance setting of the admin space, group Single sign
on. A stored setting overrides the environment. Every provider also reads `{PREFIX}_REDIRECT_URI`
from the environment only, defaulting to its callback path.

| Provider | Variable | Kind | Required |
| --- | --- | --- | --- |
| Microsoft | `MICROSOFT_CLIENT_ID` | Text | Yes (exists) |
| | `MICROSOFT_CLIENT_SECRET` | Secret | Yes (exists) |
| | `MICROSOFT_TENANT` | Text | No, `common` |
| Keycloak | `KEYCLOAK_SERVER_URL` | Url | Yes |
| | `KEYCLOAK_REALM` | Text | Yes |
| | `KEYCLOAK_CLIENT_ID` | Text | Yes |
| | `KEYCLOAK_CLIENT_SECRET` | Secret | Yes |
| Auth0 | `AUTH0_DOMAIN` | Text | Yes |
| | `AUTH0_CLIENT_ID` | Text | Yes |
| | `AUTH0_CLIENT_SECRET` | Secret | Yes |
| Okta | `OKTA_DOMAIN` | Text | Yes |
| | `OKTA_AUTH_SERVER` | Text | No |
| | `OKTA_CLIENT_ID` | Text | Yes |
| | `OKTA_CLIENT_SECRET` | Secret | Yes |
| Generic | `OIDC_BASE_URL` | Url | Yes |
| | `OIDC_NAME` | Text | No, "OpenID Connect" |
| | `OIDC_CLIENT_ID` | Text | Yes |
| | `OIDC_CLIENT_SECRET` | Secret | Yes |

A provider is offered when every required line is filled. Sixteen of the eighteen are new.

## What the provider must do

- Serve a discovery document at `{address}/.well-known/openid-configuration`, over https (plain http
  is accepted on the local machine only).
- Sign its identity tokens with a key it publishes.
- Share an email address. For Microsoft that is the sign in name, when it has the shape of an
  address.
