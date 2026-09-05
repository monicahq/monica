# Research: Adopt laravel/passkeys

Read in `laravel/passkeys-server@main` and in `vendor/`, not inferred.

## Versions

| Package | Now | After | Note |
| --- | --- | --- | --- |
| `asbiin/laravel-webauthn` | 6.0.0 | removed | |
| `laravel/passkeys` | — | ^0.2.1 | pins `web-auth/webauthn-lib: 5.3.x`, an exact minor |
| `web-auth/webauthn-lib` | 5.3.6 | 5.3.6 | already satisfies the pin, so no resolver churn |
| `@laravel/passkeys` | — | ^0.4.0 | wraps `@simplewebauthn/browser` ^13, which we already use |

It allows `illuminate/* ^13.0` and PHP ^8.2, so nothing blocks the install.

## Decisions

### D1. Refuse a deactivated or SSO user before the signature is checked

`Passkeys::allowsLogin()` is the documented seam, but `PasskeyLoginController::store()` calls it *after*
`VerifyPasskey`, which has already stamped `last_used_at`, persisted the counter and fired
`PasskeyVerified`. It would journal a sign-in and email "new sign-in detected" for somebody then
refused. FR-005 forbids that.

**Decision**: bind `Laravel\Passkeys\Actions\VerifyPasskey` to a subclass running `RefusePasskeyLogin`
between `getPasskey()` and `validate()`; the controller type-hints the concrete class, so a container
binding suffices. **Rejected**: `authorizeLoginUsing()`, on the ordering above; overriding the
controller, which would duplicate a transaction we must then keep in step.

### D2. Renaming a passkey stays ours

The package registers options, store and destroy, and **no update route**, so `PasskeyController::update`
and `settings.passkeys.update` survive FR-010 untouched. FR-010 did not anticipate this.

### D3. A record somebody does not own returns 404, not the package's 403

`PasskeyRegistrationController::destroy()` does `abort_unless(..., 403)`, and the architecture rules
require a record outside your compartment to fail as if it did not exist. The service provider binds
`{passkey}` through `resolveRouteBinding()` on the configured model, so `App\Models\Passkey` overrides
it to scope by the signed-in user: the binding 404s first and the 403 is unreachable.

### D4. Keep the Alpine component; swap the two calls inside it

`@laravel/passkeys` ships React, Vue and Svelte helpers and **no Alpine helper**, but its core API is
framework-agnostic promises: `isSupported()`, `register({name})`, `verify({remember})`, `cancel()`. The
live-region `announce()` work is ours and survives, and its typed errors (`UserCancelledError`,
`PasskeyExistsError`, `InvalidDomainError`, `NotSupportedError`) make the copy more precise, not less.
This retires the concern raised at `/speckit-specify`.

### D5. The RS1 workaround goes, and the reason is verified

`CeremonyStepManagerFactory::__construct` sets `Manager::create()->add(ES256, RS256)` — RS1 is never
registered. The workaround existed because `asbiin/laravel-webauthn` registered it through
`CoseAlgorithmManagerFactory`, and `laravel/passkeys` builds the factory with `new`, so a container
binding would not reach it anyway. Delete it (FR-013). Only ES256 and RS256 are then accepted, where
`config/webauthn.php` listed five; registration offers the same two, and FR-004 drops every existing
credential anyway.

### D6. Config seams for FR-006 and FR-007

Both are plain config, confirmed in `routes/routes.php`:

- `management_middleware` runs through `array_filter`, so `[]` removes `password.confirm` (FR-007).
- `throttle` is a middleware string, so `throttle:passkey` plus a named limiter keyed on the credential
  id keeps the per-credential allowance (FR-006), reading `id` as the current limiter already does.

### D7. Redirect after sign-in via the response contract, not config

`PasskeyLoginResponse` reads `config('passkeys.redirect')`, a static path, but the application uses
`redirect()->intended(route('home.index'))`, and `route()` in a config file breaks config caching, so
bind the contract instead. Bind `PasskeyDeletedResponse` likewise, keeping the "what still signs you
in" message `PasskeyController::destroy` shows today.

### D8. `PasskeyRegistered`, `PasskeyVerified` and `PasskeyDeleted` all carry `$user` and `$passkey`

`HandlePasskeyLogin` currently re-reads the most recently used passkey to name it, because the old event
carried none; that guess goes away.

**Open risk**: the two halves live in separate repositories on unsynchronised version numbers (v0.2.1
and v0.4.0), both pre-1.0. Spec Assumptions record this as accepted.
