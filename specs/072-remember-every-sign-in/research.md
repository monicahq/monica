# Research: Remembering Every Sign In

Read against Laravel 13.34 (`composer show laravel/framework`) and `laravel/passkeys`.

## D1. An account without a password can be remembered

- **Finding**: `SessionGuard::userFromRecaller()` returns nobody when `getAuthPassword()` is not a
  string (`SessionGuard.php:234`). `users.password` is null for an account made through a provider or
  a passkey, and after a removal, so a remember cookie never brings such an account back today.
- **Decision**: `User::getAuthPassword()`, marked `#[Override]`, returns `$this->password ?? ''`.
- **Rationale**: the recaller then compares a stable fingerprint of `''` and works. Nothing can sign in
  with it: the hasher refuses an empty hash (`AbstractHasher.php:28`), `AttemptLogin` refuses a null
  password before `Auth::attempt()`, and `ConfirmPassword` reads `password` directly. `login()` and
  `AuthenticateSession` both treat `''` as no password, as they treat null.
- **Alternatives**: a guard extending `SessionGuard`; a secret column per account (a migration).

## D2. One remembered duration for every way in

- **Decision**: `AppServiceProvider::applyInstanceSettings()` sets `auth.guards.web.remember` to
  `monica.remember_duration_days` × 1440 right after `InstanceSettings::apply()`. `AuthManager` reads
  that key each time it builds the guard (`AuthManager.php:147`). The `setRememberDuration()` block
  leaves `AttemptLogin`.
- **Rationale**: the 400 day cookie after a two factor challenge (FR-008) exists because the duration
  was set on one path only. Set where the guard is built, it reaches every path, the package's passkey
  login included, and a guard rebuilt by `forgetGuards()` keeps it. Placed next to `apply()`, the order
  it depends on is written down.
- **Alternatives**: the block before each `login()` (eight sites, a workaround for the passkey, the
  same defect waiting); `setRememberDuration()` on the guard in a `booted()` callback (lost when
  `forgetGuards()` rebuilds it, which six tests do); `bootstrap/app.php` (the order stays implicit).

## D3. Each way in passes `remember: true`

- Link by email and provider: `MagicLinkController::show` and `SsoController::signIn` call
  `login($user, remember: true)` and put `twoFactor.remember` to `true` instead of `false`.
  `TwoFactorChallengeController` already honours that key.
- Registration: `RegistrationController` and `RegistrationPasskeyController` call
  `Auth::login($user, remember: true)`. A provider account is created inside `SsoController::signIn`.
- Passkey: `PasskeyLoginController` already logs in with `$request->remember()`
  (`vendor/laravel/passkeys/src/Http/Controllers/PasskeyLoginController.php:62`). `signIn()` in
  `resources/js/passkey.js` passes `Passkeys.verify({ remember: true })`; the confirmation call
  beside it is left alone.
- The login stays in the controllers, where it is today: the actions return a user and never touch the guard.

## D4. The box is ticked unless the person unticked it

- **Decision**: `:checked="session()->hasOldInput() ? (bool) old('remember') : true"` in `login.blade.php`.
- **Rationale**: `old('remember', true)` would return `true` after a failed submit with the box
  unticked, since an unticked box sends nothing. Old input exists only after a failed submit.

## D5. Other devices are signed out by `AuthenticateSession`

- **Decision**: append `Illuminate\Session\Middleware\AuthenticateSession` to the `web` group in
  `bootstrap/app.php` (chosen with the user over deleting rows from `sessions`).
- **Rationale**: Laravel's documented mechanism, independent of the session driver. Each session keeps
  a fingerprint of the password it started with; a stale one is signed out at its next request. The
  recaller already checks the same fingerprint, which is why remembered access ends on a change today.
  It covers FR-009 and FR-011 with no other code: `ResetPassword` and `UpdateUserPassword` only write
  the password.
- **Limit**: the middleware skips an account without a password. A removal, or a first password set,
  ends remembered access elsewhere through the recaller but leaves open sessions running (spec, Clarifications).
- **Alternatives**: deleting the account's other rows from `sessions` (database driver only); both
  mechanisms together (two answers to one rule).

## D6. The device that changed the password stays remembered

- **Finding**: the change also makes the current device's own cookie stale. `AuthenticateSession`
  refreshes the session fingerprint after the response, but not the cookie.
- **Decision**: `PasswordController` (`update`, `create`, `destroy`) calls
  `Auth::guard('web')->login($request->user(), remember: true)` after the action when the request
  carries the recaller cookie, the condition `logoutOtherDevices()` uses (`SessionGuard.php:768`).
- **Rationale**: no listener reacts to `Login` (none in `app/`, `bootstrap/` or `config/`), so this only
  re-issues the cookie and regenerates the session id.
- **Alternatives**: `Auth::logoutOtherDevices($password)` hashes the password a second time and has no
  password to work with on a removal.

## D7. Left as they are

Signing out (FR-012): `SessionGuard::logout()` cycles the one remember token. The API login, the
screenshot link and the login link (FR-013).
