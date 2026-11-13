# Research: Password as the default registration method

Nothing in the Technical Context needed outside research. Each decision below was settled by reading
the code the change reaches.

## 1. Where the default lives

**Decision**: flip `RegisterViewModel::registrationMethod()`.
**Rationale**: it is the single place the screen reads its starting method from, and it already
carries the method across a refused attempt through the flashed `registration_method`.
**Alternatives considered**: a config value or a flag per instance (the spec rules out both).

## 2. Knowing that the browser can create a passkey

**Decision**: one expression in the screen's own `x-data`,
`typeof globalThis.PublicKeyCredential === 'function'`, the same test as `resources/js/passkey.js:15`.
**Rationale**: the answer exists only in the browser, and the screen already owns an Alpine scope.
**Alternatives considered**: mounting the `passkey()` component on the screen (it fetches the client
library as soon as it starts, for every visitor, most of whom now never ask for a passkey); a shared
helper for one expression used twice (`CLAUDE.md` says to repeat it).

## 3. The screen with scripts off

**Decision**: the password parts are the markup's resting state, and everything about the passkey
carries `x-cloak`.
**Rationale**: `[x-cloak]` is `display: none` in `resources/css/app.css`. Today the cloak sits on the
password fields, so a browser without scripts gets a form posting to the password route with no
password field. Moving the cloak fixes it without a second form.
**Alternatives considered**: a `<noscript>` copy of the fields (two forms to keep in step).

## 4. Wording of the control

**Decision**: "Use a passkey instead", then "Set a password instead".
**Rationale**: both are keys the application already translates (`register.blade.php:121`,
`register-passkey.blade.php:60`), so no locale gains a string.
**Alternatives considered**: "Use a password instead" (a new key in every locale for no gain).

## 5. What is deleted

**Decision**: the warning notice, the "Other options" text, and
`RegisterViewModel::passkeysUnavailableReason()`.
**Rationale**: the first two are shown by this screen only (searched `resources` and `app`). The method
has no caller for this view model, in views or in tests; the four other view models keep theirs.
`php artisan monica:localize` drops the two keys from every locale and reports them.

## 6. What is left alone

**Decision**: neither registration controller changes.
**Rationale**: each route validates on its own. `RegistrationPasskeyController::create` has no
`password` rule and passes none to `CreateAccount`, so a password typed before switching is discarded
by construction. The hidden fields are still submitted, empty, exactly as today.

## 7. Accessibility

**Decision**: keep `x-show` for the swap, add `focus-ring` to the control, keep focus on it.
**Rationale**: `x-show` sets `display: none`, which removes the fields from the tab order and from
the accessibility tree (FR-009). The control is the element activated, it stays in place and its new
name states the way back, so focus does not need to move. `focus-ring` is the named recipe
`.claude/rules/components.md` asks for, and the control has no focus style today.
**Alternatives considered**: a live region announcing the switch (the explaining sentence is read in
order once the reader moves on, and a region toggled by `display` is announced unreliably).

## 8. What the tests can and cannot prove

**Decision**: the view model test owns the default; the controller test asserts the control's copy is
present over a secure connection and absent otherwise.
**Rationale**: `.claude/rules/testing.md` lets a view test assert whether a block is present. The
browser check and the screen with scripts off need a browser, and the suite has none, so they are
manual steps in [quickstart.md](quickstart.md).
