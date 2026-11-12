# Tasks: Sign in with Apple

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/configuration.md](contracts/configuration.md), [quickstart.md](quickstart.md)

**Format**: `[ID] [P?] [Story] Description`. `[P]` means another file and no dependency on an unfinished task. Tests are written first and must fail before the implementation they cover.

## Phase 1: Setup

- [X] T001 Ask before changing dependencies, then in `composer.json` add `socialiteproviders/apple` to `require-dev` and a `suggest` line ("Required to sign in with Apple. Without it Apple is not offered."); run `composer update socialiteproviders/apple -q` and note the resolved version in `research.md` R6

## Phase 2: Foundational (blocks both stories)

- [X] T002 [P] In `config/services.php` add `apple` after `google`: `client_id` (`APPLE_CLIENT_ID`), `client_secret` as `null`, `team_id` (`APPLE_TEAM_ID`), `key_id` (`APPLE_KEY_ID`), `private_key` (`APPLE_PRIVATE_KEY`), `redirect` (`APPLE_REDIRECT_URI`, default `/sso/apple/callback`)
- [X] T003 [P] In `config/monica.php` make `sso_installed` leave `apple` out unless `SocialiteProviders\Apple\Provider` exists
- [X] T004 [P] In `tests/Unit/Enums/SsoProviderEnumTest.php` prove Apple sits after Google and before Entra, that `brandName()` answers "Apple", and that `requiredSettings()` answers `client_id`, `team_id`, `key_id`, `private_key`, `redirect`
- [X] T005 In `app/Enums/SsoProviderEnum.php` add `Apple = 'apple'` after `Google`, with its `brandName()` and `requiredSettings()`
- [X] T006 Update `tests/Unit/ViewModels/Auth/LoginViewModelTest.php`, `tests/Unit/ViewModels/Settings/Account/Security/SecurityViewModelTest.php` and any other test that lists or counts providers

**Checkpoint**: `php artisan test --compact --filter=Sso` passes with the eight existing providers.

## Phase 3: User Story 1, sign in with an Apple account (P1, MVP)

**Independent test**: with the package and the four settings, Apple is offered, and a POST from Apple ends in an account signed in or a provider connected.

- [X] T007 [P] [US1] In `tests/Unit/Helpers/SsoProviderAvailabilityTest.php` prove Apple is offered with its five settings and no `client_secret`, and absent with `private_key` missing
- [X] T008 [P] [US1] In `tests/Feature/Auth/SsoDriverRegistrationTest.php` prove `Socialite::driver('apple')` resolves `SocialiteProviders\Apple\Provider`, and that a key given on one line, with `\n`, or without its header reaches `services.apple.private_key` as a PEM block; skip when the class is absent
- [X] T009 [P] [US1] In `tests/Feature/Controllers/App/Auth/SsoControllerTest.php` (the relay is a method of `SsoController`, see T013): a POST to `/sso/apple/callback` with no CSRF token answers 303 to `auth.sso.callback`, carries `code`, `state` and `error`, shows no name or email address in the address, and sets no session cookie
- [X] T010 [P] [US1] In `tests/Feature/Controllers/App/Auth/SsoControllerTest.php` prove a relayed `user` reaches the request decrypted before Socialite is asked, that a forged one is dropped and sign in still answers, and that a connection started from the security settings ends connected
- [X] T011 [P] [US1] In `tests/Unit/Enums/InstanceSettingEnumTest.php` prove the config key, kind and group of `apple_client_id`, `apple_team_id`, `apple_key_id` (Text) and `apple_private_key` (Secret)
- [X] T012 [US1] In `app/Providers/AppServiceProvider.php`, when `apple` is in `monica.sso_installed`, listen for `SocialiteWasCalled`, rewrite `services.apple.private_key` as PEM unless it is a readable file (research R3), and extend Socialite with the package's provider
- [X] T013 [US1] Add `relay()` to `app/Http/Controllers/App/Auth/SsoController.php`, next to `redirect()` and `callback()`, since no route of the application is invokable: 303 to `auth.sso.callback` with `code`, `state`, `error`, and `user` encrypted with `Crypt::encryptString`
- [X] T014 [US1] In `routes/auth.php` add `POST sso/{provider}/callback` to `[SsoController::class, 'relay']`, named `auth.sso.relay`, `whereIn` `apple` only, with `sso` and `throttle:6,1`, without the `web` group; add it to the public list of `tests/Feature/RouteProtectionTest.php`
- [X] T015 [US1] In `app/Http/Controllers/App/Auth/SsoController.php` make `callback()` decrypt a relayed `user` into the request before asking Socialite, dropping a value that does not decrypt
- [X] T016 [P] [US1] In `app/Enums/InstanceSettingEnum.php` add the four Apple cases pointing at `services.apple.{client_id,team_id,key_id,private_key}`, with env names, group, kind, description and `providerIsInstalled()` answering for `apple`
- [X] T017 [US1] Update `tests/Feature/Views/App/Admin/ConfigurationTest.php` where it lists or counts settings

