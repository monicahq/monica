# Contract: the magic link setting and the routes behind it

## The setting

| | |
| --- | --- |
| Config key | `monica.magic_link_enabled` |
| Environment variable | `MONICA_MAGIC_LINK_ENABLED` |
| Default | on, when the variable is absent |
| Reads as off | `false`, `0`, an empty value |
| Reads as on | anything else, including a value that reads as neither |
| Read by | `EnsureMagicLinkIsEnabled` for the routes, `LoginViewModel::magicLinkIsEnabled()` for the screen |

## The routes

All three sit in the `guest` group and, after this, in a `magic.link` group inside it.

| Name | Method and URL | Route middleware | Setting on | Setting off |
| --- | --- | --- | --- | --- |
| `auth.magicLink.new` | `GET send-magic-link` | none | 200, the request screen | 404 |
| `auth.magicLink.create` | `POST send-magic-link` | `throttle:6,1` | 200, "Check your inbox", the same for any address | 404, no email sent |
| `auth.magicLink.show` | `GET magic-link/{token}` | `signed`, `throttle:6,1` | 302 to home, or to the two factor challenge | 404, the link is not spent |

`magic.link` runs before `signed`, so an off instance answers 404 whether or not the signature holds.
`throttle` runs before both by framework priority, which changes nothing about the answer.

## The sign in screen

| Setting | The box "Sign in without a password? Send me a link instead" |
| --- | --- |
| on | shown, linking to `auth.magicLink.new` |
| off | absent |

The block offering `Sign in as Monica Geller` is gone whatever the setting.

## Removed

| Name | Method and URL | Registered by | After |
| --- | --- | --- | --- |
| `loginLinkLogin` | `POST laravel-login-link-login` | `Spatie\LoginLink\LoginLinkServiceProvider` | 404, no route |

## Unchanged

`monica.magic_link_duration_minutes`, the link's single use and expiry, the two factor redirect, the
throttle, the neutral answer for an unknown, inactive or disposable address, `MagicLinkCreated`,
`CreateMagicLink`, `ConsumeMagicLink`, and `ExampleAccountSeeder`.
