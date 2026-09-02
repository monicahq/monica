# Tasks: Copy a New API Key to the Clipboard

**Input**: `/specs/011-copy-api-key/` (spec.md, plan.md, research.md)

**Format**: `[ID] [P?] [Story?] Description with file path`. `[P]` means it touches no file another unfinished task touches.

**Tests**: included, and not optional here. Principle III makes a test part of the change.

## Phase 1: Setup

- [X] T001 Run `php artisan test --compact tests/Feature/Controllers/App/Settings/Account/Security/ApiKeyControllerTest.php tests/Unit/Actions/CreateApiKeyTest.php` and record the baseline, which is twelve green: the commit named in research.md was amended to update `CreateApiKeyTest` alongside the action, so the format question is settled and the gate starts clean
- [X] T002 Build the assets with `bun run build` so Tailwind sees the panel's markup in `resources/views/app/settings/account/security/_api-keys.blade.php`

---

## Phase 2: Foundational

**Purpose**: the panel structure both stories report through. Neither the confirmation nor the failure has anywhere to appear until this is in place.

- [X] T003 Wrap the printed key block in one Alpine scope holding `state` with `idle`, `copied` and `failed`, put `x-ref="key"` on the bordered paragraph, and keep the value tight against its tags so `textContent` carries no template whitespace, in `resources/views/app/settings/account/security/_api-keys.blade.php`
- [X] T004 Add the `role="status" aria-live="polite"` line under the box, empty while `state` is `idle`, in `resources/views/app/settings/account/security/_api-keys.blade.php`
- [X] T005 Assert the box, its `select-all`, and the live region reach the response when a key has just been made, extending `it_hands_the_key_over_once` in `tests/Feature/Controllers/App/Settings/Account/Security/ApiKeyControllerTest.php`

**Checkpoint**: the key is still printed once, in a box a click selects whole, with somewhere for an outcome to be announced.

---

## Phase 3: User Story 1 - Take the key away in one gesture (P1) 🎯 MVP

**Independent Test**: make a key, activate the control, paste elsewhere, and confirm the pasted text matches the printed value exactly.

- [X] T006 [US1] Add the copy control beside the box as an `<x-button type="button" size="small">` labelled `__('Copy to the clipboard')`, as the share link screen does for the same act, in `resources/views/app/settings/account/security/_api-keys.blade.php`
- [X] T007 [US1] Have the control call `window.copyText($refs.key.textContent)` and set `state` from what it returns, so nothing is claimed that did not happen, in `resources/views/app/settings/account/security/_api-keys.blade.php`
- [X] T008 [US1] Say `__('Copied')` in the live region while `state` is `copied` and return to `idle` after a moment, leaving the button label unchanged, in `resources/views/app/settings/account/security/_api-keys.blade.php`
- [X] T009 [US1] Assert the copy control, its label and the confirmation string reach the panel, and that the printed key is still there beside them, in `tests/Feature/Controllers/App/Settings/Account/Security/ApiKeyControllerTest.php`

**Checkpoint**: a new key reaches the clipboard in one click and the screen says so.

---

## Phase 4: User Story 2 - Be told when the clipboard was out of reach (P2)

**Independent Test**: reach the screen over plain http, or deny the browser permission, activate the control, and confirm the screen reports a failure and the key is still there to select by hand.

- [X] T010 [US2] Write the failure sentence in the live region while `state` is `failed`, naming what to do instead, and leave it up until the next attempt rather than timing it out, in `resources/views/app/settings/account/security/_api-keys.blade.php`
- [X] T011 [US2] Assert the failure sentence reaches the panel, and that both outcomes are sentences rather than a colour, in `tests/Feature/Controllers/App/Settings/Account/Security/ApiKeyControllerTest.php`

**Checkpoint**: a copy that does not happen says so, and the key is still there to take by hand.

---

## Phase 5: Polish

- [X] T012 Run `php artisan monica:localize` and translate the failure sentence in the nine locales under `lang/`, following the typography rules of each
- [ ] T013 Open the panel on a plain http origin and confirm the textarea fallback puts the key on the clipboard and the screen confirms it, then deny the browser's clipboard permission and confirm the failure sentence appears instead, on the panel in `resources/views/app/settings/account/security/_api-keys.blade.php`
- [ ] T014 Walk the panel with the keyboard alone and with a screen reader, at 200% zoom and at phone width, and fix what that finds in `resources/views/app/settings/account/security/_api-keys.blade.php`

**T013 and T014 stand open on purpose.** What was verified without a browser: all four Alpine expressions parse under the same async compilation Alpine performs, the helper they call already carries the insecure origin fallback and is shipped in three components, the control is a real `<button type="button">` carrying the design system's focus ring, both outcomes are sentences in one always present polite region rather than a colour, the row wraps, and the key box still breaks on any character so it cannot overflow at 200%. What nobody has done is put a real screen reader on it, open it at 200% on a real phone, or reach it over a plain http origin that is not localhost, which browsers treat as secure. That needs a person and a deployment.
- [X] T015 Run `php artisan monica:localize --check`, `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse` and the full suite

---

## Dependencies

- T001 and T002 are independent of everything and of each other.
- T003 blocks T004, and both block every task below: the whole feature lives in one Alpine scope and one live region.
- T005 depends on T003 and T004. T009 depends on T006, T007 and T008. T011 depends on T010.
- Phase 3 and Phase 4 both depend on Phase 2 and on nothing else, so either could be built first. Phase 4 is second because a failure to report is worth nothing until there is a copy to fail.
- T012 depends on T010, the only task that writes new copy. T013 depends on Phases 3 and 4. T015 runs last.
- No task carries `[P]`. The work touches three files, two of which several tasks edit, so nothing can run beside anything else. Phases 3 and 4 are the only pair a second person could take in parallel, and they would collide in the same Blade file.
- MVP is Phases 1, 2 and 3. Worth shipping there: a key reaches the clipboard in one click, and on an origin where that cannot happen the button simply stays silent, which is what the six other copy controls in the application already do. Phase 4 is what makes this one better than them.