**Checkpoint**: story 1 passes on its own.

## Phase 4: User Story 2, an instance without the package (P1)

**Independent test**: with `apple` out of `monica.sso_installed` and the four settings supplied, Apple is on no screen and nothing fails.

- [X] T018 [P] [US2] In `tests/Unit/Helpers/SsoProviderAvailabilityTest.php` prove Apple is absent when not in `sso_installed`, whatever is supplied, and that the other providers are still offered
- [X] T019 [P] [US2] In `tests/Feature/Controllers/App/Auth/SsoControllerTest.php` prove `/sso/apple/redirect` and the relay answer not found when Apple is not installed
- [X] T020 [P] [US2] In `tests/Unit/Enums/InstanceSettingEnumTest.php` prove `providerIsInstalled()` is false for the four Apple settings when `apple` is not in `sso_installed`
- [X] T021 [P] [US2] Covered by T027 instead: the listener is chosen at boot, before a test can change `sso_installed`, so only a run without the package proves it
- [X] T022 [US2] Fix whatever T018 to T021 show, in `app/Providers/AppServiceProvider.php` or `app/Http/Middleware/EnsureSsoProviderIsAvailable.php`; Phases 2 and 3 are expected to satisfy them already

**Checkpoint**: both stories pass, with the package and without.

## Phase 5: Polish

- [X] T023 [P] In `.env.example` add `APPLE_CLIENT_ID`, `APPLE_TEAM_ID`, `APPLE_KEY_ID`, `APPLE_PRIVATE_KEY` and add `apple` to the list of providers in the comment
- [X] T024 [P] Name Apple, say its package is installed separately, what to supply and that Apple refuses a local address, in `docs/portal/en/10-your-account-and-security/5-sign-in-with-a-provider.md`, `docs/portal/en/10-your-account-and-security/13-when-something-looks-wrong.md` and `docs/portal/en/2-start-here/2-create-your-account.md`
- [X] T025 Run `php artisan monica:localize`, translate what it reports in every `lang/*.json`, then pass `php artisan monica:localize --check`
- [X] T026 Pass `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent` and the whole suite with `php artisan test --compact`
- [X] T027 Run `composer remove --dev socialiteproviders/apple -q`, pass the whole suite again (FR-003), then restore `composer.json` and `composer.lock` and run `composer install -q`
- [X] T028 Record the feature in `docs/FEATURES.md` with the feature-documentator skill
- [X] T030 Accept a secret generated in advance as `APPLE_CLIENT_SECRET` (`apple_client_secret`, Secret) when no key is supplied, in `config/services.php`, `app/Enums/SsoProviderEnum.php`, `app/Enums/InstanceSettingEnum.php`, `.env.example`, the guide and every `lang/*.json`, with tests in `tests/Unit/Enums/`, `tests/Unit/Helpers/SsoProviderAvailabilityTest.php` and `tests/Feature/Auth/SsoDriverRegistrationTest.php`
- [ ] T029 Run the manual check of `quickstart.md` on a public HTTPS address, in a real browser

## Dependencies

- Phase 1, then Phase 2, then the stories, then Phase 5.
- T005 before T006 and before every task of Phases 3 and 4. T004 fails until T005 lands.
- T013 before T014, T014 before T015. T009 fails until T014, T010 until T015.
- T012 needs T001: the provider class must exist for T008 to run and not skip.
- T007 and T018 share one file, as do T010 and T019, T011 and T020: within a story they are parallel, across stories they are not.
- Story 2 adds tests only and needs T012, T014 and T016 to have something to refuse.
- T025 after T016, the only task adding copy.

## Strategy

The MVP is Phases 1 to 3. If T009 shows that `withoutMiddleware('web')` does not remove the group by name, move the relay route to a file loaded without it before going further. T029 is the only proof that the session survives the relay: if its fourth row fails, T013 answers with a page that navigates by itself instead of a 303 (plan, Risks), and nothing else changes.
