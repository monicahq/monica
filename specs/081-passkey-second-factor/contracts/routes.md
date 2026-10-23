# Contract: routes

Every JSON shape is the one `@laravel/passkeys` sends and expects, reached through its `routes` override.

## Security screen (`routes/web.php`, signed in, beside `settings.passkeys.update`)

| Method and URL | Controller | Name | Extra middleware |
| --- | --- | --- | --- |
| GET `settings/account/security/security-keys/options` | `SecurityKeyOptionsController@show` | `settings.securityKeyOptions.show` | `EnsurePasswordIsConfirmedWhenThereIsOne`, as passkey management |
| POST `settings/account/security/security-keys` | `SecurityKeyController@create` | `settings.securityKeys.create` | `EnsurePasswordIsConfirmedWhenThereIsOne`, as passkey management |
| PUT `settings/account/security/security-keys/{securityKey}` | `SecurityKeyController@update` | `settings.securityKeys.update` | `EnsurePasswordIsConfirmedWhenThereIsOne`, as passkey management |
| DELETE `settings/account/security/security-keys/{securityKey}` | `SecurityKeyController@destroy` | `settings.securityKeys.destroy` | `EnsurePasswordIsConfirmedWhenThereIsOne`, as passkey management |

- **options**: `200 {"options": PublicKeyCredentialCreationOptionsJSON}`; the options are kept in the session under `securityKey.registrationOptions`. `404` on an insecure connection.
- **create**: body `{"name": string, "credential": RegistrationResponseJSON}`. `201 {"id", "name", "redirect"}`, where `redirect` is the recovery codes screen when this key turned two-factor authentication on, the security screen otherwise. `422 {"message"}` for a credential already added or a response that does not verify.
- **update**: form field `name`, required, string, max 255. Redirect to the security screen with a status.
- **destroy**: redirect to the security screen with a status naming whether two-factor authentication is still on.
- `{securityKey}` belonging to somebody else: `404`, from the binding.
- POST `settings/account/security/recovery-codes` (existing) moves into the same group, so an account without a password, which a key can now protect, can still get new codes.

## Challenge (`routes/auth.php`, guest)

| Method and URL | Controller | Name | Middleware |
| --- | --- | --- | --- |
| GET `two-factor-challenge/key-options` | `TwoFactorKeyOptionsController@show` | `auth.twoFactorKeyOptions.show` | `throttle:12,1,two-factor-key-options`, a budget of its own so the prompt opened on load spends none of the code's |
| POST `two-factor-challenge` (existing) | `TwoFactorChallengeController@create` | `auth.twoFactor.create` | `throttle:6,1` (existing) |

- **key-options**: `200 {"options": PublicKeyCredentialRequestOptionsJSON}` with `allowCredentials` holding the security keys and passkeys of the person in `twoFactor.user.id`, user verification `discouraged`; kept in the session under `twoFactor.keyOptions`. No `twoFactor.user.id` in the session: `422 {"message": "That took too long. Please sign in again.", "redirect": <sign-in URL>}`.
- **create with `credential`**: body `{"credential": AuthenticationResponseJSON, "remember": bool}`; `remember` is ignored in favour of `twoFactor.remember`. `200 {"redirect": <intended URL>}` after the same sign-in completion as a code. A credential that is not the person's, does not verify, or replays a counter: `422 {"message"}` with the refusal sentence, and the person stays on the challenge.
- **create with `code`**: unchanged.

## API (`routes/api.php`)

`POST api/v1/login` keeps its request and responses. It now asks a key-only account for `code`, which only a recovery code can satisfy. No route lists or manages security keys (spec FR-019).
