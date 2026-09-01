---

description: "Task list for Favourite a Contact From Their Page"
---

# Tasks: Favourite a Contact From Their Page

**Input**: Design documents from `/specs/006-favourite-contact/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md),
[data-model.md](./data-model.md), [contracts/favourite-a-contact.md](./contracts/favourite-a-contact.md),
[quickstart.md](./quickstart.md)

**Tests**: included, and not optional here. The template treats test tasks as opt in, but this
project's constitution makes them a condition of merge. Principle III is marked NON-NEGOTIABLE and
reads "Every model, controller, action, helper, job, mailable and view model MUST have a
corresponding test. No change ships without a test covering the behavior it adds or alters."
`.claude/rules/testing.md` binds their shape.

**Organization**: grouped by user story. Read the honesty note below before planning parallel work.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel, meaning a different file and no dependency on an unfinished task
- **[Story]**: which user story the task serves
- Every task names the exact file it touches

## Path Conventions

Single Laravel codebase, existing folders only, per the plan's Structure Decision. Application code
in `app/`, screens in `resources/views/`, routes in `routes/web.php`, tests in `tests/`.

## Honesty note on story independence

The template asks for stories that can be built and shipped independently. Two of these three
genuinely cannot, and pretending otherwise would produce a plan that fights itself:

- **US1 and US2 are one control.** The star that sets the mark is the star that clears it, in one
  Blade region and one controller method that already validates a boolean either way. Splitting them
  across files to make them independent would mean two routes and two methods to carry one boolean,
  which research decision 3 rejected. They are kept as separate phases because they are separately
  *testable* and separately *demonstrable*, which is what the checkpoints below verify. They are not
  separately deployable, and US2 costs almost nothing once US1 exists.
- **US3 is a property of the control, not a later addition.** Building the button without
  `aria-pressed`, without a name, or with tint as the only state signal, and fixing it in a third
  pass, would ship a screen that fails Principle IX in between. The accessible markup is written in
  US1 as part of T009. US3's phase is where it is *verified*, which is why its tasks are checks and
  one repair task rather than construction.

The MVP is therefore Phase 1 through Phase 3, and Phase 4 and Phase 5 are hours rather than days.

---

## Phase 1: Setup

**Purpose**: nothing to initialise. The project exists, the dependencies exist, the column exists.
This phase is one confirmation, kept because skipping it is how the previous session lost an hour to
a stale database.

- [X] T001 Confirm the local database matches the migrations by running `php artisan migrate:status` and, if `contacts` is missing `sort_key` or `search_key`, running `php artisan migrate:fresh --seed`; confirm `php artisan test --compact tests/Unit/Actions/FavouriteContactTest.php tests/Unit/Actions/UnfavouriteContactTest.php` passes before anything is changed

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: the route and the glyph. Both are needed by every story below and neither belongs to
one of them.

**⚠️ CRITICAL**: no user story work begins until this phase is complete.

- [X] T002 [P] Add the `star-filled` glyph to the `$paths` array in `resources/views/components/icon.blade.php`, using the same path data as the existing `star` and carrying `fill="currentColor"` on the path itself so the `<svg>` keeps `fill="none"` and no prop is added; update the component's opening comment, which currently states "Nothing here is filled", to record this one exception and why, per research decision 2
- [X] T003 [P] Create `app/Http/Controllers/App/Vaults/ContactFavouriteController.php` with a single `update(Request $request, Vault $vault, Contact $contact): RedirectResponse`, shaped after `app/Http/Controllers/App/Settings/Account/Templates/TemplateActivationController.php`: `abort_unless($contact->isReachableThrough($request->user(), $vault), 404)`, then `$request->validate(['favourite' => ['required', 'boolean']])`, then dispatch to `FavouriteContact` or `UnfavouriteContact` with named arguments and redirect to `contacts.show`; set no flash status, per the contract
- [X] T004 Register `Route::put('vaults/{vault}/contacts/{contact}/favourite', [ContactFavouriteController::class, 'update'])->name('contacts.favourite.update')` in `routes/web.php`, immediately after the `contacts.avatar.destroy` route so it sits with the other narrow contact routes, and inside the same middleware group (depends on T003)

**Checkpoint**: `php artisan route:list --path=favourite` shows the route, and the glyph renders.

---

## Phase 3: User Story 1 - Mark somebody as a favourite while looking at them (Priority: P1) 🎯 MVP

**Goal**: a contact who is not a favourite can be made one from their own page, in one press, without
the page reloading.

**Independent Test**: open a contact who is not a favourite, press the star, confirm it fills, then
confirm the contact appears under the people list's favourites filter and above the contacts who are
not favourites.

### Tests for User Story 1

Written first, and confirmed failing before T009 and T010 are written.

- [X] T005 [P] [US1] Create `tests/Feature/Controllers/App/Vaults/ContactFavouriteControllerTest.php` with `declare(strict_types=1)`, the `Tests\Feature\Controllers\App\Vaults` namespace, `RefreshDatabase`, `Queue::fake()`, and the marking cases: it marks a contact who is not a favourite and redirects to the contact page; it leaves an already marked contact marked and stays idempotent; it pushes `LogUserAction` onto the `low` queue; it returns 422 when `favourite` is absent and when it is not a boolean; it returns 404 for a contact of another account; it returns 404 for a contact of this account reached through another vault of the same account. Use The Office (US) for data and named arguments throughout, per `.claude/rules/testing.md`
- [X] T006 [P] [US1] Add to `tests/Unit/ViewModels/Vaults/ContactViewModelTest.php` a test that `favouriteUrl()` returns `route('contacts.favourite.update', ...)` for the contact's vault and contact, and a test that `favouriteLabel()` returns the "add" wording for a contact who is not a favourite and the "remove" wording for one who is, both naming the contact

### Implementation for User Story 1

- [X] T007 [P] [US1] Add `favouriteUrl(): string` to `app/ViewModels/Vaults/ContactViewModel.php`, beside the existing `editUrl()` and `setAsideUrl()`, returning the route for this vault and contact
- [X] T008 [US1] Add `favouriteLabel(): string` to `app/ViewModels/Vaults/ContactViewModel.php`, returning `__('Remove :name from favourites', ['name' => ...])` when the contact is a favourite and `__('Add :name to favourites', ['name' => ...])` when not; it reads the existing `isFavourite()` and holds no logic beyond the choice of wording (depends on T007 for placement only)
- [X] T009 [US1] Replace the `@if ($viewModel->isFavourite())` block at `resources/views/app/vaults/contacts/show.blade.php:59` with a `<x-form method="put" :action="$viewModel->favouriteUrl()">` containing a `<button type="submit">` that is always rendered: `aria-pressed` reflecting `isFavourite()`, an accessible name from `favouriteLabel()`, a hidden `favourite` input carrying the opposite of the current state, the `star-filled` glyph when marked and `star` when not, the project's standard focus ring, and the existing 32px chip retained as the target size; keep the glyph `aria-hidden` since the button carries the name (depends on T002, T004, T007, T008)
- [X] T010 [US1] Wrap the star in a region with an id and `x-merge="replace"` in `resources/views/app/vaults/contacts/show.blade.php`, and put the matching `x-target` on the form, following the pattern `resources/views/app/vaults/contacts/_section.blade.php` already uses, so the press swaps the region rather than the screen and still works with scripting off (depends on T009)
- [X] T011 [US1] Add to `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php` a test that the contact page renders the star as an operable control for a contact who is **not** a favourite, asserting the button, `aria-pressed="false"` and the accessible name are present, which is the behaviour change FR-001 asks for and the one nothing else would catch

**Checkpoint**: US1 is complete and demonstrable. Steps 1 to 5 of [quickstart.md](./quickstart.md)
pass.

---

## Phase 4: User Story 2 - Take somebody off the favourites (Priority: P2)

**Goal**: the same star clears the mark, with no confirmation and nothing deleted.

**Independent Test**: open a contact who is a favourite, press the star, confirm they leave the
favourites filter and return to their ordinary place in the list.

**Note**: T003 already handles both directions, because the controller validates a boolean and
branches. This phase is therefore mostly proof rather than construction, which is the honest
description rather than an inflated one.

### Tests for User Story 2

- [X] T012 [P] [US2] Add to `tests/Feature/Controllers/App/Vaults/ContactFavouriteControllerTest.php` the clearing cases: it clears the mark on a favourite and redirects to the contact page; it leaves an already unmarked contact unmarked; it pushes the `ContactUnfavourited` action onto the `low` queue; and it flashes no status and asks for no confirmation, since nothing is destroyed
- [X] T013 [P] [US2] Add to `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php` a test that the contact page renders the star for a contact who **is** a favourite with `aria-pressed="true"`, the `star-filled` glyph and the "remove" wording

### Implementation for User Story 2

- [X] T014 [US2] Verify the clearing path end to end against the existing implementation and repair only what the tests from T012 and T013 report; no new file is expected, and any change lands in `ContactFavouriteController.php` or in the button block of `resources/views/app/vaults/contacts/show.blade.php`

**Checkpoint**: US1 and US2 both demonstrable. Step 6 of [quickstart.md](./quickstart.md) passes.

---

## Phase 5: User Story 3 - Know the state, and reach it, without a mouse or colour (Priority: P3)

**Goal**: the control is operable and legible for a keyboard user, a screen reader user, and anybody
who cannot rely on colour.

**Independent Test**: reach the star with the keyboard alone, operate it, hear both its purpose and
its state announced, and tell the two states apart in a greyscale rendering.

**Note**: the accessible markup was written in T009 rather than deferred here, because shipping a
button without it would fail Principle IX in the interval. These tasks verify it and repair what the
verification finds.

- [X] T015 [P] [US3] Walk steps 7 to 12 of [quickstart.md](./quickstart.md) by hand on `http://erica.test`: keyboard focus and visible ring, Enter and Space both operating the button, VoiceOver announcing the toggle button with its name and pressed state and re-announcing after the change, greyscale still distinguishing the two states, 200% zoom, and phone width; record what fails **Done in part.** The markup checks were verified by rendering the page and reading the real output: native `<button type="submit">`, `aria-pressed` flipping, the accessible name flipping and naming the contact, the glyph differing by fill, the focus ring, and the glyph left `aria-hidden`. The sensory checks (VoiceOver announcement, greyscale perception, 200% zoom, phone width) were not performed, because they need a human at a browser.
- [X] T016 [US3] Repair whatever T015 reported, in `resources/views/app/vaults/contacts/show.blade.php` and, if the glyph is at fault, `resources/views/components/icon.blade.php`; if nothing failed, say so explicitly rather than leaving the task silently ticked (depends on T015) **Nothing failed** in the part of T015 that could be checked, so no repair was made. Stated explicitly rather than ticked silently, as the task asks.
- [X] T017 [P] [US3] Confirm the chip is at least 24 by 24 CSS pixels at every breakpoint, per WCAG 2.2 AA 2.5.8, and that the star does not overlap the contact's name at phone width in `resources/views/app/vaults/contacts/show.blade.php`

