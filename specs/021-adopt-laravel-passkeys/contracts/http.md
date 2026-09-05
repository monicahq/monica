# HTTP Contract

What the browser talks to before and after. FR-010 moves management and sign-in onto the package's
routes; two of ours survive because the package has no equivalent.

## Adopted from the package

Registered by `PasskeysServiceProvider` under the `web` group. Route names are the package's own,
kebab-cased with a dot, which the project's camelCase convention would not have chosen; see
Complexity Tracking in [plan.md](../plan.md).

| Method | URI | Name | Middleware |
| --- | --- | --- | --- |
| GET | `passkeys/login/options` | `passkey.login-options` | `guest:web`, `throttle:passkey` |
| POST | `passkeys/login` | `passkey.login` | `guest:web`, `throttle:passkey` |
| GET | `user/passkeys/options` | `passkey.registration-options` | `auth:web`, `throttle:passkey` |
| POST | `user/passkeys` | `passkey.store` | `auth:web`, `throttle:passkey` |
| DELETE | `user/passkeys/{passkey}` | `passkey.destroy` | `auth:web` |

`management_middleware` is set to `[]`, which strips `password.confirm` from the last three (FR-007).
The two `passkeys/confirm/*` routes are registered too and go unused; nothing in this application
asks a signed-in person to re-prove a passkey.

Responses are JSON when asked for. Sign-in returns `{"redirect": …}`, registration
`{"status": "passkey-registered", "id", "name"}`, deletion `{"status": "passkey-deleted"}`. The
sign-in and deletion responses are rebound (research D7); the others are used as they ship.

## Kept

| Method | URI | Name | Why |
| --- | --- | --- | --- |
| PUT | `settings/account/security/passkeys/{passkey}` | `settings.passkeys.update` | the package has no rename route |
| GET | `register/passkey` | `auth.registerPasskey.new` | the package registers a passkey only for somebody already signed in |
| POST | `register/passkey` | `auth.registerPasskey.create` | as above: the account must exist before the ceremony |

## Removed

Everything the old package served under the `passkeys/` prefix, from `config('webauthn.prefix')`.
`bootstrap/app.php` exempts that prefix from CSRF-related handling by reading the same config key and
must be repointed at the new paths.

## Browser

`@laravel/passkeys` defaults to exactly the four ceremony URIs above, so no route override is passed.
`Passkeys.verify({remember})` drives sign-in, `Passkeys.register({name})` drives enrolment, and both
reject with typed errors the Alpine component maps to announcements.
