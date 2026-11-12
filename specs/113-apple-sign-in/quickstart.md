# Quickstart: checking Sign in with Apple by hand

Apple refuses a local address, so this runs on an instance with a public HTTPS domain. The names
are in [contracts/configuration.md](contracts/configuration.md).

## Prerequisites

- An Apple developer account, a Services ID with Sign in with Apple enabled, the instance's domain
  and `/sso/apple/callback` registered on it, and a key (`.p8`) with its key ID.
- `composer require socialiteproviders/apple`, then the four settings.

## With the package

| Step | Expected |
| --- | --- |
| Open the sign in screen | **Continue with Apple** sits after Google and before Microsoft |
| Continue with Apple, with an Apple account never used here | An account is created with the name and email address Apple shared |
| Sign out, continue with Apple again | The same account, with no second account created |
| Sign in with a password on another account, open the security settings, connect Apple | "Apple now signs you in." The session that started the connection is still signed in |
| Disconnect Apple there | Disconnected, under the existing rule for the last way in |
| Cancel at Apple | Back on the sign in screen with the usual error |
| Look at the address bar and the access log during a first sign in | No name and no email address in any URL |
| Paste the key on one line into the admin space | Apple is still offered and sign in still works |
| Empty the key and supply a secret generated in advance as `APPLE_CLIENT_SECRET` | Apple is still offered and sign in still works |

The fourth row is the one that proves the relay: if the security settings are not reached signed
in, the session cookie did not travel (plan, Risks).

## Without the package

```bash
composer remove --dev socialiteproviders/apple
php artisan test --compact
```

| Step | Expected |
| --- | --- |
| The suite | Passes; the registration test for Apple is skipped |
| Open the sign in, registration and security screens with the four settings supplied | No Apple, every other provider present |
| Open `/sso/apple/redirect` | Not found |
| Open the instance settings of the admin space | The Apple settings, with the sentence saying the package is not installed |

Restore the package with `git checkout composer.json composer.lock` and `composer install`.