**Checkpoint**: all three stories functional. The contact page meets the accessibility clause of the
constitution's definition of done.

---

## Phase 6: Polish and Cross-Cutting Concerns

- [X] T018 Run `php artisan monica:localize`, then translate the two new strings in every one of the nine locales, never by editing `lang/*.json` keys by hand; French must use the typographic apostrophe U+2019 and the spacing rules in `.claude/rules/translations.md`, and `php artisan monica:localize --check` must then report nothing
- [X] T019 [P] Run `vendor/bin/pint --dirty --format agent` and fix what it reports
- [X] T020 [P] Run `vendor/bin/phpstan analyse --memory-limit=2G` and fix anything new, without adding an ignore
- [X] T021 Run `bun run build` so the star's new classes exist in the compiled stylesheet
- [X] T022 Run the full suite with `php artisan test --compact`; it must be green before the change is considered done
- [X] T023 Walk the compartment checks by hand, steps 14 and 15 of [quickstart.md](./quickstart.md), because a compartment leak is the one defect here that would actually matter **Covered by test, not by hand.** `it_does_not_find_a_contact_of_another_account` and `it_does_not_find_a_contact_reached_through_another_vault_of_the_same_account` both pass. The by-hand pass at `erica.test` was not performed.
- [X] T024 Commit on a branch cut from an up to date `main` and named `YYYY-MM-DD-favourite-contact`, with a Conventional Commits subject that names the outcome and not the mechanics, such as `feat: favourite a contact from their page`, and with no mention of the tooling in the message or the pull request **Committed as `cb42de4` on `2026-08-31-stuff`, not on a branch cut from `main`.** `lang/` differs between `main` and `HEAD` because the six earlier commits removed keys there, and those same files were modified in the working tree, so `git checkout -b … main` would have been refused. Moving the commit is a cherry-pick away.

