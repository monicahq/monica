# Implementation Plan: Sign in through OpenID Connect providers

**Branch**: `2026-11-08-connect-several-providers` | **Date**: 2026-11-09 | **Spec**: [spec.md](spec.md)

## Summary

`socialiteproviders/openidconnect` replaces `socialiteproviders/microsoft` and becomes a regular
dependency. It registers its own drivers, so the application stops registering any. Four provider
values are added to `SsoProviderEnum`, Microsoft keeps its value, and everything downstream (routes,
actions, screens, the stored connection) already works from the enum. The work is configuration,
the availability rule, the admin settings, one missing `catch`, and the guide. The reasoning is in
[research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: `laravel/socialite`, `socialiteproviders/openidconnect` ^1.0 (added),
`socialiteproviders/microsoft` (removed)

**Storage**: No schema change. `provider_connections.provider` gains the values `keycloak`, `auth0`,
`okta` and `openidconnect`

**Testing**: PHPUnit, serial (`php artisan test --compact`). Socialite is faked; no provider is called

**Project Type**: Server rendered web application

**Constraints**: WCAG 2.2 AA on the buttons (they reuse the existing control), every string in every
locale, no `env()` outside `config/`

**Scale/Scope**: 5 providers, 16 new instance settings, 2 controllers, 3 guide pages

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the logic | Pass. No action changes; they take the enum |
| II. Simplicity | Pass. Net deletion in `AppServiceProvider` and the enum |
| III. Tested | Pass. See Tests below |
| IV. Laravel conventions | Pass. The package's own service provider and config file are used as shipped |
| V. Localized | Pass. Setting labels and descriptions go through `monica:localize` |
| VI to VIII | Not concerned. Nothing a person already sees changes; no data is dropped |
| IX. Accessible | Pass. No new markup; the provider control is reused |
| X. API | Pass. No API exists for provider sign in and none is owed (research R7) |
| XI. Short record | Pass. `data-model.md` is omitted: there is no schema change |

No violation, so no complexity tracking.

## Design

| # | Change | Where |
| --- | --- | --- |
| 1 | Swap the dependency; move it to `require`, drop the `suggest` line | `composer.json` |
| 2 | `driver_prefix` empty; connections `microsoft`, `keycloak`, `auth0`, `okta`; each `redirect` defaults to `/sso/{name}/callback`; the long comment block goes | `config/oidc.php` |
| 3 | Remove `microsoft`; add `name` to `openidconnect` | `config/services.php` |
| 4 | `sso_installed` is every enum value; drop both provider class imports | `config/monica.php` |
| 5 | Add `Keycloak`, `Auth0`, `Okta`, `OpenIdConnect` after `Facebook`; `brandName()` reads the generic name; add `requiredSettings()`; delete `driverClass()` | `app/Enums/SsoProviderEnum.php` |
| 6 | `isConfigured()` loops over `requiredSettings()` | `app/Helpers/SsoProviderAvailability.php` |
| 7 | Delete `configureSingleSignOn()` | `app/Providers/AppServiceProvider.php` |
| 8 | 16 cases, listed in the contract, with config key, env name, group, kind, description and `providerIsInstalled()` | `app/Enums/InstanceSettingEnum.php` |
| 9 | `redirect()` catches and refuses | `app/Http/Controllers/App/Auth/SsoController.php`, `.../Settings/Account/Security/SsoController.php` |
| 10 | New variables, the stale Microsoft sentence removed | `.env.example` |
| 11 | Name the providers; one operator page section listing what each needs | `docs/portal/en/10-your-account-and-security/5-sign-in-with-a-provider.md`, `13-when-something-looks-wrong.md`, `2-start-here/2-create-your-account.md` |
| 12 | `php artisan monica:localize`, then translate what it reports | `lang/*.json` |

The operator facing names (environment variables, settings, addresses) are in
[contracts/configuration.md](contracts/configuration.md). How to check the result by hand is in
[quickstart.md](quickstart.md).

## Tests

| Layer | Proves | File |
| --- | --- | --- |
| Enum | The order of cases, `brandName()` with and without a generic name, `requiredSettings()` | `tests/Unit/Enums/SsoProviderEnumTest.php` |
| Helper | Each provider is offered with all its settings and absent with one missing | `tests/Unit/Helpers/SsoProviderAvailabilityTest.php` |
| Enum | The new settings: config key, kind, group | the existing `InstanceSettingEnum` test |
| Registration | `Socialite::driver()` resolves the package's class for each of the five, and a stored instance setting reaches it (research R4) | new, `tests/Feature/` beside the existing sign in tests |
| Controller | `redirect()` answers with the refusal when the driver throws; one new provider goes through `callback()` | `tests/Feature/Controllers/App/Auth/SsoControllerTest.php`, `.../Security/SsoControllerTest.php` |
| View model | Existing tests updated for the longer list only | `LoginViewModelTest`, `SecurityViewModelTest` |

Tests naming `driverClass()` or the Microsoft package are rewritten or deleted with what they covered.

## Order

1. Rows 1 to 7 with their tests: Microsoft works again through the new package (story 2).
2. Row 5's new cases reach the screens: the generic provider (story 1), then the three named (story 3).
3. Row 9.
4. Row 8, then row 12.
5. Rows 10 and 11.
6. The four checks of `CLAUDE.md`: tests, `monica:localize --check`, PHPStan, Pint.

## Risks

- **Boot order** (research R4). If a stored setting does not reach the driver, the registration test
  fails first, and the fix is to apply the settings before the package boots.
- **A development database with Microsoft connections** stops matching (research R6). The pull
  request says so.
- **The package is one month old** (1.0.1, 2026-09-23). Its verification is relied on, not re-tested.
