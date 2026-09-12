# Quickstart: Sign in with an outside provider

How to see this working and how to prove it. Routes, configuration keys and answers are in [contracts/sign-in-with-a-provider.md](contracts/sign-in-with-a-provider.md).

## Prerequisites

- `composer require laravel/socialite`, and `composer require --dev socialiteproviders/microsoft` if you want Microsoft too.
- One provider registered with its own company, its redirect pointing at `{APP_URL}/sso/github/callback`, and its client and secret in `.env`.
- `php artisan migrate:fresh --seed`, since `users` changes.
- The application running: `composer run dev`.

## Seeing it work

1. Open `/login`. One control per configured provider sits beside the passkey block, each reading `Continue with` and the provider name.
2. Choose one, confirm at the provider, and land on the vaults. The account was created: a vault, the default value lists and the default relationship vocabulary are all there.
3. Sign out and repeat. The same account is reached, and nothing new is created.
4. Open `/settings/account/security`. The provider is named, with a control to disconnect it.
5. Try to disconnect. It is refused, because that provider is the only way into an account created through it.
6. Set a password on the same screen, disconnect, confirm the warning, and sign in with the password.
7. Connect the provider again from the security screen and confirm it signs you in.

## Proving it

```bash
php artisan test --compact tests/Unit/Actions/SignInWithProviderTest.php
php artisan test --compact tests/Feature/Controllers/App/Auth/SsoControllerTest.php
php artisan test --compact tests/Feature/Controllers/App/Settings/Account/Security/SsoControllerTest.php
```

The provider is never called in a test. Socialite's driver is faked, and the tests pass the identifier, the address and the verification flag the provider would have returned.

Both package states. Overriding the config key proves the availability logic; only removing the package proves the application boots without it, and SC-004 asks for both:

```bash
php artisan test --compact --filter=Sso
```

```bash
composer remove --dev socialiteproviders/microsoft && php artisan test --compact && composer require --dev socialiteproviders/microsoft
```

The tests that matter override `monica.sso_installed` to leave Microsoft out, then assert that `/login` offers only GitHub, Google and Facebook, that every Microsoft route is not found, and that the security screen names Microsoft nowhere. The second command is what the CI job in T035 runs.

## What to check by hand

| Check | Expected |
| --- | --- |
| `/sso/github/redirect` with no provider configured | not found |
| `/sso/nonsense/redirect` | not found, refused by the route constraint |
| A provider address matching a user who has a password | back to `/login`, told the address is in use |
| The sign in screen with a screen reader | each control announced by the provider name, not by its mark |
| The security screen zoomed to 200% | the provider block stays readable and the disconnect control reachable |
| `php artisan monica:localize --check` | nothing missing, in every configured locale |
