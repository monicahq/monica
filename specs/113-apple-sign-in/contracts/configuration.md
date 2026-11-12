# Contract: what an operator supplies for Apple

## Package

```bash
composer require socialiteproviders/apple
```

Without it Apple is absent from every screen, whatever is supplied below.

## Settings

| Environment variable | Instance setting | Config key | Kind | Required |
| --- | --- | --- | --- | --- |
| `APPLE_CLIENT_ID` | `apple_client_id` | `services.apple.client_id` | text | yes. The Services ID |
| `APPLE_TEAM_ID` | `apple_team_id` | `services.apple.team_id` | text | yes, unless a secret is supplied |
| `APPLE_KEY_ID` | `apple_key_id` | `services.apple.key_id` | text | yes, unless a secret is supplied |
| `APPLE_PRIVATE_KEY` | `apple_private_key` | `services.apple.private_key` | secret | yes, unless a secret is supplied. The text of the `.p8` key on one line or several, or the path to the file |
| `APPLE_CLIENT_SECRET` | `apple_client_secret` | `services.apple.client_secret` | secret | no. A secret generated in advance, used only when no key is supplied |
| `APPLE_REDIRECT_URI` | none | `services.apple.redirect` | | no. Defaults to `/sso/apple/callback` |

With the key, the secret is built for each request and never expires for the operator. A secret
generated in advance lasts six months at most and is renewed by whoever generated it.

## Addresses

| Address | Method | Who calls it |
| --- | --- | --- |
| `/sso/apple/redirect` | GET | The reader, from the sign in and registration screens |
| `/settings/account/security/sso/apple/redirect` | GET | The reader, from the security settings |
| `/sso/apple/callback` | POST | Apple. This is the return URL to register at Apple. It answers 303 to the same address |
| `/sso/apple/callback` | GET | The reader's browser, following that answer |

Apple accepts a return URL only on a public domain served over HTTPS.
