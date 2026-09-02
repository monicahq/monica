# Research: Copy a New API Key to the Clipboard

Nine decisions. One of them is a finding about this branch rather than a design choice.

## Putting the value on the clipboard

**Decision**: call `window.copyText()` from `resources/js/app.js`, which returns whether it managed.

**Rationale**: it already answers FR-004 and FR-005 on its own. It falls back to a hidden textarea and `document.execCommand` where `navigator.clipboard` is absent, which is every self hosted instance reached over plain http, and it reports the outcome instead of assuming it. Three api-docs components already use it.

**Alternatives**: calling `navigator.clipboard.writeText` directly, as the share link and the two template exports do. Rejected: it resolves nothing on an insecure origin and those screens claim success they have not had.

## Three states rather than a boolean

**Decision**: one Alpine scope on the panel holding `state` with `idle`, `copied` and `failed`.

**Rationale**: FR-004 needs a failure that is not the same thing as "not copied yet", which a boolean cannot express. The panel sits inside `#api-keys`, which alpine-ajax replaces rather than morphs, so the state starts at `idle` after a key is made and there is nothing to reset by hand.

**Alternatives**: the `copied: false` boolean the six sibling copy controls use. Rejected: it has no third state to put a failure in.

## Where the outcome is announced

**Decision**: one `role="status" aria-live="polite"` line under the box, empty at rest, carrying the confirmation or the failure sentence. The button label does not change.

**Rationale**: FR-008 wants both outcomes announced and FR-010 forbids colour as the only carrier, and a sentence satisfies both. `field-type.blade.php:243` already uses a separate polite region for exactly this.

**Alternatives**: `aria-live` on the button with a swapped label, which is what the six siblings do. Rejected: it announces the control renaming itself and leaves the failure sentence nowhere to live. `role="alert"` was rejected as interrupting for something the user just asked for.

## Which value is copied

**Decision**: read `$refs.key.textContent` from the printed box, the way the share link screen reads its URL.

**Rationale**: FR-002 asks for exactly what is shown, and one source cannot drift from a second one. It also satisfies FR-011 without effort, since nothing leaves the browser. The value must stay tight against its tags in the Blade, because `textContent` would otherwise carry the template's newline and indentation.

**Alternatives**: a `data-key` attribute, rejected as printing the secret into the DOM twice for no gain.

## The box and the click already exist

**Decision**: keep the bordered `bg-canvas` paragraph and its `select-all`, and cover both by test.

**Rationale**: FR-006 and FR-007 describe what `_api-keys.blade.php:38` already renders. `user-select: all` is what makes one click take the whole value. Nothing is built here; a test is what stops it being removed by the next person who restyles the panel.

## No shared copy control yet

**Decision**: write the control inline in the partial, as the six others in the application are written.

**Rationale**: an `<x-copy-button>` used by one screen while six stay inline would leave two patterns instead of one. Naming the concept is the right follow up, once the failure state has proved itself here, and it means touching six screens the spec deliberately left alone.

## The words

**Decision**: reuse `Copy to the clipboard` and `Copied`, already translated in all nine locales. One new sentence is needed for the failure.

**Rationale**: the copy is the same act with the same name as elsewhere. Only the outcome the product has never reported is new.

## What the suite can cover

**Decision**: assert the rendered contract through `ApiKeyControllerTest`, which already renders this panel in `it_hands_the_key_over_once`.

**Rationale**: PHPUnit is the whole suite. There is no Dusk, no Pest and no JavaScript runner, so no test can execute a clipboard write or take either branch of `copyText`. What is testable is the box, its `select-all`, the button, the live region and the failure sentence being in the response.

**Alternatives**: adding a browser or JavaScript test runner, which the constitution makes an amendment rather than a task.

## The token format question, settled during the work

**Decision**: settled by the author, not by this plan. The shortened token stays and its test follows it.

**Rationale**: the commit that strips Sanctum's `id|` prefix off the plain text token in `CreateApiKey` was amended, while this feature was being built, to update `CreateApiKeyTest` alongside the action. That answers the question this section originally handed back: the format change is intended.

**What it leaves**: two assertions elsewhere still expect the old format, in `Api\ApiKeyControllerTest::it_creates_an_api_key` and `Api\Auth\RegistrationControllerTest::it_hands_the_token_back_as_a_string_and_nothing_else`, and `resources/docs/api/03-authentication.php` documents a token as `1|f9aB2c...` in two places, which the product no longer emits. The action feeds three surfaces, so the drift is public. Out of scope here, and reported to the author rather than fixed in passing, because half of it (the tests without the docs) would be worse than none.
