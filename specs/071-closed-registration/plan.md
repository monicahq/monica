# Implementation Plan: Closing Registration on an Instance

**Branch**: `071-closed-registration` (git branch `2026-10-07-closed-registration`) | **Date**: 2026-10-07 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/071-closed-registration/spec.md`

## Summary

One switch for the whole instance, `monica.registration_enabled`, defaulting to enabled, supplied by
`MONICA_REGISTRATION_ENABLED` and overridable from the Configuration screen. While it is off, a
middleware answers not found on the three sign up routes and the API register endpoint,
`SignInWithProvider` refuses somebody it has never seen, and four screens stop offering an account. It
copies the link by email setting throughout; the decisions are in
[research.md](./research.md) and the API change in [contracts/api.md](./contracts/api.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel (installed major)  
**Primary Dependencies**: Blade, Alpine.js, Laravel Socialite (provider sign in), Sanctum (API)  
**Storage**: the existing instance settings table; no migration  
**Testing**: PHPUnit, feature and unit, per `.claude/rules/testing.md`  
**Target Platform**: Linux server, hosted and self hosted alike  
**Project Type**: web application with a JSON API  
**Performance Goals**: none beyond today; the check is one configuration read per request  
**Constraints**: nothing changes for an instance that sets nothing (SC-004)  
**Scale/Scope**: about 20 files touched, one new class, no new table

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the business logic | Pass. The provider refusal lives in `SignInWithProvider`; route gating is a middleware, as for links by email (research D2) |
| II. Simplicity | Pass. One key, one middleware, four one line reads, no helper |
| III. Tested before merged | Pass. Every route, the action, the enum and the view models get a test |
| IV. Laravel conventions | Pass. Middleware alias, configuration key, inline route middleware |
| V. Localized and safe | Pass. Three new strings go through `monica:localize`; opening warns first (FR-002) |
| VI. Nothing changes under anybody's feet | Pass. No existing account is touched (FR-007) |
| VIII. Nothing disappears silently | Pass. Nothing is deleted; the switch reverts |
| IX. Accessible | Pass. An offer is absent, not half drawn; research D4 says why not `aria-disabled` |
| X. Everything through the API | Pass. The register endpoint obeys the switch |
| XI. Short record | Pass. `data-model.md` omitted: the one setting is research D1 |
| Product invariants | Pass. No invitation, same product hosted and self hosted (FR-010) |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/071-closed-registration/
├── plan.md
├── research.md
├── quickstart.md
├── contracts/api.md
└── tasks.md            # /speckit-tasks
```

### Source Code (repository root)

```text
config/monica.php                                   registration_enabled
.env.example                                        MONICA_REGISTRATION_ENABLED=true
app/Enums/InstanceSettingEnum.php                   RegistrationEnabled: group, kind, description, warning, switchedOn
app/Http/Middleware/EnsureRegistrationIsEnabled.php new
bootstrap/app.php                                   alias registration
routes/auth.php, routes/api.php                     middleware on four routes
app/Actions/SignInWithProvider.php                  refuseWhenRegistrationIsDisabled()
app/ViewModels/Auth/LoginViewModel.php              registrationIsEnabled()
app/ViewModels/Marketplace/MarketplaceViewModel.php registrationIsEnabled()
app/ViewModels/Docs/DocsViewModel.php               registrationIsEnabled()
app/View/Components/MarketplaceLayout.php           registrationIsEnabled()
resources/views/app/auth/login.blade.php
resources/views/app/marketplace/index.blade.php
resources/views/layouts/marketplace.blade.php
resources/views/app/docs/index.blade.php
resources/views/app/admin/configuration/index.blade.php  switchedOn in takesSomethingAway()
resources/docs/api/02-authentication.php
lang/*.json                                         through monica:localize only
docs/portal/ROADMAP.md, docs/FEATURES.md

tests/Feature/Controllers/App/Auth/RegistrationControllerTest.php         new and create answer 404
tests/Feature/Controllers/App/Auth/RegistrationPasskeyControllerTest.php  create answers 404
tests/Feature/Controllers/Api/Auth/RegistrationControllerTest.php         404, no account
tests/Unit/Actions/SignInWithProviderTest.php        refuses the unknown, signs in the connected
tests/Unit/Enums/InstanceSettingEnumTest.php         group, kind, warnsWhen
tests/Unit/ViewModels/{Auth/LoginViewModelTest,Docs/DocsViewModelTest,Marketplace/MarketplaceViewModelTest}.php
tests/Feature/Views/Layouts/MarketplaceLayoutTest.php  offer absent when disabled
tests/Feature/Views/App/Admin/ConfigurationTest.php  the switchedOn mode reaches the form
```

**Structure Decision**: the existing Laravel layout; nothing new at the top level.

## Complexity Tracking

No violation to justify.
