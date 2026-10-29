# Quickstart: List every session of an account and remove any of them

## Automated

```bash
php artisan test --compact tests/Unit/Actions/DestroySessionTest.php tests/Unit/Helpers/DeviceDescriptionTest.php tests/Unit/ViewModels/Settings/Account/Security
```

```bash
php artisan test --compact tests/Feature/Controllers/App/Settings/Account/Security/SessionControllerTest.php tests/Feature/Auth/RemovedDeviceSignOutTest.php tests/Feature/RouteProtectionTest.php
```

Then the gates in `CLAUDE.md`: `monica:localize --check`, PHPStan, Pint.

Tests write their rows into `sessions` themselves, and set `session.driver` to `database` where a view
model or the screen is read ([R7](research.md)).

## What each scenario proves

| Spec | Test | Expected |
| --- | --- | --- |
| US1.1, FR-001, FR-002 | `SessionListViewModelTest` | Two rows, the one in hand first and marked; device, address and last use filled |
| US1.2 | View model on a row with no user agent | "Unknown device" |
| US1.3, FR-003 | View model on a row older than `session.lifetime` | Not listed |
| US1.4, FR-003 | View model with another user's row | Not listed |
| FR-001 | `DeviceDescriptionTest` | Chrome on macOS, Safari on iPhone, Firefox on Windows, Edge on Windows; "Unknown device" for null and for garbage |
| US2.1, FR-005, FR-008 | `DestroySessionTest` | Row gone, `remember_token` changed, one `SessionRemoved` with `device` only |
| US2.5, FR-003 | Destroy another user's row | `ModelNotFoundException`, row kept, token unchanged |
| US2.4, FR-002 | Destroy the current session id | `CannotDeleteTheCurrentSession`, row kept |
| Edge: twice | Destroy a row already gone | `ModelNotFoundException` |
| US2.2, FR-005, FR-006 | `RemovedDeviceSignOutTest` | A recaller cookie with the old token signs in before, is sent to sign in after; a live session of the person carries on |
| US2.1, US2.6, FR-007 | `SessionControllerTest` | `DELETE` with the password confirmed redirects with the flash; without it, redirects to the confirmation screen |
| US2.3, FR-004 | Screen | The row's form points at `settings.sessions.destroy`; the warning names the device and says every device will ask to sign in again |
| FR-009 | Screen with `session.driver` = `file` | The notice, no row, no form |
| FR-010 | `monica:localize --check` | Nothing missing |

## By hand

1. Sign in on two browsers. On the first, open Settings, Security and access: both are listed, the
   first marked "This device".
2. Remove the second, confirm the password, confirm the warning: it leaves the list, and the flash says
   it is signed out at its next request.
3. Reload the second browser: the sign in screen. Sign in again with the box ticked: it is listed again.
4. On the first browser, wait for the session to run out (`SESSION_LIFETIME`, two hours by default, or
   set it to 1): the sign in screen, since remembering ended for every device.
5. Set `SESSION_DRIVER=file` and open the screen: the notice, and no row.
