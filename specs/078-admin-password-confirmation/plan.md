# Implementation Plan: Password confirmation on the admin screens

**Branch**: `2026-10-13-admin-password-confirmation` | **Date**: 2026-10-13 | **Spec**: [admin-password-confirmation.spec.md](admin-password-confirmation.spec.md)

## Summary

The seven gated admin actions open `x-modal.confirm-password` in place of the redirect to the full confirmation screen. Each button dispatches the existing `confirm-password` event, the card resubmits the form, and the server rule stays as it is (R5). Two orderings needed design, because a warning must come before the card: inline two step warnings move from `@submit` to the button's click (R1), and `x-modal.warning` gains an opt in that closes it and hands its form to the card (R2). The card gets a `description` prop for the admin wording (R3).

No new package, no migration, no JavaScript change.

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Nothing is written. The view models gain only `credentials()`, which reads (R4). |
| II. Simplicity | Two optional props on existing components; no new component, no JavaScript change. |
| III. Tested | The tests listed under Project Structure. The card already has its own tests and endpoint tests. |
| IV. Laravel conventions | Stock `password.confirm` stays the rule. |
| V. Localized, warns first | Two new sentences go through `monica:localize`. Every existing warning still comes first (FR-007). |
| VI to VIII | Not touched. |
| IX. Accessible | Reuses the card's dialog contract (named dialog, focus trap, Escape, live region). One dialog is open at a time (R2). A refused button keeps `aria-disabled` and opens nothing (R6). |
| X. API | No capability is added. The admin space has no API, and none is introduced. |
| XI. Short record | No `data-model.md`, no `contracts/`. The feature adds no data, and its interface is the two props and one event below. |

Gate: passes before and after design.

## Interface

| Element | Contract |
| --- | --- |
| `x-modal.confirm-password` | New prop `description` (`?string`, default `null` = today's sign-in sentence). The props `offersPassword` and `offersPasskey` are unchanged. |
| `x-modal.warning` | New prop `confirmsPassword` (`bool`, default `false`). When true, the danger button runs `open = false` and dispatches `confirm-password` with `{ form: $el.form }`. |
| `x-delete-action` | Forwards `confirms-password` through `$attributes`. No new prop. |
| `confirm-password` window event | Unchanged: `detail.form` is the form to resubmit. |
| `ConfigurationViewModel`, `FlagsViewModel` | Constructor takes `User $user`; new `credentials(): ConfirmPasswordViewModel`. |

## Project Structure

```text
resources/views/components/modal/confirm-password.blade.php   description prop
resources/views/components/modal/warning.blade.php            confirmsPassword prop on the danger button
resources/views/app/admin/configuration/index.blade.php       card at top; Save runs warning then dispatch (R1, R6);
                                                              Clear, Put back, orphan removal pass confirms-password
resources/views/app/admin/configuration/_telegram.blade.php   Generate a secret (warning then dispatch), Register (dispatch);
                                                              Check stays ungated
resources/views/app/admin/flags/index.blade.php               card at top; both x-modal.warning pass confirms-password
app/ViewModels/Admin/ConfigurationViewModel.php               User in constructor, credentials()
app/ViewModels/Admin/FlagsViewModel.php                       User in constructor, credentials()
app/Http/Controllers/App/Admin/InstanceSettingController.php  passes $request->user()
app/Http/Controllers/App/Admin/FlagController.php             passes $request->user()
lang/*.json                                                   through monica:localize
tests/Feature/Views/Components/Modal/ConfirmPasswordTest.php  description default and override
tests/Feature/Views/Components/Modal/WarningTest.php          confirmsPassword dispatches; absent by default
tests/Feature/Views/App/Admin/ConfigurationTest.php           card present with admin wording; hooks per R1, R6
tests/Feature/Views/App/Admin/FlagsTest.php                   new: card present, both warnings opt in
tests/Unit/ViewModels/Admin/{Configuration,Flags}ViewModelTest.php  constructor call sites
tests/Feature/Controllers/App/Admin/InstanceSettingControllerTest.php  unconfirmed destroy and orphan removal refused
tests/Feature/Controllers/App/Admin/FlagActivationControllerTest.php   unconfirmed deactivation refused
```

**Structure Decision**: the card is placed per screen, as on security and profile, and not in the admin layout (R4).

## Notes for implementation

- Add the dispatch on the Save button with the existing button component, as `security/_api-keys.blade.php:98` does. Never put it on `x-dynamic-component`.
- `ConfigurationTest` counts `takesSomethingAway($refs.value.value)` exactly. That count moves with R1. Rewrite the assertion to describe the behaviour, not to recount.
- After a warning is accepted and the card cancelled, `confirming` stays true, so the next Save goes straight to the card. The administrator has already seen the warning, so this is intended.
- Run the whole suite. `x-modal.warning` is used in 27 files, and the new prop's default must leave them unchanged.
