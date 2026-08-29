# HTTP contract: WebAuthn Authentication

**Feature**: `002-webauthn-auth` | **Date**: 2026-08-25

Most of this surface is the package's, used as it ships, which is what
`asbiin/laravel-webauthn-example` does. `Webauthn::ignoreRoutes()` is **not** called. Two routes are
this project's own, both about the password, because nothing in the package covers removing one.

Authentication underneath every route here runs through the `webauthn` provider driver, set on the
`users` provider in `config/auth.php`. That is what resolves a credential to its owner, so no route
takes an email address and none needs to.

## The package's routes

Registered by the package at `config('webauthn.prefix')`, which this project sets to `passkeys` so
the public URLs read in the product's vocabulary. The route names stay `webauthn.*`, because the
package owns them and renaming is not offered.

| Verb | URL | Name | Used for |
|---|---|---|---|
| GET | `passkeys/auth` | `webauthn.login` | the sign in screen, and the assertion options as JSON |
| POST | `passkeys/auth/options` | `webauthn.auth.options` | the assertion challenge |
| POST | `passkeys/auth` | `webauthn.auth` | verify the assertion and sign in |
| GET | `passkeys/keys/create` | `webauthn.create` | the registration step |
| POST | `passkeys/keys/options` | `webauthn.store.options` | the attestation challenge |
| POST | `passkeys/keys` | `webauthn.store` | verify the attestation and store the passkey |
| PUT | `passkeys/keys/{id}` | `webauthn.update` | rename a passkey |
| DELETE | `passkeys/keys/{id}` | `webauthn.destroy` | remove a passkey |
| POST | `passkeys/confirm-key` | `webauthn.key.confirm` | not used by this feature, left alone |

### What this project supplies to them

**Screens**, through two response classes registered in `AppServiceProvider::register()`:

```php
Webauthn::loginViewResponseUsing(PasskeyLoginViewResponse::class);
Webauthn::registerViewResponseUsing(PasskeyRegisterViewResponse::class);
```

Both extend the package's base response and read `$this->publicKey` from it. Following the example,
each answers a JSON request with the options and a browser request with a rendered screen, so the
same route serves the Alpine component and the person.

**Policy**, through the pipeline:

```php
Webauthn::authenticateThrough(fn () => [
    EnsureLoginIsNotThrottled::class,
    EnsurePasskeyLoginIsAllowed::class,
    AttemptToAuthenticate::class,
    PrepareAuthenticatedSession::class,
]);
```

`EnsurePasskeyLoginIsAllowed` runs before `AttemptToAuthenticate`, so a refused user never reaches
the assertion check. It resolves the candidate through the provider, hands it to
`RefusePasskeyLogin`, and that action decides. The refusals, all reported with the message
`AttemptLogin` already uses, `These credentials do not match our records.`:

| Refusal | Requirement |
|---|---|
| No such credential registered here | FR-013 |
| The user is not `is_active` | FR-013 |
| The user is soft deleted, so the provider resolves null | FR-013 |
| The user has an `sso_provider` | FR-013 |

Every one of those is byte identical, so nothing distinguishes an unregistered credential from a
deactivated account (SC-004). The one exception is a credential bound to a former instance address,
which gets its own message because the person can act on it (FR-041).

**Reaction**, through listeners, which are auto-discovered in `app/Listeners`:

| Event | Listener | Does |
|---|---|---|
| `AuthenticatorAssertionResponseValidationSucceededEvent` | `RecordPasskeyUse` | writes `used_at` |
| `WebauthnLogin` | `HandlePasskeyLogin` | `last_login_at`, `CheckLastLogin`, `LogUserAction`, `NewLoginDetected` |
| `WebauthnRegister` | `HandlePasskeyRegistration` | `LogUserAction`, `PasskeyAdded` |
| `WebauthnRegisterFailed` | `HandlePasskeyRegistrationFailure` | `PasskeyCounterAnomaly` where the counter is why |

A passkey sign in does **not** put the user through the two factor challenge, whatever
`two_factor_confirmed_at` says (FR-020). Nothing in the pipeline adds that stage, which is the whole
of the implementation: the magic link's challenge lives in `MagicLinkController::show` and is
untouched (FR-022).

### Rate limiting

`config('webauthn.limiters.login')` names a limiter registered in `AppServiceProvider`, the way the
example does it. Five attempts a minute, keyed on the credential id and the IP rather than an email
and IP, because a passkey sign in has no email (FR-014). Whether that key can be shaped inside
`EnsureLoginIsNotThrottled` or has to move into `EnsurePasskeyLoginIsAllowed` is an open item in
research section 6, settled in step 1.

