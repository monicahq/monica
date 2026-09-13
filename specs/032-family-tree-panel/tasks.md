---
description: "Task list for the Family tree panel"
---

# Tasks: Family tree panel

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: required. Constitution III is non-negotiable and `.claude/rules/testing.md` governs how they are written. No test asserts a class string, a border or any other property of the drawing.

**Format**: `[ID] [P?] [Story?] Description with file path`. `[P]` means a different file with nothing incomplete in front of it.

## Phase 1: Setup

- [X] T001 Confirm nothing new is needed: the `family` and `profile` glyphs in `resources/views/components/icon.blade.php`, and `x-panel` with its `subtitle` and `footer` slots, `x-contact-avatar` and `x-button.secondary` in `resources/views/components/`

## Phase 2: Foundational (blocks every story)

- [X] T002 Add `scopeParentsOf()` beside `scopeChildrenOf()`, selecting `contact_a_id` where the contact is on side B, reading membership through `typesBehavingAs(GenealogyParentage)` and consulting no status, in `app/Models/Contact.php`
- [X] T003 [P] Test the scope: parents found, children not returned in their place, status ignored, a withdrawn type still reaching nobody by name, another vault and another account excluded, in `tests/Unit/Models/ContactTest.php`
- [X] T004 Create the block with its constructor, the three tier queries each eager-loading `avatar` and ordered by `sort_key`, reading no age, since the drawing names people rather than dating them, in `app/ViewModels/Vaults/FamilyTreeBlock.php`
- [X] T005 Add `familyTree()` returning the block, beside `immediateFamily()` and `relationships()`, in `app/ViewModels/Vaults/ContactViewModel.php`
- [X] T006 Turn the page into a grid of the groups and an aside, stacking below `xl`, and include the new partial in the aside, in `resources/views/app/vaults/contacts/_relationships.blade.php`
- [X] T007 Create the partial with its `x-panel`, titled after the contact, in `resources/views/app/vaults/contacts/_family-tree.blade.php`
- [X] T008 [P] Test that `familyTree()` answers a block for the contact of the page, in `tests/Unit/ViewModels/Vaults/ContactViewModelTest.php`

**Checkpoint**: the panel stands beside the groups and draws nobody yet.

## Phase 3: User Story 1 - See a contact's line at a glance (P1) 🎯 MVP

**Goal**: parents above, the contact and current partners in the middle, children below, joined and named.

**Independent Test**: record a parent, a partner and two children, open the Relationships tab, see all five in three tiers with the contact marked out and the name-only node leading nowhere.

- [X] T009 [US1] Add `parents()`, `currentPartners()` and `children()` returning rows of name, URL, avatar, age and whether the person is a name only, dropping a tier that holds nobody, in `app/ViewModels/Vaults/FamilyTreeBlock.php`
- [X] T010 [US1] Add the line saying friends and colleagues stay in the list, in `app/ViewModels/Vaults/FamilyTreeBlock.php`
- [X] T011 [US1] Draw the three tiers as flex rows inside a grid centred on one line, the middle tier a `1fr auto 1fr` grid holding the contact in the `auto` column and the partners in the right one, in `resources/views/app/vaults/contacts/_family-tree.blade.php`
- [X] T012 [US1] Draw the joins as `before:`/`after:` borders on each cell, using `first:`, `last:` and `only:` so a tier of any size needs no arithmetic, per [research.md](research.md) D1 and D3, in `resources/views/app/vaults/contacts/_family-tree.blade.php`
- [X] T013 [US1] Draw a person: avatar and a link to their page, or a dashed mark leading nowhere when they are a name only, with the name under each node, in `resources/views/app/vaults/contacts/_family-tree.blade.php`
- [X] T014 [US1] Mark the contact out from the rest and say so in words as well as in the drawing, so nothing is carried by colour alone, in `resources/views/app/vaults/contacts/_family-tree.blade.php`
- [X] T015 [US1] Name each tier for a screen reader, and give the name-only node its wording rather than only its dashed edge, in `resources/views/app/vaults/contacts/_family-tree.blade.php`
- [X] T016 [US1] Draw the line about friends and colleagues under the panel, in `resources/views/app/vaults/contacts/_family-tree.blade.php`
- [X] T017 [P] [US1] Test the block: the three tiers and who lands in each, an empty tier dropped, a partnership counted only while current, a parentage counted whatever its status, membership following the behaviour after a type is renamed and after it is withdrawn, an age present and absent, a name-only person having no URL, and a person who is both parent and partner drawn in both, in `tests/Unit/ViewModels/Vaults/FamilyTreeBlockTest.php`
- [X] T018 [P] [US1] Test the panel: its title, each person named, the link to a person with a profile, the absent link on a name-only node, the tier names, and the line about friends and colleagues, in `tests/Feature/Views/Vaults/FamilyTreeTest.php`
- [X] T019 [P] [US1] Test that a friend appears in the groups and nowhere in the panel, in `tests/Feature/Views/Vaults/FamilyTreeTest.php`
- [X] T020 [P] [US1] Test the compartments: the page is not found for a contact in another account, and for a contact reached through the wrong vault, in `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php`

