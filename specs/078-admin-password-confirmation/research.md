# Research: Password confirmation on the admin screens

## R1. Where the card is triggered on a form with a two step warning

**Decision**: the warning check moves from the form's `@submit` to the Save button's `@click.prevent`, and the same handler dispatches `confirm-password` once the warning has been shown. The form keeps no `@submit` handler.

**Rationale**: the card resubmits with `form.requestSubmit()`, which fires `submit` but not `click`. With the warning on `@submit` and the card on `@click`, the card opens first and the warning cancels the resubmission, which reverses FR-007. A `@submit` interceptor for the card instead loops, because `requestSubmit()` fires `submit` again. Pressing Enter in a field clicks the default button, so the warning still runs on Enter.

**Alternatives considered**: keeping `@submit` and adding a pass-through flag the card sets before resubmitting (two pieces of state across two components for one ordering).

## R2. Gated actions behind `x-modal.warning`

**Decision**: `x-modal.warning` gains a `confirmsPassword` prop, false by default. When it is true, the danger button gets `@click.prevent="open = false; $dispatch('confirm-password', { form: $el.form })"`. `x-delete-action` passes it through its attributes and declares nothing new.

**Rationale**: the warning is teleported to `body` and holds `x-trap.noscroll.inert`. If it stayed open, the card would be inert behind it. Closing it in the same handler keeps one dialog open at a time and puts the warning before the card (FR-007, FR-010). The form stays in the DOM while hidden, so `requestSubmit()` still reaches it.

**Alternatives considered**: a second warning component for admin (two copies of a dialog contract, which `components.md` forbids at the second file); asking for the credential before the warning (reverses FR-007).

## R3. The card's wording

**Decision**: `x-modal.confirm-password` gains a `description` prop. When it is null, the card shows today's sign-in sentence. The admin screens pass "What you are about to change decides how this instance behaves for everybody, so we ask for a credential first. You will not be asked again for a little while." The title "Confirm it is you" fits both and stays.

**Rationale**: FR-006 asks for admin wording. The spec's assumptions keep the security and profile text as it is today.

**Alternatives considered**: a slot (one sentence needs no markup); a `purpose` enum selecting from fixed wordings (indirection for two strings).

## R4. Where the card's credentials come from

**Decision**: `ConfigurationViewModel` and `FlagsViewModel` take the `User` in their constructor and expose `credentials(): ConfirmPasswordViewModel`, as `SecurityViewModel` and `ProfileViewModel` do. Each screen places the card once at its top.

**Rationale**: this matches the convention already on the security and profile screens. The admin layout serves ungated screens too, and has no user of its own to read the offers from.

**Alternatives considered**: placing the card in `layouts/admin.blade.php`; passing a second view variable from the controller (two objects for one screen).

## R5. The server rule

**Decision**: nothing changes on the server. The stock `password.confirm` middleware still guards the seven routes. The passkey confirmation writes `auth.password_confirmed_at` (`PasskeyConfirmationController:70`), so a passkey satisfies it.

**Rationale**: FR-008 and FR-009. The status endpoint and the middleware read the same session key and the same `auth.password_timeout`, so the card and the rule agree on what counts as recent.

## R6. Buttons that look disabled

**Decision**: the dispatch is rendered only on a button that can act. A setting that cannot be changed, and a Telegram button that is unavailable, keep `aria-disabled` and `onclick="return false"` with no Alpine handler.

**Rationale**: an inline `return false` does not stop an Alpine listener, so an unguarded dispatch would open the card from a button that says it cannot be used. `@js()` is not compiled inside a component tag (`components.md`), so the guard is a Blade `@if` around two renderings of the button. It is not an expression inside the handler.

## R7. What the resubmission carries

**Decision**: no change to `confirm-password.js`.

**Rationale**: `requestSubmit()` without a submitter drops the button's `name` and `value`. None of the gated buttons carry either, so the request matches what the administrator typed (FR-002).
