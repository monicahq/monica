# Implementation Plan: Adopt laravel/passkeys

**Branch**: `021-adopt-laravel-passkeys` | **Date**: 2026-09-05 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/021-adopt-laravel-passkeys/spec.md`

## Summary

Replace `asbiin/laravel-webauthn` with `laravel/passkeys`, taking its routes, controllers and browser
client. The application keeps four seams: a `VerifyPasskey` subclass that refuses a deactivated or SSO
user before the signature is checked, a `Passkey` model whose route binding scopes to its owner, two
rebound response contracts, and three listeners on the package's events. Rename and passkey-only
registration stay on their own routes because the package offers neither. Every decision below is
argued once in [research.md](research.md) and not repeated here.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13

**Primary Dependencies**: `laravel/passkeys ^0.2.1` (in), `asbiin/laravel-webauthn 6.0` (out),
`@laravel/passkeys ^0.4.0` (in), `@simplewebauthn/browser` (direct → transitive),
`web-auth/webauthn-lib 5.3.6` (unchanged)

**Storage**: MySQL/SQLite. `webauthn_keys` dropped, `passkeys` created from the package's migration.

**Testing**: PHPUnit. 207 assertions across 31 files touch passkeys today.

**Target Platform**: server-rendered web, https only. WebAuthn needs a secure context.

**Project Type**: Blade + Alpine monolith

**Constraints**: no visible change beyond FR-009's two removals and the URL moves in FR-010; WCAG 2.2
AA on every screen touched; nine locales must stay in step.

**Scale/Scope**: ~30 application files, ~31 test files, one migration, two config files.

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the logic | Pass. Journal and email move to listeners on `PasskeyRegistered`/`Verified`/`Deleted`, which is what `HandlePasskeyLogin` and `HandlePasskeyRegistration` already are. `UpdatePasskey` stays an action. |
| II. Simplicity over abstraction | Pass, and net negative: two dead repositories, the RS1 workaround, a credential-repository layer and the dual-form credential id matching all go. |
| III. Tested before merged | Pass. SC-001 keeps every assertion except FR-009's. |
| IV. The Laravel way | **One deviation**, route naming; see Complexity Tracking. The package migration ships with `constrained()` and a `down()`, which Principle IV exempts for published migrations. |
| V. Translatable | Pass. FR-014 runs `monica:localize`; the only strings that move are the ones FR-009 deletes. |
| VI / VII | Not engaged. No template, version, vault or record concept is touched. |
| IX. Accessible | Pass. The Alpine component and its live regions survive (research D4), and typed errors sharpen the copy. |
| X. API parity | Neutral. Passkeys have no JSON API today and gain none; nothing regresses. |
| XI. Short written record | Pass, every cap met: spec 120, research 80, plan 97, data-model 42, contracts 46, quickstart 47. |

Re-evaluated after Phase 1: unchanged. The one deviation was known before design and is not a
consequence of it.

## Project Structure

```text
specs/021-adopt-laravel-passkeys/
├── plan.md, spec.md, research.md, data-model.md, quickstart.md
└── contracts/http.md
```

### Source

```text
app/
├── Actions/            VerifyPasskey (new, the D1 subclass), RefusePasskeyLogin and UpdatePasskey
│                       (kept), DestroyPasskey (deleted, becomes a listener)
├── Helpers/            PasskeyAvailability (loses its config('webauthn.enable') branch)
├── Http/
│   ├── Controllers/    App/Auth/RegistrationPasskeyController (kept), Settings/.../PasskeyController
│   │                   (loses destroy, keeps update)
│   ├── Middleware/     EnsurePasskeyLoginIsAllowed (deleted; the pipeline it hooked is gone)
│   └── Responses/      PasskeyLoginResponse, PasskeyDeletedResponse (rebound contracts)
├── Listeners/          HandlePasskeyLogin, HandlePasskeyRegistration, HandlePasskeyRemoval (new);
│                       RecordPasskeyUse, NotifyOfPasskeyAnomaly, ExplainAPasskeyBoundElsewhere (deleted)
├── Models/             Passkey (new, replaces WebauthnKey), User (trait + contract swap)
├── Notifications/      PasskeyCounterAnomaly (deleted, FR-009)
└── Providers/          AppServiceProvider trimmed; Webauthn/ deleted entirely

config/                 auth.php driver back to eloquent; webauthn.php deleted; passkeys.php published
database/migrations/    add_webauthn deleted; package migration published
resources/
├── js/passkey.js       same component, calls Passkeys.register/verify and maps typed errors
└── views/app/          security screens and login/register point at the new URLs
routes/                 web.php loses the destroy route; auth.php unchanged
```

**Structure Decision**: no new directory anywhere, which the architecture rules require. The
`VerifyPasskey` subclass goes in `app/Actions/`, an allowed folder and the honest description of it.
`app/Http/Responses/` already exists and keeps two classes. `app/Providers/Webauthn/` is deleted.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Route names `passkey.login-options`, `passkey.store`, `passkey.destroy` are kebab-cased with a dot, where `.claude/rules/code-style.md` requires camelCase | FR-010 adopts the package's routes as it registers them; renaming means re-declaring all seven ourselves | `Passkeys::ignoreRoutes()` with our own route file keeps the convention, and was the recommended option at `/speckit-specify`. The requester chose wholesale adoption. Reversing it is a spec change, not a plan decision. |
| `passkeys/confirm/options` and `passkeys/confirm` are registered and never used | They come with the package's route file, which is all-or-nothing | Same as above. They are inert: `auth`-guarded, and no screen calls them. |
