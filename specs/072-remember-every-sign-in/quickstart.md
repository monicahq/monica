# Quickstart: Remembering Every Sign In

How to see the feature work end to end. The rules themselves are proven by the tests listed in
[plan.md](./plan.md); this guide covers what PHPUnit cannot reach, the browser and the passkey prompt.

## Prerequisites

- The application served over https (passkeys need it), with `SESSION_LIFETIME=1` in `.env` so a
  session expires one minute after the last request.
- An account with a password, an account with only a passkey, and the link by email switched on.
- The browser's developer tools open on the cookies for the instance. The remember cookie is named
  `remember_web_` followed by a hash.

## Steps

1. **Password, ticked by default.** Open the sign in screen: the box is ticked. Submit a wrong
   password with the box unticked: it comes back unticked. Sign in with it ticked: a `remember_web_`
   cookie appears, expiring in the instance's remembered duration (400 days unless changed). Wait two
   minutes, reload: still signed in.
2. **Passkey.** Sign out, sign in with the passkey-only account: the cookie appears with the same
   expiry. Wait two minutes, reload: still signed in, which proves an account without a password is
   recalled (research D1).
3. **Link by email.** Sign out, ask for a link, open it: the cookie appears. Repeat with an account
   that has two factor authentication: the cookie appears after the code, with the same expiry.
4. **Provider.** With a provider configured, sign in through it: the cookie appears.
5. **Password change.** Sign in with the password account in two browsers. In the first, change the
   password in Settings, Security. Reload the second: it lands on the sign in screen. Wait two minutes
   in the first and reload: still signed in.
6. **Sign out.** Sign in on two browsers, sign out in one, wait two minutes, reload the other: it
   lands on the sign in screen (FR-012, unchanged).

## Checks before the pull request

```bash
php artisan test --compact tests/Feature/Controllers/App/Auth tests/Unit/Models/UserTest.php tests/Unit/Providers/AppServiceProviderTest.php tests/Feature/Controllers/App/Settings/Account/Security/PasswordControllerTest.php
```

Then the full suite (the middleware sits on every web route), `vendor/bin/phpstan analyse --no-progress`,
`vendor/bin/pint --dirty --format agent` and `php artisan monica:localize --check`.

Reset `SESSION_LIFETIME` afterwards.