### Removing and renaming

`webauthn.destroy` and `webauthn.update` are the package's and scope to the signed in user, which is
what FR-036 needs. The warning FR-031 requires lives on the security screen, and the confirmation is
what submits the request.

`DestroyPasskey` is still an action, and it is called before the delete rather than after: the
activity log entry needs the row in hand, because `LogUserAction` takes its loggable at construction
precisely so a deletion can be recorded. FR-033, the case of a passwordless account removing its
last passkey, is warned on the screen and not refused here, because refusing would leave somebody
with a passkey they cannot revoke.

## This project's routes

Two, in `routes/web.php`, inside the existing group:

```php
Route::middleware(['auth', 'active', 'set.locale'])->group(function (): void { ... });
```

placed beside the other security routes.

| Verb | URL | Name | Controller |
|---|---|---|---|
| POST | `settings/account/security/password` | `settings.password.create` | `Security\PasswordController@create` |
| DELETE | `settings/account/security/password` | `settings.password.destroy` | `Security\PasswordController@destroy` |

The existing `settings.password.update` is unchanged.

### `settings.password.create`

Sets a password on an account that has none. Input: `new_password`, `['required', 'string',
'max:255', 'confirmed', Password::min(8)]`. No `current_password` rule, because there is none to
check, which is the same problem the example solves in its Fortify password confirmation callback.

Calls `UpdateUserPassword`, which detects that no password was set, logs `UserPasswordSet` rather
than `UserPasswordUpdate`, and sends `PasswordSet` (FR-026, FR-028).

Refuses with `422` when the account already has a password, since that is
`settings.password.update`'s job and the two must not overlap.

### `settings.password.destroy`

Calls `DestroyUserPassword`, which nulls `password` and `password_changed_at`, dispatches
`LogUserAction` with `UserPasswordRemoved`, and sends `PasswordRemoved`.

| Outcome | Response |
|---|---|
| Removed | `302` to `settings.security.index` with a status naming what still signs them in |
| No passkey on the account | `422` saying a passkey is needed first (FR-024) |
| Passkeys unavailable on this instance | `422` with the reason (FR-040) |
| SSO user | `422`, there is no password to remove |

The warning FR-025 requires is on the screen; this route is what the confirmation submits.

## Signing up with a passkey

Two routes in `routes/auth.php`, inside the existing guest group.

| Verb | URL | Name | Controller |
|---|---|---|---|
| POST | `register/passkey` | `auth.registerPasskey.create` | `Auth\RegistrationPasskeyController@create` |
| GET | `register/passkey` | `auth.registerPasskey.new` | `Auth\RegistrationPasskeyController@new` |

The order is forced by the protocol: `Webauthn::prepareAttestation()` takes a user, so the account
has to exist before a credential can be made against it.

`auth.registerPasskey.create` validates everything the password route validates except the password:
name, email, timezone, terms, and the bot check. It creates the account with `password` null, signs
them in, and redirects to `auth.registerPasskey.new`.

`auth.registerPasskey.new` is the step that opens the device prompt. It posts to the package's
`webauthn.store.options` and `webauthn.store`, which now work because the person is signed in. On
success it confirms the passkey was created (FR-049) and sends them to `auth.verification.notice`,
exactly where the password route sends them.

A prompt cancelled here leaves an account with neither a passkey nor a password. The route does not
refuse or undo anything: the account is real and the emailed link reaches it. It says so and offers
both a passkey and a password again (FR-050, FR-052).

`auth.register.create` is unchanged. Its password rules, its terms acceptance and its bot check stay
exactly as they are (FR-054).

## What the provider change means for the routes that already exist

`auth.login.create`, `auth.magicLink.show`, `settings.password.update` and the remember cookie are
unchanged in contract and in behaviour, but they no longer take the path they used to. Every one now
runs through `EloquentWebAuthnProvider`, which delegates to `EloquentUserProvider` for anything that
is not a signed challenge, and which checks a password only while `webauthn.fallback` is true.

Nothing about their contract changes. They are named here so the regression suite in step 1 has an
explicit list, and so a reader knows the provider change reaches further than the passkey routes.

## What is not added

No route serves a passkey, a public key, a credential id or a challenge as its own resource
(FR-042). No route lists another account's passkeys. No `Api` routes, because there is no `Api`
namespace yet.
