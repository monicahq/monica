# Tasks: Sign in through OpenID Connect providers

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/configuration.md](contracts/configuration.md), [quickstart.md](quickstart.md)

**Format**: `[ID] [P?] [Story] Description`. `[P]` means another file and no dependency on an unfinished task. Tests are written first and must fail before the implementation they cover.

## Phase 1: Setup

- [X] T001 Check that `composer.json` requires `socialiteproviders/openidconnect` `^1.0`, names `socialiteproviders/microsoft` nowhere (`require-dev`, `suggest`), and that `composer.lock` agrees; run `composer install -q`

## Phase 2: Foundational (blocks every story)

- [X] T002 Rewrite `config/oidc.php`: `driver_prefix` is an empty string; connections `microsoft` (provider `entra`, `MICROSOFT_CLIENT_ID`, `MICROSOFT_CLIENT_SECRET`, `MICROSOFT_TENANT` defaulting to `common`), `keycloak`, `auth0`, `okta` with the variables of the contract; each `redirect` reads `{PREFIX}_REDIRECT_URI` and defaults to `/sso/{name}/callback`; delete the long comment block
- [X] T003 [P] In `config/services.php` remove the `microsoft` block and give `openidconnect` a `name` (`OIDC_NAME`) and a `redirect` defaulting to `/sso/openidconnect/callback`
- [X] T004 [P] In `config/monica.php` make `sso_installed` every value of `SsoProviderEnum` when Socialite is present, and delete both provider class imports
- [X] T005 [P] Update `tests/Unit/Enums/SsoProviderEnumTest.php`: delete the `driverClass()` tests, add one proving `requiredSettings()` answers `client_id`, `client_secret`, `redirect` for GitHub, Google, Facebook and Microsoft
- [X] T006 [P] Update `tests/Unit/Providers/AppServiceProviderTest.php`: delete what covered `configureSingleSignOn()`
- [X] T007 In `app/Enums/SsoProviderEnum.php` add `requiredSettings(): array` and delete `driverClass()`
- [X] T008 In `app/Helpers/SsoProviderAvailability.php` make `isConfigured()` loop over `requiredSettings()` against `services.{value}`
- [X] T009 In `app/Providers/AppServiceProvider.php` delete `configureSingleSignOn()` and its call in `boot()`

**Checkpoint**: `php artisan test --compact --filter=Sso` passes with the four existing providers.

## Phase 3: User Story 1, the generic provider (P1, MVP)

**Independent test**: with the four `OIDC_*` settings filled, the provider is on the sign in and security screens under its name, and absent with one missing.

- [X] T010 [P] [US1] In `tests/Unit/Enums/SsoProviderEnumTest.php` prove `openidconnect` is the last case, that `brandName()` answers `services.openidconnect.name` and falls back to "OpenID Connect", and that `requiredSettings()` adds `base_url`
- [X] T011 [P] [US1] In `tests/Unit/Helpers/SsoProviderAvailabilityTest.php` prove the generic provider is configured with all four settings and not with `base_url` missing
- [X] T012 [P] [US1] Create `tests/Feature/Auth/SsoDriverRegistrationTest.php`: `Socialite::driver('openidconnect')` resolves the package's provider class, and a stored instance setting for the generic secret is what `config('services.openidconnect.client_secret')` answers (research R4)
- [X] T013 [P] [US1] In `tests/Feature/Controllers/App/Auth/SsoControllerTest.php` prove `redirect()` returns to the sign in screen with "That did not work. Try again, or sign in another way." when the driver throws, and that `callback()` signs somebody in through `openidconnect`
- [X] T014 [P] [US1] In `tests/Feature/Controllers/App/Settings/Account/Security/SsoControllerTest.php` prove `redirect()` refuses the same way when the driver throws
- [X] T015 [P] [US1] In `tests/Unit/Enums/InstanceSettingEnumTest.php` prove the config key, kind and group of the four generic settings (`OIDC_BASE_URL` Url, `OIDC_NAME` Text, `OIDC_CLIENT_ID` Text, `OIDC_CLIENT_SECRET` Secret)
- [X] T016 [US1] In `app/Enums/SsoProviderEnum.php` add `OpenIdConnect = 'openidconnect'` after `Facebook`, with its `brandName()` and `requiredSettings()`
- [X] T017 [P] [US1] In `app/Enums/InstanceSettingEnum.php` add the four generic cases pointing at `services.openidconnect.{base_url,name,client_id,client_secret}`, group Single sign on, each with its description and `providerIsInstalled()`
- [X] T018 [P] [US1] In `app/Http/Controllers/App/Auth/SsoController.php` make `redirect()` catch `Throwable` and refuse with the copy `callback()` uses
- [X] T019 [P] [US1] In `app/Http/Controllers/App/Settings/Account/Security/SsoController.php` make `redirect()` catch `Throwable` and refuse the same way
- [X] T020 [US1] Update `tests/Unit/ViewModels/Auth/LoginViewModelTest.php`, `tests/Unit/ViewModels/Settings/Account/Security/SecurityViewModelTest.php` and `tests/Feature/Views/App/Admin/ConfigurationTest.php` where they list or count providers or settings

