# Implementation Plan: Sign in with Apple

**Branch**: `claude/apple-socialite-provider-af30cc` | **Date**: 2026-11-11 | **Spec**: [spec.md](spec.md)

## Summary

`socialiteproviders/apple` is added as a suggested package, kept in the development dependencies.
`SsoProviderEnum` gains `Apple` after `Google`, and everything downstream (routes, actions, screens,
the stored connection, the admin notice for an absent package) already works from the enum and from
`monica.sso_installed`. The new work is one relay route for the POST Apple answers with, the
private key's shape, configuration, four instance settings and the guide. The reasoning is in
[research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: `laravel/socialite`, `socialiteproviders/manager` (already present),
`socialiteproviders/apple` (added to `require-dev` and `suggest`)

**Storage**: No schema change. `provider_connections.provider` gains the value `apple`

**Testing**: PHPUnit (`php artisan test --compact`). Socialite is faked; Apple is never called

**Constraints**: The session cookie stays `SameSite=lax`. No `env()` outside `config/`. Every string
in every locale. The suite passes with the package absent

**Scale/Scope**: 1 provider, 4 instance settings, 1 new route and controller, 3 guide pages

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the logic | Pass. No action changes; the relay holds no rule |
| II. Simplicity | Pass. One enum case reaches every screen; one route is the only new moving part |
| III. Tested | Pass. See Tests |
| IV. Laravel conventions | Pass. The package is used as shipped, registered the way its guide says |
| V. Localized | Pass. Four setting descriptions go through `monica:localize`. "Apple" is not translated |
| IX. Accessible | Pass. No new markup; the provider control is reused |
| X. API | Pass. None owed (research R6) |
| Suggested packages | Pass, applied by analogy: suggested, kept for development, read through configuration (research R4) |
| XI. Short record | Pass. `data-model.md` is omitted: there is no schema change |

## Design

| # | Change | Where |
| --- | --- | --- |
| 1 | Add the package to `require-dev` and a `suggest` sentence | `composer.json` |
| 2 | `services.apple`: `client_id`, `client_secret` (null), `team_id`, `key_id`, `private_key`, `redirect` defaulting to `/sso/apple/callback` | `config/services.php` |
| 3 | `sso_installed` leaves `apple` out unless the package's class exists | `config/monica.php` |
| 4 | `Apple = 'apple'` after `Google`; `brandName()`; `requiredSettings()` (research R5) | `app/Enums/SsoProviderEnum.php` |
| 5 | When `apple` is installed: listen for `SocialiteWasCalled`, rewrite the private key as PEM (research R3), extend Socialite | `app/Providers/AppServiceProvider.php` |
| 6 | `POST sso/apple/callback`, named `auth.sso.relay`, with `sso` and the throttle, without the `web` group | `routes/auth.php` |
| 7 | `SsoController::relay()`: 303 to `auth.sso.callback` with `code`, `state`, `error`, and `user` encrypted | `app/Http/Controllers/App/Auth/` |
| 8 | `callback()` decrypts a relayed `user` into the request before asking Socialite; a value that does not decrypt is dropped | `app/Http/Controllers/App/Auth/SsoController.php` |
| 9 | `AppleClientId`, `AppleTeamId`, `AppleKeyId` (text), `ApplePrivateKey` (secret): config key, env name, group, description, `providerIsInstalled()` | `app/Enums/InstanceSettingEnum.php` |
| 10 | The new variables and the provider list | `.env.example` |
| 11 | Name Apple, say the package is installed separately, what to supply, and that Apple refuses a local address | `docs/portal/en/10-your-account-and-security/5-sign-in-with-a-provider.md`, `13-when-something-looks-wrong.md`, `2-start-here/2-create-your-account.md` |
| 12 | `php artisan monica:localize`, then translate what it reports | `lang/*.json` |
| 13 | One line for the feature | `docs/FEATURES.md` |

The operator facing names are in [contracts/configuration.md](contracts/configuration.md). How to
check the result by hand is in [quickstart.md](quickstart.md).

## Tests

| Layer | Proves | File |
| --- | --- | --- |
| Enum | Apple's place in the order, `brandName()`, `requiredSettings()` | `tests/Unit/Enums/SsoProviderEnumTest.php` |
| Helper | Offered with every setting, absent with one missing, absent when not in `sso_installed` whatever is supplied | `tests/Unit/Helpers/SsoProviderAvailabilityTest.php` |
| Enum | The four settings: config key, kind, group, and absent when `apple` is not installed | `tests/Unit/Enums/InstanceSettingEnumTest.php` |
| Registration | `Socialite::driver('apple')` resolves the package's class, and a one line key reaches it as PEM. Skipped when the class is absent | `tests/Feature/Auth/SsoDriverRegistrationTest.php` |
| Relay | A POST with no CSRF token answers 303 to the callback, carries `code` and `state`, hides the name, and sets no session cookie. Not found when Apple is not installed | `tests/Feature/Controllers/App/Auth/SsoControllerTest.php` |
| Controller | A relayed `user` reaches the faked driver decrypted; a forged one is dropped and sign in still answers | `tests/Feature/Controllers/App/Auth/SsoControllerTest.php` |
| Routes | The relay route is added to the public list | `tests/Feature/RouteProtectionTest.php` |
| View model | Existing tests updated for the longer list only | `LoginViewModelTest`, `SecurityViewModelTest` |

## Order

1. Rows 1 to 5 with their tests: Apple is offered, or absent (story 2 holds from here).
2. Rows 6 to 8: the journey of story 1 completes.
3. Row 9, then row 12.
4. Rows 10, 11 and 13.
5. The four checks of `CLAUDE.md`, then the suite once more with `apple` removed from `sso_installed`.
6. [quickstart.md](quickstart.md) on a public address, in a real browser.

## Risks

- **The relay is not proven** (research R1). That a `lax` cookie travels on the GET after Apple's
  POST is browser behaviour no test here reaches. If the quickstart shows it does not, the relay
  answers with a page that navigates by itself instead of a 303, and nothing else changes.
- **Removing the `web` group from one route** is assumed to work by name. The relay test proves it,
  or the route moves to a file loaded without the group.
- **The package was read on `master`**, not at a release. Row 1 pins what Composer resolves, and the
  registration test fails first if `AppleToken` or the key handling differs.
- **Apple's button rules** (its logo, its wording) were not read. The button is the plain control
  every provider uses. Apple may ask for more before it approves a Services ID for production use.
- **A name comes from the POST, not from the signed token**, so it is typed input. It is stored as
  every other provider's name is; no new validation is planned.
