# Research: Security keys as a second factor

## R1. Security keys live in a table of their own

- **Decision**: a `security_keys` table and an `App\Models\SecurityKey` model, the same columns as `passkeys`. Nothing is added to `passkeys`.
- **Rationale**: `Laravel\Passkeys\Actions\VerifyPasskey::getPasskey()` finds a credential by its id alone, with no user, at passkey sign-in. A key stored in `passkeys` that checks a PIN would sign somebody in, which FR-016 forbids. A separate table makes FR-016 hold by construction, and keeps the package's `excludeCredentials`, `allowCredentials` and confirmation lookups untouched.
- **Alternatives**: a `kind` column on `passkeys` filtered in an override of `getPasskey()`. Rejected: every package lookup would need the same filter, and spec 021 FR-012 already deleted code filtering on such a column.

## R2. Enrolment reuses the package's builders with relaxed criteria

- **Decision**: `App\Actions\GenerateSecurityKeyOptions` extends `Laravel\Passkeys\Actions\GenerateRegistrationOptions` and overrides two public methods: `authenticatorSelection()` (resident key discouraged, user verification discouraged, no attachment preference) and `excludedCredentials()` (the person's security keys and passkeys: a platform authenticator keeps one discoverable credential per account and user handle, so registering a key on a device that holds the passkey would replace that passkey; excluded, the device refuses and its passkey keeps answering). `App\Actions\CreateSecurityKey` checks the response with `Laravel\Passkeys\Support\WebAuthn::attestationValidator()`, refuses a credential id already in `security_keys`, and stores the record as `StorePasskey::createPasskey()` does.
- **Rationale**: attestation stays `none` and the algorithms stay ES256 and RS256, so FR-003 holds without new code. Discouraged, not forbidden: an authenticator that verifies its owner anyway is still accepted (FR-002).
- **Risk**: `Support\WebAuthn` is internal to a v0.2.1 package. Accepted, as spec 021 accepted adopting it pre 1.0.

## R3. The challenge verifies keys and passkeys itself

- **Decision**: `App\Actions\GenerateSecondFactorOptions` builds request options with `allowCredentials` set to the person's security keys and passkeys, and user verification discouraged. `App\Actions\VerifySecondFactorKey` finds the raw id among that person's security keys, then their passkeys, checks it with `WebAuthn::assertionValidator()`, stores the new credential record and `last_used_at`, and writes the journal.
- **Rationale**: the package's `VerifyPasskey` dispatches `PasskeyVerified`, whose listener `HandlePasskeyLogin` records a passkey sign-in and sends the new sign-in email, before the challenge controller sends its own. Verifying here dispatches nothing, so one email goes out (FR-015). The validator still refuses a replayed counter.
- **Journal**: a key writes the new `SecurityKeyUsed`; a passkey writes the existing `PasskeyConfirmed`, whose wording ("Confirmed who they are with the passkey called :name") already fits.

## R4. "Two-factor authentication is on" is one method

- **Decision**: `User::usesTwoFactorAuthentication()` answers `two_factor_confirmed_at !== null || securityKeys()->exists()`. A new `usesAuthenticatorApp()` keeps the old meaning for the screens that need it.
- **Rationale**: `LoginController`, `MagicLinkController`, `SsoController`, `ConsumeMagicLink`, `SignInWithProvider`, `SettingsViewModel`, `SecurityViewModel`, `Admin\AccountViewModel` and `RegenerateTwoFactorRecoveryCodes` already ask this method, so FR-007 reaches all of them with one change.
- **The one exception**: `Api\Auth\LoginController::passesSecondFactor()` reads `two_factor_confirmed_at` directly. It switches to the method, or a key-only account gets a token with no second factor. `VerifyTwoFactorCode` already falls back to recovery codes when there is no secret, which is FR-019's recovery code path.

## R5. Recovery codes follow the first and the last factor

- **Decision**: whichever action turns two-factor authentication on issues the codes, asking `usesTwoFactorAuthentication()` before it writes: `ConfirmTwoFactorAuthentication` and `CreateSecurityKey`. Both leave existing codes alone otherwise (FR-008). `DisableTwoFactorAuthentication` and `DestroySecurityKey` clear the codes only when nothing remains. Adding the first key redirects to `settings.recoveryCodes.show` with the codes, as confirming the app does.
- **Changes shipped behaviour**: confirming the app today always mints new codes. With a key already present it now keeps them, which is the point of FR-008.

## R6. One challenge route answers both a code and a key

- **Decision**: `POST two-factor-challenge` keeps its URL and its `throttle:6,1`. When the request carries `credential`, `TwoFactorChallengeController::create()` verifies the key and answers `{"redirect": ...}` as JSON for the browser client; otherwise it reads `code` as today. The part that signs in, regenerates the session and sends the email is shared by both paths. `GET two-factor-challenge/key-options` is the one new route, on `TwoFactorKeyOptionsController::show()`, with the same throttle.
- **Rationale**: the sign-in completion is security critical; written twice it would drift. The throttle and the expiry are the code challenge's by construction (FR-014).
- **Alternatives**: a second POST route on a second controller. Rejected for the duplication above.

## R7. The prompt opens on load, and says so

- **Decision**: `resources/js/passkey.js` gains a `secondFactor()` method calling `Passkeys.verify({ routes: { options, submit } })`, run from `init()` once the library has loaded, and again from a "Use your security key" button. The live region says the prompt is opening before the call, and explains a refusal after it. A `NotAllowedError` from a browser refusing a call nobody pressed for (Safari) is announced as waiting for a press, not as a failure.
- **Rationale**: the decision of 2026-10-18 (spec Clarifications). The library is imported in `init()` already, because Safari refuses a WebAuthn call that waited on the network after a press.
- The component starts only when `PasskeyAvailability::onThisInstance()` and the person holds a key or a passkey; otherwise the screen is the code form it is today (FR-013).

## R8. The security screen

- **Decision**: the two-factor section holds three rows: the authenticator app (on or off), the security keys (a list built like `_passkeys.blade.php`, with `x-delete-action`), and the recovery codes while two-factor authentication is on. The warning on removing the app or a key reads one of two sentences, chosen in `SecurityViewModel`: one when another factor remains, one when it is the last (FR-009).
- Enrolment, rename and removal sit behind `EnsurePasswordIsConfirmedWhenThereIsOne`, as passkey management does.

## R9. Emails, journal and words

- **Decision**: notifications `SecurityKeyAdded` and `SecurityKeyRemoved` implementing `HasEmailType`, with two new `EmailType` cases. `UserActionEnum` gains `SecurityKeyAdded`, `SecurityKeyRenamed`, `SecurityKeyRemoved` and `SecurityKeyUsed`. "Security key" gets a glossary row in `.claude/rules/translations.md`, filled for every locale before `monica:localize` is run.

## R10. No feature flag

- Rejected: the capability ships whole in one change, so there is nothing to keep out of sight while it is built (`.claude/rules/flags.md`).