**Checkpoint**: story 1 passes on its own.

## Phase 4: User Story 2, Microsoft stays (P2)

**Independent test**: with the two existing Microsoft settings, Microsoft is offered at `/sso/microsoft/redirect` under its own name.

- [X] T021 [P] [US2] In `tests/Feature/Auth/SsoDriverRegistrationTest.php` prove `Socialite::driver('microsoft')` resolves the package's provider class and that a stored Microsoft identifier reaches `services.microsoft.client_id`
- [X] T022 [P] [US2] In `tests/Feature/Controllers/App/Auth/SsoControllerTest.php` and `tests/Unit/Helpers/SsoProviderAvailabilityTest.php` rewrite or delete the tests naming the removed Microsoft class
- [X] T023 [P] [US2] In `tests/Unit/Enums/InstanceSettingEnumTest.php` prove the `MICROSOFT_TENANT` setting (Text, `services.microsoft.tenant`)
- [X] T024 [US2] In `app/Enums/InstanceSettingEnum.php` add the Microsoft tenant case, and make `providerIsInstalled()` of the two existing Microsoft cases stop naming the removed class

**Checkpoint**: Microsoft works with nothing changed on the operator's side.

## Phase 5: User Story 3, Keycloak, Auth0 and Okta (P3)

**Independent test**: each is offered under its own name with its settings filled, and absent with one missing.

- [X] T025 [P] [US3] In `tests/Unit/Enums/SsoProviderEnumTest.php` prove the order (existing four, Keycloak, Auth0, Okta, generic) and `requiredSettings()` for the three (`server_url` and `realm`; `domain`; `domain`)
- [X] T026 [P] [US3] In `tests/Unit/Helpers/SsoProviderAvailabilityTest.php` prove each of the three is configured with all its settings and not with one missing, through a `#[DataProvider]`
- [X] T027 [P] [US3] In `tests/Feature/Auth/SsoDriverRegistrationTest.php` prove `Socialite::driver()` resolves the package's class for `keycloak`, `auth0` and `okta`
- [X] T028 [P] [US3] In `tests/Unit/Enums/InstanceSettingEnumTest.php` prove the eleven settings of the three providers listed in `contracts/configuration.md`
- [X] T029 [US3] In `app/Enums/SsoProviderEnum.php` add `Keycloak`, `Auth0` and `Okta` between `Facebook` and `OpenIdConnect`, with brand names and `requiredSettings()`
- [X] T030 [P] [US3] In `app/Enums/InstanceSettingEnum.php` add the eleven cases pointing at `services.{keycloak,auth0,okta}.*`
- [X] T031 [US3] Update the view model and configuration tests of T020 for the three further providers

**Checkpoint**: all five providers work independently.

## Phase 6: Polish

- [X] T032 [P] In `.env.example` add the sixteen new variables and remove the sentence about installing the Microsoft package
- [X] T033 [P] Name the providers and what an operator supplies for each in `docs/portal/en/10-your-account-and-security/5-sign-in-with-a-provider.md`, `docs/portal/en/10-your-account-and-security/13-when-something-looks-wrong.md` and `docs/portal/en/2-start-here/2-create-your-account.md`
- [X] T034 Run `php artisan monica:localize`, translate what it reports in every `lang/*.json`, then pass `php artisan monica:localize --check`
- [X] T035 Search the repository for `socialiteproviders/microsoft`, `SocialiteProviders\Microsoft` and `oidc_` and remove what is left (SC-005)
- [X] T036 Pass `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent` and the whole suite with `php artisan test --compact`, serially
- [ ] T037 Run the manual check of `quickstart.md` against a local Keycloak
- [X] T038 Record the feature in `docs/FEATURES.md` with the feature-documentator skill

## Dependencies

- Phase 1, then Phase 2, then the stories, then Phase 6.
- T007 before T008. T005 and T006 fail until T007 and T009 land.
- T016 before T017 to T020. T029 after T016, since both edit the same enum and T029 places its cases before `OpenIdConnect`.
- T017, T024 and T030 edit `app/Enums/InstanceSettingEnum.php`: one at a time.
- T012, T021 and T027 share one file, as do T010 and T025, T011 and T026, T015, T023 and T028: within a story they are parallel, across stories they are not.
- Story 2 needs nothing from story 1. Story 3 needs T016 only for the order of the cases.
- T034 after every task adding copy (T017, T024, T030).

## Strategy

Phase 2 alone carries Microsoft through the new package, so story 2 is three tests and one case. The MVP is Phases 1 to 3. If T012 fails on the stored setting, apply the instance settings before the package boots (plan, Risks) before going further.