**Checkpoint**: US1 works on its own and is the MVP.

## Phase 4: User Story 2 - Read a line with nothing in it, or too much (P2)

**Goal**: a contact with no family is told so, and a contact with a large one loses nobody.

**Independent Test**: open the panel on a contact with nothing recorded, see the empty line and the way in. Record seven children on another and reach every one of them.

- [X] T021 [US2] Add `holdsNothing()`, the empty line and the URL offering the first relationship, in `app/ViewModels/Vaults/FamilyTreeBlock.php`
- [X] T022 [US2] Draw the empty state and its way to add a relationship, in the shape `_immediate-family.blade.php` already uses, in `resources/views/app/vaults/contacts/_family-tree.blade.php`
- [X] T023 [US2] Give the panel body a fixed width and height with the drawing sized to its content inside, scrolling both ways, in `resources/views/app/vaults/contacts/_family-tree.blade.php`
- [X] T024 [US2] Make the scroll box a focusable named region, so a tier holding only name-only nodes is still reachable without a pointer, per [research.md](research.md) D4, in `resources/views/app/vaults/contacts/_family-tree.blade.php`
- [X] T025 [P] [US2] Test the empty line and that a contact with children but no parent and no partner draws two tiers rather than three, in `tests/Unit/ViewModels/Vaults/FamilyTreeBlockTest.php`
- [X] T026 [P] [US2] Test that seven children are all named and all reachable, and that the page asks the records the same number of times as it does for one child, in `tests/Feature/Views/Vaults/FamilyTreeTest.php`
- [X] T027 [P] [US2] Test the empty state and its way in, in `tests/Feature/Views/Vaults/FamilyTreeTest.php`

**Checkpoint**: both ends of the range read well.

## Phase 5: Polish

- [X] T028 Run `php artisan monica:localize` and translate everything it reports, in every configured locale, in `lang/*.json`
- [X] T029 Run `php artisan monica:localize --check` and correct whatever it refuses, notably any counted phrase and the French typography rules
- [X] T030 Walk the panel with the keyboard alone and with a screen reader, at 200% zoom, on desktop, tablet and phone widths, per [quickstart.md](quickstart.md). The markup contract is checked against the rendered page: focusable named scroll region, a name for each tier, the subject and the name-only state said in words, connectors hidden from assistive technology. A human still owes the screen-reader and zoom pass.
- [X] T031 Run `vendor/bin/pint --dirty --format agent`, then `vendor/bin/phpstan analyse --memory-limit=2G`, and fix what they report
- [X] T032 Run the full suite, since `_relationships.blade.php` changed shape and other tests read that page

## Dependencies

- T001 blocks nothing; it only confirms.
- T002 blocks T003, T004, T009.
- T004 blocks T005, T009, T010, T021.
- T005 blocks T006, T008.
- T006 blocks T007.
- T007 blocks T011 to T016 and T022 to T024.
- T009 blocks T011, T013, T017.
- T011 blocks T012, T014, T023.
- T021 blocks T022, T025.
- T023 blocks T024, T026.
- Phase 2 blocks Phase 3 and Phase 4. Phase 3 does not block Phase 4: the empty state and the scroll box stand whether or not the drawing is finished, though testing them is easier once it is.
- Phase 5 waits for everything.

## Parallel work

- T003 and T008 run beside their siblings; both touch a test file nothing else in the phase touches.
- T017, T018, T019 and T020 run together once T009 to T016 are done; four different files.
- T025, T026 and T027 run together once T021 to T024 are done, but T025 shares `FamilyTreeBlockTest.php` with T017 and T026 shares `FamilyTreeTest.php` with T018, so those pairs are sequential between phases.
- Everything else in Phases 2 to 4 touches `FamilyTreeBlock.php` or `_family-tree.blade.php` and runs in order.

## Strategy

Phase 2 then Phase 3 is the MVP: the panel drawing a line, which is the whole point of the feature and is deliverable alone. Phase 4 is what lets it go on every contact rather than on the tidy ones. Ship Phase 3 first if the two are split.
