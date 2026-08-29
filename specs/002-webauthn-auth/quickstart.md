# Quickstart: WebAuthn Authentication

**Feature**: `002-webauthn-auth` | **Date**: 2026-08-25

How to set this up locally and prove it works. Every scenario here maps to a user story or an edge
case in [spec.md](spec.md), and the whole list is what "done" means for the feature.

## Prerequisites

- The repository set up as usual: `composer setup`.
- A browser and a device that can do passkeys. Any current Chrome, Safari, Firefox or Edge on a
  machine with Touch ID, Windows Hello, a fingerprint reader or a security key.
- The application reached at `http://localhost:8000`. Passkeys need a secure context and localhost
  is the exception browsers make, so no certificate is needed (FR-032).

## Setup

```bash
composer require asbiin/laravel-webauthn:^6.0
```

```bash
php artisan vendor:publish --provider="LaravelWebauthn\WebauthnServiceProvider"
```

```bash
php artisan migrate
```

Switch the `users` provider in `config/auth.php` to the `webauthn` driver, which is option 2 in the
package README:

```php
'providers' => [
    'users' => [
        'driver' => 'webauthn',
        'model' => env('AUTH_MODEL', User::class),
    ],
],
```

Then apply the configuration deviations from [research.md](research.md) to `config/webauthn.php`:
`prefix` to `passkeys`, `userless` true, `fallback` true written in explicitly, `resident_key` and
`user_verification` both `required`, `model` pointing at `App\Models\WebauthnKey`, `limiters.login`
to a named limiter, `timeout` set explicitly, `attachment_mode` left null.

`fallback` is the one to get right. It is in neither the published config nor the example's, it
defaults to true, and if it is ever false then no password in the application is checked by
anything.

Two settings deliberately differ from `asbiin/laravel-webauthn-example`, which ships both at
`preferred`: `resident_key` and `user_verification` are `required` here, because FR-009 promises
nothing is typed and FR-020 promises no two factor code afterwards. Research section 9 has the
argument and the cost.

The browser side needs one install, the library the reference application uses:

```bash
bun add @simplewebauthn/browser
```

It goes in `dependencies`, beside `alpinejs` and `@hotwired/turbo`, rather than in `devDependencies`
where the example keeps it. `resources/js/passkey.js` is a thin Alpine wrapper over it, and converts
nothing: at version 13 the library takes and returns the JSON form of the WebAuthn structures.

Add to `.env`:

```
WEBAUTHN_USERLESS=true
```

One deployment note that matters locally too: the challenge lives in the cache between the options
request and the verification request, so `CACHE_STORE` must not be `array` or `null`. `file` is fine
for development.

```bash
composer dev
```

## Testing without real hardware

Every scenario below can be driven from a real authenticator, and most should be at least once. For
repeatable runs, Chrome DevTools has a virtual authenticator: open DevTools, find the `WebAuthn`
panel, enable the virtual environment, and add an authenticator with resident keys and user
verification both on. Turning user verification off there is the way to prove the FR-020 claim
holds, since the assertion should then be refused rather than accepted as two factors.

## Scenarios

### Nothing else broke (run this first)

The `users` provider now serves every sign in, so before touching passkeys at all, confirm the
authentication that has nothing to do with them still works:

1. Sign in with an email address and a password.
2. Sign in with a wrong password and confirm the refusal is unchanged.
3. Confirm a deactivated user and an SSO user are still refused as they were.
4. Sign in through a magic link, and confirm a user with two factor authentication on is still asked
   for the code.
5. Sign in with "remember me", close the session cookie, and confirm the remember cookie still signs
   you back in.
6. Set `webauthn.fallback` to false, confirm password sign in stops working entirely, then set it
   back. This is worth doing once by hand so the failure is recognisable if it ever happens by
   accident.

### Register and sign in (User Story 1)

1. Sign in with an email address and a password.
2. Go to Settings, then Security and access. The passkeys section explains what a passkey is and
   offers to add one (Story 2 scenario 2).
3. Add one. Confirm on the device. The passkey appears with a name, the date added, and a statement
   that it has not been used yet (Story 1 scenario 1).
4. Add a second one, then cancel the device prompt partway. Nothing is added and the screen says so
   rather than going quiet (Story 1 scenario 2).
5. Sign out. On the sign in screen choose to sign in with a passkey. Type nothing. Confirm on the
   device. You are signed in (Story 1 scenario 3).
6. Back on the security screen, the passkey now shows a last used date, written by
   `RecordPasskeyUse` into `used_at`, and the account activity holds a sign in entry naming which
   passkey was used (Story 1 scenario 4, FR-016).
7. Sign out and sign in with the password. It still works and the passkey is untouched (Story 1
   scenario 5).

### Manage them (User Story 2)

1. With three passkeys registered, confirm all three are listed most recently used first, each
   distinguishable by name, date added and last use (SC-006).
2. Rename one. Submit a blank name and confirm it is refused with a reason and the old name stands.
3. Remove one. Confirm the warning appears first, that it names what the removal costs, and that
   nothing happens until it is confirmed.
4. Confirm the removed passkey no longer signs you in, and is refused exactly as an unregistered
   credential is.
