---
description: "Task list for 014-favourite-toast"
---

# Tasks: Confirmation when a contact is marked a favourite

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: Included. Constitution Principle III is non-negotiable, so test tasks are not optional here.

**Format**: `[ID] [P?] [Story?] Description with file path`. `[P]` means it can run beside its
neighbours, which here means a different file.

## Phase 1: Setup

- [X] T001 Cut branch `2026-09-02-favourite-toast` from an up-to-date `main`

---

## Phase 2: Foundational

None. The notification area, the route, the two actions and the `is_favourite` column all exist, so
nothing blocks the stories. See [research.md](research.md) Decision 3.

**Checkpoint**: user stories can start immediately.

---

## Phase 3: User Story 1 - Marking is confirmed (Priority: P1) 🎯 MVP

**Goal**: Pressing the star on a contact who is not a favourite says so in the notification area.

**Independent Test**: Press the star on a non-favourite; a confirmation appears while the star shows the new state.

- [X] T002 [US1] Rewrite `it_says_nothing_the_star_has_not_already_said` as `it_confirms_the_marking`, asserting `assertSessionHas('status')` and `assertSessionHas('status_description')`, in tests/Feature/Controllers/App/Vaults/ContactFavouriteControllerTest.php
- [X] T003 [US1] Flash `__('Marked as a favourite.')` and `__('They sit at the top of the list.')` on the favouriting branch, and rewrite the class docblock that says nothing is flashed, in app/Http/Controllers/App/Vaults/ContactFavouriteController.php

**Checkpoint**: marking is confirmed and shippable on its own.

---

## Phase 4: User Story 2 - Removal is confirmed (Priority: P2)

**Goal**: Pressing the star again says the contact is no longer a favourite.

**Independent Test**: Press the star on a favourite; a confirmation naming the removal appears.

- [X] T004 [US2] Replace the `assertSessionMissing('status')` in `it_destroys_nothing_when_it_clears_the_mark` and add `it_confirms_the_removal` asserting both flashed keys, in tests/Feature/Controllers/App/Vaults/ContactFavouriteControllerTest.php
- [X] T005 [US2] Flash `__('No longer a favourite.')` and `__('They go back in with everybody else.')` on the unfavouriting branch in app/Http/Controllers/App/Vaults/ContactFavouriteController.php

**Checkpoint**: both directions are confirmed, and neither wording can be read as the other.

---

## Phase 5: User Story 3 - It behaves like every other confirmation (Priority: P3)

**Goal**: Same place, lifetime, dismissal and announcement as every other confirmation, with and without scripting.

**Independent Test**: Compare it against any other confirmation in the product.

- [X] T006 [US3] Add `it_shows_the_confirmation_on_the_page_the_redirect_lands_on`, using `followingRedirects()` and `assertSee()`, in tests/Feature/Controllers/App/Vaults/ContactFavouriteControllerTest.php
- [X] T007 [P] [US3] Assert a failed request still flashes nothing, in the not-found cases of tests/Feature/Controllers/App/Vaults/ContactFavouriteControllerTest.php
- [ ] T008 [US3] Walk the keyboard, screen reader, reduced motion, no-scripting and long-name checks in [quickstart.md](quickstart.md)

**Checkpoint**: every functional requirement in the spec has something proving it.

---

## Phase 6: Polish

- [X] T009 [P] Correct the "Two signals, one state" comment, now three, in resources/views/app/vaults/contacts/show.blade.php
- [X] T010 Run `php artisan monica:localize` and translate the four reported strings in all nine locales, in lang/*.json
- [X] T011 Run `php artisan monica:localize --check`, `vendor/bin/pint --format agent`, `vendor/bin/phpstan analyse` and `php artisan test --compact`

---

## Dependencies & Execution Order

- T001 precedes everything.
- Phase 2 is empty, so US1, US2 and US3 are each unblocked from the start.
- Within a story the test comes before the change it pins: T002 → T003, T004 → T005.
- T003 and T005 edit the same controller, and T002, T004, T006 and T007 edit the same test file, so
  the only genuinely parallel pairs are T007 with T006, and T009 with anything.
- T010 needs every string to exist, so it follows T005. T011 is last.

**MVP**: T001 through T003. That is the request as it was worded, and it stands alone.
