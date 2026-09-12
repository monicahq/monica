# Contract: Sign in with an outside provider

Five routes and two configuration blocks. Nothing here reaches the JSON API, which gains nothing from this feature.

## Configuration

| Key | Source | Meaning |
| --- | --- | --- |
| `monica.sso_installed` | a `class_exists` per packaged provider, evaluated once in `config/monica.php` | the providers whose driver is there. GitHub, Google and Facebook always, Microsoft only when its package is. A test overrides the key to reach either state |
| `services.{provider}.client_id` | `{PROVIDER}_CLIENT_ID` | supplied by the operator, never by a user |
| `services.{provider}.client_secret` | `{PROVIDER}_CLIENT_SECRET` | the same |
| `services.{provider}.redirect` | `{PROVIDER}_REDIRECT_URI` | where the provider sends the person back |

A provider is offered when it appears in `sso_installed` and holds all three credentials. `SsoProviderAvailability` answers both halves. Failing either and it does not exist on that instance: no control, no route, no string.

## Guest routes, `routes/auth.php`

Inside the existing `guest` group, behind a middleware that answers not found while no provider is available.

| Verb | URL | Name | Answers |
| --- | --- | --- | --- |
| GET | `sso/{provider}/redirect` | `auth.sso.redirect` | a redirect to the provider, or not found when that provider is not available |
| GET | `sso/{provider}/callback` | `auth.sso.callback` | a redirect to the vaults, to the two factor challenge, or back to the sign in screen with an error |

`{provider}` is constrained to the enum's values, so an unknown one is not found before anything runs. Both are rate limited at 6 a minute, the rate the magic link and the password reset already use.

### What the callback answers

| Case | Result |
| --- | --- |
| the pair matches a user, active, no two factor | signed in, redirected to the vaults |
| the pair matches a user with two factor | `twoFactor.user.id` put in the session, redirected to the challenge |
| the pair matches a user who is deactivated | back to the sign in screen, refused, the way every other route refuses one |
| the address is unknown here | an account is created, verified, signed in |
| the address belongs to a user who signs in another way | back to the sign in screen, told the address is in use and to connect it from inside |
| the provider returns no verified address | back to the sign in screen, refused |
| cancelled, failed, or a callback nobody asked for | back to the sign in screen, told it did not work, nothing created |

## Signed in routes, `routes/web.php`

Inside the existing `auth` group beside the passkey routes, behind the same availability middleware, and rate limited at 6 a minute like the guest routes.

| Verb | URL | Name | Answers |
| --- | --- | --- | --- |
| GET | `settings/account/security/sso/{provider}/redirect` | `settings.sso.redirect` | a redirect to the provider |
| GET | `settings/account/security/sso/{provider}/callback` | `settings.sso.callback` | back to the security screen, connected or refused |
| DELETE | `settings/account/security/sso` | `settings.sso.destroy` | back to the security screen, disconnected or refused |

### What connecting and disconnecting answer

| Case | Result |
| --- | --- |
| the user holds no provider and the pair is free | connected, named on the security screen, and every other way in untouched |
| the pair already belongs to another user | refused, nothing changes on either account |
| the user already holds a provider | refused, disconnect first |
| disconnecting with a password or a passkey left | disconnected, after a warning, and `SignInProviderDisconnected` is sent |
| signing in with a password while a provider is connected | signed in. The refusal `AttemptLogin` applies today is removed |
| disconnecting with nothing else left | refused, told what to set up first |