5. Confirm the account activity holds an entry for the addition, the rename and the removal, and
   that an email arrived for the addition and the removal (FR-035).
6. Try to register the same authenticator twice. The browser should refuse at the device level from
   `excludeCredentials`; then post a duplicate directly to confirm the server refuses it too, with
   no second row created (FR-004).

### Refusals reveal nothing (FR-013, SC-004)

Each of these must return the same message as a wrong password, and they must be identical to each
other:

1. An assertion from a credential that was never registered here.
2. A passkey belonging to a user whose `is_active` is false.
3. A passkey belonging to a soft deleted user.
4. A passkey belonging to a user with an `sso_provider`.
5. A replayed assertion, meaning the same challenge answered twice.

Compare the response bodies rather than eyeballing them.

### Keep a way in (User Story 3)

1. With a passkey registered, confirm password sign in and magic link sign in both still work.
2. Open the sign in screen in a browser without passkey support, or over a plain HTTP host that is
   not localhost. The screen states passkeys are unavailable and why, and shows no control that
   would fail (Story 3 scenarios 2 and 3).
3. Exceed the attempt limit on `passkey-login` and confirm further attempts are refused for a period
   with the reason stated (Story 3 scenario 6).
4. Change `APP_URL` to a different host, then present a passkey created under the old one. The
   message explains the passkey no longer applies to this address and points elsewhere, rather than
   reporting a bare failure (Story 3 scenario 7, FR-041).
5. In the DevTools virtual authenticator, force the signature counter backwards and present the
   passkey. The sign in is refused and the owner is emailed (FR-012).
6. Sign in through the magic link as a user who has two factor authentication on. The code is still
   asked for, which is the rule FR-022 pins in place.

### Cross device (User Story 4)

1. Register a passkey on a phone.
2. On a computer that holds no passkey, choose to sign in with a passkey and complete the platform's
   own cross device flow. You are signed in and the session is an ordinary one.
3. Abandon that flow halfway. The screen returns to its starting state and says nothing was signed
   in.

### Go passwordless (User Story 5)

1. With at least one passkey, remove the password. Confirm the warning names what still signs you
   in, and that nothing happens until you confirm.
2. Sign out. Sign in with the passkey. Then submit your old email address and password and confirm
   the refusal is the ordinary one, revealing nothing about the account having no password (Story 5
   scenario 3).
3. Ask for a password reset link and use it to set a password. The flow works rather than failing on
   an account that had none (Story 5 scenario 5).
4. Remove the password again, then remove your last passkey. Confirm the warning says the emailed
   link becomes your only way in, that it offers setting a password instead, and that it does not
   simply refuse (FR-033).
5. Confirm the emailed link then signs you in.
6. On an account with no passkey, try to remove the password. Refused, with a reason (FR-024).
7. On a plain HTTP host that is not localhost, try to remove the password. Refused, with a reason
   (FR-040).
8. Confirm the activity holds an entry for the removal and for setting one again, and that both sent
   an email (FR-028).

### Sign up with a passkey and no password (User Story 6)

1. Open the registration screen over https. Creating a passkey is the primary action, and choosing a
   password is one link away rather than a field sitting beside it.
2. Fill in a name and an email address, nothing else, and confirm the passkey on the device. The
   account exists, you are signed in, and you are told the passkey was created.
3. Confirm on the security screen that the account holds that passkey and no password.
4. Sign out and sign back in with the passkey, typing nothing.
5. Start again and choose the password route instead. It behaves exactly as it did before this
   feature existed, terms and bot check included.
6. Start again, get as far as the device prompt, and cancel it. The account exists with neither a
   passkey nor a password: confirm you are told the emailed link is what reaches you, and that both a
   passkey and a password are offered.
7. Open the registration screen over plain http. It says why a passkey cannot be made here and
   offers only the password route.

### Account deletion (FR-037)

Delete an account holding passkeys and confirm the `webauthn_keys` rows are gone. This is the
database cascade doing the work, so run it on each connection rather than trusting one.

### One thing the example warns about

Its Fortify provider replaces the password confirmation validator so passwordless users can get past
it. Walk the signed in screens looking for anything that asks for a current password before acting,
and confirm each is either reachable for an account with no password or explicitly refused with a
reason. `PasswordController::update` is the known one, which is why setting a password is a separate
route.

## Automated suite

```bash
php artisan test
```

```bash
php artisan test --filter=Passkey
```

```bash
php artisan test --filter=ProviderRegression
```

The suite must pass on all three connections, since the credential id index differs by driver:

```bash
php artisan test --env=testing.mysql
```

```bash
php artisan test --env=testing.pgsql
```

## Before it is done

```bash
php artisan monica:localize --check
```

Every string this feature adds must be translated in all nine locales, and the counted phrases must
go through `trans_choice()` with `:count` in every segment.

```bash
vendor/bin/pint --test
```

```bash
vendor/bin/phpstan analyse
```

Then the accessibility pass on the two screens touched, meaning the sign in screen and the security
screen: keyboard only operation, a screen reader over the whole passkey section, focus visible
throughout, no information carried by colour alone, readable at 200%, and usable at phone width. The
outcome of a cancelled device prompt has to be announced, not just rendered, since it changes
nothing visible on the page (FR-044, SC-008).
