---
description: "Task list for the Immediate family block"
---

# Tasks: Immediate family block on a contact record

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required. Constitution III is non-negotiable and `.claude/rules/testing.md` governs how they are written.

**Format**: `[ID] [P?] [Story?] Description with file path`. `[P]` means a different file with nothing incomplete in front of it.

## Phase 1: Setup

- [X] T001 Confirm nothing new is needed: the `family` and `gift` glyphs in `resources/views/components/icon.blade.php`, and `x-panel`, `x-caption`, `x-contact-avatar` and `x-button.secondary` in `resources/views/components/`

## Phase 2: Foundational (blocks every story)

- [X] T002 Create the block object with its constructor, the shared age lookup and the relationship form URL, in `app/ViewModels/Vaults/ImmediateFamilyBlock.php`
- [X] T003 Add `immediateFamily()` returning that object, in `app/ViewModels/Vaults/ContactViewModel.php`
- [X] T004 Create the panel shell, its title, its `family` mark and its way to the relationship form, in `resources/views/app/vaults/contacts/_immediate-family.blade.php`
- [X] T005 Include the partial in the right-hand column of the overview, between Facts and the placeholder Relationships panel, in `resources/views/app/vaults/contacts/show.blade.php`
- [X] T006 [P] Test the age lookup: present, absent, and suppressed for an unknown year, in `tests/Unit/ViewModels/Vaults/ImmediateFamilyBlockTest.php`

**Checkpoint**: the block is on the screen and holds nothing yet.

## Phase 3: User Story 1 - See a person's children (P1) 🎯 MVP

**Goal**: children named under Children, each linking to their record.

**Independent Test**: record parentage to two people, open the record, see both under Children with a count of two.

- [X] T007 [P] [US1] Test `scopeChildrenOf`: direction, ended rows kept, distinctness, partial and archived kept, deleted excluded, in `tests/Unit/Models/ContactTest.php`
- [X] T008 [US1] Add `scopeChildrenOf()`, ordered by `sort_key`, in `app/Models/Contact.php`
- [X] T009 [US1] Add `children()` and its distinct count, in `app/ViewModels/Vaults/ImmediateFamilyBlock.php`
- [X] T010 [US1] Draw the Children list, its caption, its count and its rows, in `resources/views/app/vaults/contacts/_immediate-family.blade.php`
- [X] T011 [P] [US1] Test the children rows, the count and the nameless fallback, in `tests/Unit/ViewModels/Vaults/ImmediateFamilyBlockTest.php`
- [X] T012 [P] [US1] Test the Children list on the screen: the copy, the links and the age, in `tests/Feature/Views/Vaults/ImmediateFamilyTest.php`

**Checkpoint**: US1 works on its own.

## Phase 4: User Story 2 - See who the person is with now (P1)

**Goal**: current partners named under Current partner, ended ones absent.

**Independent Test**: record one current and one ended romantic link, open the record, see only the current one.

- [X] T013 [P] [US2] Test `scopeCurrentPartnersOf`: either side, ended excluded, ended and current to one person counted once, two distinct partners both shown, in `tests/Unit/Models/ContactTest.php`
- [X] T014 [US2] Add `scopeCurrentPartnersOf()`, ordered by `sort_key`, in `app/Models/Contact.php`
- [X] T015 [US2] Add `currentPartners()` and its distinct count, in `app/ViewModels/Vaults/ImmediateFamilyBlock.php`
- [X] T016 [US2] Draw the Current partner list under Children, in `resources/views/app/vaults/contacts/_immediate-family.blade.php`
- [X] T017 [P] [US2] Test the partner rows and the count, in `tests/Unit/ViewModels/Vaults/ImmediateFamilyBlockTest.php`
- [X] T018 [P] [US2] Test the Current partner list on the screen, in `tests/Feature/Views/Vaults/ImmediateFamilyTest.php`

**Checkpoint**: both lists work, independently of each other.

## Phase 5: User Story 3 - Understand an empty block (P2)

**Goal**: an empty block explains what would land there and never claims the person has no family.

**Independent Test**: open a record with no qualifying relationships and read what the block says.

- [X] T019 [US3] Add the both-empty sentence and the two *No … recorded* lines, in `app/ViewModels/Vaults/ImmediateFamilyBlock.php`
- [X] T020 [US3] Draw the both-empty body with its way to record a relationship, and each list's own empty line, in `resources/views/app/vaults/contacts/_immediate-family.blade.php`
- [X] T021 [P] [US3] Test all three empty shapes: both empty, children only, partner only, in `tests/Feature/Views/Vaults/ImmediateFamilyTest.php`

**Checkpoint**: every state of the block is drawn.

## Phase 6: User Story 4 - Trust the block on a crowded record (P3)

**Goal**: nothing enters the block that should not.

**Independent Test**: record one relationship of every supplied type plus an invented type named Spouse, and confirm only the qualifying people appear.

- [X] T022 [P] [US4] Test the exclusions: parent, sibling, cousin, colleague, a partner's child, an invented type named Spouse, and a renamed or withdrawn supplied type still counted, in `tests/Unit/Models/ContactTest.php`
- [X] T023 [P] [US4] Test that the query count does not grow with the number of people listed, in `tests/Unit/ViewModels/Vaults/ImmediateFamilyBlockTest.php`
- [X] T024 [US4] Correct whatever T022 and T023 catch, in `app/Models/Contact.php` (the scopes needed no correction; T023 caught a one-off account lookup skewing the measurement, corrected in the test)

**Checkpoint**: the thirteen scenarios in `spec/models/026` all hold.

## Phase 7: Polish

- [X] T025 [P] Test that the overview still answers and another account's contact is still not found, in `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php` (already covered there; no new test, which the testing rules ask for)
- [X] T026 Run `php artisan monica:localize` and translate everything it reports, in every configured locale under `lang/`
- [X] T027 Run `php artisan monica:localize --check` and correct the counted phrases and the per-locale typography it refuses
- [X] T028 Run `vendor/bin/pint --dirty --format agent`
- [X] T029 Run `vendor/bin/phpstan analyse` and clear anything new
- [X] T030 Run `php artisan test --compact`, the whole suite, because the overview is a shared screen
- [X] T031 Check the block by keyboard and by screen reader, at 200% zoom and at phone width, as rendered by `resources/views/app/vaults/contacts/_immediate-family.blade.php`

## Dependencies

- T001 → T002 → T003, T004 → T005. T006 after T002.
- US1: T007 → T008 → T009 → T010; T011 and T012 after T010.
- US2: T013 → T014 → T015 → T016; T017 and T018 after T016.
- US3 after T010 and T016. US4 after T008 and T014.
- Phase 7 after every story it is meant to cover; T030 last but for T031.

## Parallel opportunities

- T011 with T012, T017 with T018, T022 with T023, and T006 alongside nothing else in its phase.
- With two people: US1 and US2 after the checkpoint at T006, one story each. They share `Contact.php` and the partial, so their file edits are sequenced, not their work.

## Implementation strategy

MVP is Phase 1, Phase 2 and US1: the block on the screen listing children. Stop there and it is worth shipping. US2 doubles its value, US3 makes the common empty case honest, and US4 is the proof rather than the feature.
