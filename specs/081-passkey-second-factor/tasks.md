# Tasks: Security keys as a second factor

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/routes.md](contracts/routes.md)
**Tests**: required by the constitution (III). Each task names the test that proves it. Build credentials in tests the way `tests/Unit/Actions/VerifyPasskeyTest.php` does.

## Phase 1: Foundational, keys exist and count

- [X] T001 Create `database/migrations/2026_10_18_000000_create_security_keys_table.php` with `up()` only, per data-model.md: `id`; `foreignIdFor(User::class)->constrained()->cascadeOnDelete()` indexed; `name` "string (255)"; `credential_id` "string, unique"; `credential` json; `last_used_at` "timestamp, nullable"; timestamps
- [X] T002 Create `app/Models/SecurityKey.php` per data-model.md: full model docblock, `#[Fillable]` name, credential_id, credential, last_used_at; casts `credential` array and `last_used_at` datetime; `user(): BelongsTo<User, $this>`; `hasBeenUsed()`; `resolveRouteBinding()` limited to `user_id = Auth::id()` as `app/Models/Passkey.php` does
- [X] T003 [P] Create `database/factories/SecurityKeyFactory.php` mirroring `database/factories/PasskeyFactory.php`
- [X] T004 `app/Models/User.php`: `securityKeys(): HasMany<SecurityKey, $this>` ordered oldest first, `usesAuthenticatorApp()` answering `two_factor_confirmed_at !== null`, and `usesTwoFactorAuthentication()` answering `usesAuthenticatorApp()` or at least one key (research R4)
- [X] T005 [P] `tests/Unit/Models/SecurityKeyTest.php` (`hasBeenUsed`, binding answers null for another user's key) and `tests/Unit/Models/UserTest.php` (two-factor on with the app, with a key, with both, off with neither; a passkey alone leaves it off)
- [X] T006 `tests/Feature/RouteProtectionTest.php`: add `SecurityKey::class` to `$modelsScopedByTheirController`
- [X] T007 `app/Http/Controllers/Api/Auth/LoginController.php` `passesSecondFactor()`: ask `$user->usesTwoFactorAuthentication()` instead of `two_factor_confirmed_at`; `tests/Feature/Controllers/Api/Auth/LoginControllerTest.php` proves a key-only account is refused without `code` and accepted with a recovery code (spec FR-019)
- [X] T008 [P] `app/Enums/UserActionEnum.php`: cases `SecurityKeyAdded`, `SecurityKeyRenamed`, `SecurityKeyRemoved`, `SecurityKeyUsed` with wordings "Added the security key called :name", "Renamed a security key to :name", "Removed the security key called :name", "Confirmed a sign in with the security key called :name"; extend `tests/Unit/Enums/UserActionEnumTest.php`
- [X] T009 [P] `app/Enums/EmailType.php`: cases for the two emails of T020, following the existing passkey cases

**Checkpoint**: a seeded key turns two-factor authentication on everywhere it is reported, and the API sign-in asks for a code.

## Phase 2: User Story 1, answer the challenge with a key (P1) MVP

**Independent test**: an account holding a seeded security key signs in with its password, the prompt opens, touching the key signs it in.

- [X] T010 [US1] Create `app/Actions/GenerateSecondFactorOptions.php` (research R3): request options from `Passkeys::relyingPartyId()` and `Passkeys::timeout()`, `allowCredentials` the user's security keys then passkeys, user verification `discouraged`, a 32 byte challenge
- [X] T011 [US1] Create `app/Actions/VerifySecondFactorKey.php` (research R3): inside a transaction, find the raw id among the user's security keys then passkeys with `lockForUpdate`, refuse anything else with the refusal sentence, refuse a deactivated user, check with `WebAuthn::assertionValidator()`, store the new record and `last_used_at`, dispatch `LogUserAction` with `SecurityKeyUsed` or `PasskeyConfirmed` and the name; dispatch no package event
- [X] T012 [P] [US1] `tests/Unit/Actions/GenerateSecondFactorOptionsTest.php` and `tests/Unit/Actions/VerifySecondFactorKeyTest.php`: options list both kinds and nobody else's; a key without user verification passes; a passkey passes and logs `PasskeyConfirmed`; another user's key, an unknown id, a replayed counter and a deactivated user are refused; `last_used_at` set; `Event::fake()` proves `PasskeyVerified` is not dispatched
- [X] T013 [US1] Create `app/Http/Controllers/App/Auth/TwoFactorKeyOptionsController.php` `show()` per contracts/routes.md: 422 with the expiry message and the sign-in URL when `twoFactor.user.id` is missing, else the options as `WebAuthn::toBrowserArray()` kept in the session under `twoFactor.keyOptions`
- [X] T014 [US1] `app/Http/Controllers/App/Auth/TwoFactorChallengeController.php` `create()` (research R6): when the request has `credential`, verify through T011 against `twoFactor.keyOptions` and answer `{"redirect"}`, 422 `{"message"}` on refusal; otherwise the code path as today; both end in one private completion that logs in with `twoFactor.remember`, forgets the session keys, regenerates and sends `NewLoginDetected` once
- [X] T015 [US1] `routes/auth.php`: `GET two-factor-challenge/key-options` named `auth.twoFactorKeyOptions.show` with `throttle:6,1`, next to `auth.twoFactor.new`
- [X] T016 [P] [US1] `tests/Feature/Controllers/App/Auth/TwoFactorKeyOptionsControllerTest.php` and extend `TwoFactorChallengeControllerTest.php`: options answer 422 without a pending sign-in; a valid key signs in, regenerates the session, sends one `NewLoginDetected`, honours `twoFactor.remember`; a refused key answers 422 and leaves the person signed out; the code path unchanged
- [X] T017 [US1] `app/ViewModels/Auth/TwoFactorViewModel.php`: take the pending user; `offersKey()` (keys or passkeys held and `PasskeyAvailability::onThisInstance()`), `usesAuthenticatorApp()`, the options and submit URLs, the code field label (six digit code, or recovery code for a key-only account); `tests/Unit/ViewModels/Auth/TwoFactorViewModelTest.php`
- [X] T018 [US1] `resources/js/passkey.js`: `secondFactor()` calling `Passkeys.verify({ routes: { options, submit } })` then `window.location = redirect`; `init()` runs it once the library loaded when the config asks; announce "opening" before the call, `config.text.waiting` on a `NotAllowedError` nobody pressed for, `config.text.cancelled` and refusals after (research R7)
- [X] T019 [US1] `resources/views/app/auth/two-factor-challenge.blade.php`: when `offersKey()`, a block above the form with the passkey component configured for auto-open, a live region, and a "Use your security key" button that calls `secondFactor()`; description and notice reworded for key, app and recovery codes; the code form below as today; extend the challenge controller test with the button, the live region `aria-live` and the `x-data` hook

**Checkpoint**: story 1 works with factory keys and passkeys; nobody can add a key yet.

## Phase 3: User Story 2, add and manage security keys (P2)

**Independent test**: add a key that has no PIN, rename it, remove it; two-factor turns on and off with the first and last factor.

- [X] T020 [P] [US2] Create `app/Notifications/SecurityKeyAdded.php` and `SecurityKeyRemoved.php` on `app/Notifications/Messages/MailMessage`, implementing `HasEmailType`, worded as `PasskeyAdded` and `PasskeyRemoved`; `tests/Unit/Notifications/SecurityKeyAddedTest.php`, `SecurityKeyRemovedTest.php`
- [X] T021 [US2] Create `app/Actions/GenerateSecurityKeyOptions.php` extending `Laravel\Passkeys\Actions\GenerateRegistrationOptions`, overriding `authenticatorSelection()` (resident key and user verification discouraged, no attachment preference) and `excludedCredentials()` (the user's security keys) (research R2)
- [X] T022 [US2] Create `app/Actions/CreateSecurityKey.php`: validate with `WebAuthn::attestationValidator()`, refuse a `credential_id` already in `security_keys`, issue recovery codes with `RecoveryCodes::generate()` only when `usesTwoFactorAuthentication()` was false before, store, log `SecurityKeyAdded`, send `SecurityKeyAdded`; return the key and whether it turned two-factor on
- [X] T023 [P] [US2] Create `app/Actions/UpdateSecurityKey.php` as `app/Actions/UpdatePasskey.php`, logging `SecurityKeyRenamed`; name "string (255)"
- [X] T024 [US2] Create `app/Actions/DestroySecurityKey.php`: owner check, delete, clear `two_factor_recovery_codes` when no factor remains, log `SecurityKeyRemoved`, send `SecurityKeyRemoved`
- [X] T025 [US2] `app/Actions/ConfirmTwoFactorAuthentication.php`: issue codes only when two-factor was off; `app/Actions/DisableTwoFactorAuthentication.php`: clear codes only when no key remains (research R5)
- [X] T026 [P] [US2] Tests for T021 to T025 under `tests/Unit/Actions/`: options exclude existing keys and relax both criteria; first key issues codes and second keeps them; duplicate refused; rename and removal of another user's key throw `ModelNotFoundException`; removing the last factor clears codes; confirming the app with a key present keeps the codes; turning the app off with a key present keeps two-factor on
- [X] T027 [US2] Create `app/Http/Controllers/App/Settings/Account/Security/SecurityKeyOptionsController.php` `show()` and `SecurityKeyController.php` `create()`, `update()`, `destroy()` per contracts/routes.md; options 404 when `PasskeyAvailability::onThisInstance()` is false
- [X] T028 [US2] `routes/web.php`: the four routes of contracts/routes.md beside `settings.passkeys.update`, in a group behind `EnsurePasswordIsConfirmedWhenThereIsOne`, as passkey management, so an account without a password is never asked for one
- [X] T029 [P] [US2] `tests/Feature/Controllers/App/Settings/Account/Security/SecurityKeyControllerTest.php` and `SecurityKeyOptionsControllerTest.php`: create answers 201 with `redirect` to recovery codes for the first key and to security otherwise, 422 on a refused credential; rename validation; destroy redirects with the status; options 404 on an insecure request
- [X] T030 [US2] `app/ViewModels/Settings/Account/Security/SecurityViewModel.php`: `securityKeys()` rows (name, added, last used or never), `usesAuthenticatorApp()`, and the removal warning: "Two-factor authentication stays on with your other security keys or your authenticator app." or "Two-factor authentication turns off, and your recovery codes stop working." (spec FR-009); `tests/Unit/ViewModels/Settings/Account/Security/SecurityViewModelTest.php`
- [X] T031 [US2] Create `resources/views/app/settings/account/security/_security-keys.blade.php` and `_security-key-row.blade.php` from `_passkeys.blade.php` and `_passkey-row.blade.php`: list, inline rename, `x-delete-action` with the T030 warning, an "Add a security key" button wired to the passkey component with the T027 routes, the line saying a built-in authenticator counts (FR-002), `_passkeys-unavailable` on an insecure connection
- [X] T032 [US2] `resources/views/app/settings/account/security/index.blade.php`, `_two-factor-on.blade.php`, `_two-factor-off.blade.php`: three rows, app, keys, recovery codes while on (research R8); the app's "Turn it off" warning takes the T030 sentence
- [X] T033 [P] [US2] Extend `tests/Feature/Controllers/App/Settings/Account/Security/SecurityControllerTest.php`: the keys list and the add button render, the warning names the last factor, nothing renders on an insecure request

**Checkpoint**: both stories work end to end.

## Phase 4: Polish

- [X] T034 `specs/002-webauthn-auth/spec.md`: rewrite FR-019 to let a passkey answer the challenge and name security keys, adjust the FR-021 sentences and the matching assumption, citing spec 081
- [X] T035 [P] `spec/models/002-user-done.md`: what turns two-factor authentication on, and the `security_keys` table
- [X] T036 [P] `.claude/rules/translations.md`: glossary row "security key" filled for every locale column, distinct from "passkey"
- [X] T037 Run `php artisan monica:localize` and translate every reported string in every configured locale per `.claude/rules/translations.md`; never edit `lang/*.json` keys by hand
- [X] T038 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress`, `php artisan monica:localize --check` and the whole suite
- [ ] T039 Check by hand with a key without a PIN, a key with a PIN and a built-in authenticator (SC-002), in Safari and one Chromium browser, with a screen reader on the challenge (SC-005)

## Phase 5: Review fixes (2026-10-18)

- [X] T040 `app/Actions/VerifyTwoFactorCode.php`: refuse the code of an authenticator app nobody confirmed, which a key would otherwise let through; `VerifyTwoFactorCodeTest`
- [X] T041 `GenerateSecurityKeyOptions::excludedCredentials()`: exclude passkeys too, so a built-in authenticator is never overwritten; "This key already answers for your account."
- [X] T042 Journal: `AuthenticatorAppAdded` and `AuthenticatorAppRemoved`, and `TwoFactorEnabled` and `TwoFactorDisabled` written by a key that turns it on or off
- [X] T043 Key row and "Get new codes" ask for the password only when there is one; `settings.recoveryCodes.create` behind `EnsurePasswordIsConfirmedWhenThereIsOne`
- [X] T044 Challenge: a throttle budget of its own for the key options, a translated 429, no prompt and focus on the field after a refused code, the FR-013 sentence for the app plus a key over http
- [X] T045 The app confirmed beside a key keeps the codes out of sight; the codes screen names the key; removal warnings say what happens; focus and `aria-describedby` on the key removal step
- [X] T046 Translate the five strings T041 to T045 added, in every configured locale, and run `monica:localize --check`

## Dependencies

- Phase 1 before everything. T002 before T003 to T006; T004 before T007.
- US1 (Phase 2) and US2 (Phase 3) need only Phase 1; US1 is tested with factory keys.
- T014 needs T011 and T013; T019 needs T017 and T018. T022 and T024 need T020; T027 needs T021 to T024; T031 needs T030.
- Polish last; T037 after every string is written.

## Parallel examples

- Phase 1: T003, T005, T008, T009 once T002 exists.
- US1: T012 and T016 beside their implementation tasks; T017 beside T013.
- US2: T020 and T023 first, then T026 and T029 beside T027.

## Strategy

MVP is Phase 1 plus US1, testable with factory keys, but only US2 lets anybody add a key, so both ship together in one pull request.