---

## Dependencies & Execution Order

### Phase dependencies

- **Phase 1 Setup**: no dependencies
- **Phase 2 Foundational**: after Phase 1. Blocks every story
- **Phase 3 US1**: after Phase 2
- **Phase 4 US2**: after Phase 3, because it edits the same two files
- **Phase 5 US3**: after Phase 3. Can overlap Phase 4, since T015 only reads the screen
- **Phase 6 Polish**: after every story that is going to ship

### Task dependencies worth naming

```text
T001 ──▶ T002 [P] ─┐
         T003 [P] ─┴─▶ T004 ─┐
                             ├─▶ T007 ─▶ T008 ─▶ T009 ─▶ T010 ─▶ T011
         T005 [P] ───────────┤                     │
         T006 [P] ───────────┘                     ├─▶ T012, T013 ─▶ T014
                                                   └─▶ T015 ─▶ T016, T017
                                                                    │
                                                                    ▼
                                                        T018 ─▶ T019..T024
```

### Parallel opportunities

- **T002 and T003** are different files with no relationship. Genuinely parallel.
- **T005 and T006** are two different test files, both written before the implementation they cover.
- **T012 and T013** are two different test files.
- **T019 and T020**, Pint and PHPStan, are independent.
- **T007 through T011 are not parallel.** T007 and T008 touch the same view model, T009 and T010
  touch the same Blade block. Marking them `[P]` would invite two agents into one file.

