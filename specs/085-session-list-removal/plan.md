# Implementation Plan: List every session of an account and remove any of them

**Branch**: `claude/speckit-session-list-removal-d142fc` | **Date**: 2026-10-26 | **Spec**: [spec.md](spec.md)

## Summary

A new section of Security and access lists the live rows of the `sessions` table that belong to the
signed in person, marks the one in hand and offers to remove every other. `DestroySession` deletes the
row, retires the account's remembered credential and journals ([R3](research.md)). `DestroyOtherSessions`
checks the password, calls the guard's `logoutOtherDevices`, deletes every other row and journals each. A small helper puts
the device into words ([R5](research.md)). No migration, no dependency, no API.

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel 13.35 |
| Storage | `sessions` and `users.remember_token`, both unchanged; no migration |
| Configuration | `session.driver` and `session.lifetime`, read; nothing added |
| Front end | Blade, `x-row`, `x-badge`, `x-delete-action`, `x-notice`; no new JavaScript |
| Testing | PHPUnit on the `array` session driver; scenarios in [quickstart.md](quickstart.md) |
| Scale | A handful of rows per account; every operation touches one row |

No open question remains. `data-model.md` is omitted: no table, column or state is added, and what a
row carries is in the spec's Key Entities and FR-001. `contracts/` is omitted: no API, and no interface
another system reads.

## Constitution Check

| Principle | How it holds |
| --- | --- |
| I. Actions | `DestroySession` holds the rules; `SessionController` validates nothing and delegates; `SessionListViewModel` only reads |
| II. Simplicity | Two actions, two exceptions, one view model, one helper, two controllers, four partials, two enum cases |
| III. Tested | Action, helper, view model, controller and the recaller end to end, per `.claude/rules/testing.md` |
| IV. Laravel | The database session handler and the remember token as the framework writes them; a `DELETE` route and `destroy` |
| V. Localized, safe | Removal behind `x-delete-action` and the password confirmation; every phrase through `__()` and `trans_choice()` |
| VI. Nothing underfoot | Only the account's own action ends its sessions |
| VIII. Nothing disappears silently | The removal is asked, warned and journalled. The removed device sees the sign in screen and nothing more: a session that is gone can carry no message. Accepted |
| IX. Accessible | `x-delete-action` traps focus and closes on Escape; the device in hand is told by a text badge, not by colour |
| X. API | **Set aside**: no endpoint in this feature, an exception the maintainer decided on 2026-10-29 and the pull request states; the logic sits in two actions, so an endpoint later is transport |
| XI. Short record | spec 92, plan, research and quickstart under their caps; two artifacts omitted, said above |

Re-checked after Phase 1: unchanged. **Rule set aside**: Principle X, above.

## Project Structure

```text
specs/085-session-list-removal/
├── spec.md, plan.md, research.md, quickstart.md
└── checklists/requirements.md

app/Actions/DestroySession.php                                            # new (R3, R4)
app/Exceptions/CannotDeleteTheCurrentSession.php                          # new (R4)
app/Actions/DestroyOtherSessions.php                                       # new: everywhere else
app/Exceptions/PasswordIsWrong.php                                        # new
app/Helpers/DeviceDescription.php                                         # new (R5)
app/Enums/UserActionEnum.php                                              # SessionRemoved (R8)
app/Http/Controllers/App/Settings/Account/Security/SessionController.php  # new: destroy
app/Http/Controllers/App/Settings/Account/Security/OtherSessionController.php  # new: destroy
app/Http/Controllers/App/Settings/Account/Security/SecurityController.php # hands over the session id
app/ViewModels/Settings/Account/Security/SessionListViewModel.php         # new (R1, R2)
app/ViewModels/Settings/Account/Security/SecurityViewModel.php            # sessions()
resources/views/app/settings/account/security/index.blade.php             # the section (R6)
resources/views/app/settings/account/security/_sessions.blade.php         # new
resources/views/app/settings/account/security/_session-row.blade.php      # new
resources/views/app/settings/account/security/_sessions-unavailable.blade.php  # new
resources/views/app/settings/account/security/_sign-out-other-devices.blade.php  # new: panel footer
routes/web.php                                                            # settings.sessions.destroy (R6), settings.otherSessions.destroy
docs/portal/en/10-your-account-and-security/13-when-something-looks-wrong.md   # removing a device
docs/FEATURES.md
lang/*.json                                                               # through monica:localize

tests/Unit/Actions/DestroySessionTest.php
tests/Unit/Helpers/DeviceDescriptionTest.php
tests/Unit/ViewModels/Settings/Account/Security/SessionListViewModelTest.php
tests/Feature/Controllers/App/Settings/Account/Security/SessionControllerTest.php
tests/Unit/Actions/DestroyOtherSessionsTest.php
tests/Feature/Controllers/App/Settings/Account/Security/OtherSessionControllerTest.php
tests/Feature/Auth/RemovedDeviceSignOutTest.php                           # (R7)
```

**Structure Decision**: the existing Laravel layout; nothing new at the top level. `spec/models/` gets
no file: a session is the framework's record, not a model of the product.

## Complexity Tracking

None.