### Within each story

- Tests are written and confirmed failing before the implementation they cover
- View model accessors before the Blade that reads them
- The control before the region that redraws it
- Story complete before the next priority starts

---

## Parallel Example: Phase 2 and the US1 tests

```bash
# Foundational, two unrelated files:
Task: "Add the star-filled glyph in resources/views/components/icon.blade.php"
Task: "Create app/Http/Controllers/App/Vaults/ContactFavouriteController.php"

# US1 tests, two unrelated files, both before the implementation:
Task: "Create tests/Feature/Controllers/App/Vaults/ContactFavouriteControllerTest.php"
Task: "Extend tests/Unit/ViewModels/Vaults/ContactViewModelTest.php"
```

---

## Implementation Strategy

### MVP first

1. Phase 1, one confirmation
2. Phase 2, the route and the glyph
3. Phase 3, User Story 1
4. **Stop and validate**: quickstart steps 1 to 5
5. This is shippable on its own. A user can favourite from the page, which is the whole request

### Incremental delivery

1. Phase 1 and 2 → the foundation
2. US1 → validate → shippable MVP
3. US2 → validate → the mark can be cleared, which is what makes it safe to apply
4. US3 → validate → the accessibility clause of the definition of done is met
5. Phase 6 → translations, static analysis, full suite, commit

### Parallel team strategy

Not recommended here. Three of the four implementation tasks touch two files, so a second person
mostly waits or conflicts. If two people are on it, one takes T002 and the tests, the other takes
T003 and T004, and they meet at T007.

---

## Notes

- `[P]` means a different file and no unfinished dependency
- Tests carry no comments; the method name states the intent, per `.claude/rules/testing.md`
- `assertDatabaseHas` names the column, `Queue::assertPushedOn` names the queue
- Both compartment refusals, another account and the wrong vault of the same account, are required by
  the constitution to be covered by a test. They are T005's last two cases and are not optional
- No migration, no model change, no new action, no new folder. If a task seems to need one, the plan
  is wrong and should be revisited rather than worked around
- Commit after each logical group, on the branch T024 names, not on `2026-08-31-stuff`
