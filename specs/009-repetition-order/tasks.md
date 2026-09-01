---

description: "Task list for which end a new repetition goes to"
---

# Tasks: Which end a new repetition goes to

**Input**: Design documents from `specs/009-repetition-order/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[data-model.md](data-model.md), [contracts/repetition-order.md](contracts/repetition-order.md)

**Tests**: Included, and not optional here. Constitution Principle III makes a test a condition of
merge. One test in this list is written before the change it guards and has to be seen failing, for the
reason in the next section.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel with the other `[P]` tasks in the same phase (different files, no
  dependency between them)
- **[Story]**: Which user story the task belongs to (US1, US2, US3)
- Every task names the exact file it touches

## Read this before starting

**The risk in this feature is deletion, not disorder.** A save understands a repetition that does not
arrive in the request as one somebody took away. This feature draws the same repetitions in a different
order, so if the identity of each does not survive the round trip, a save does not produce a muddled
list. It deletes notes. T014 is that test, it comes before the change it guards, and it has to be seen
failing before it is made to pass.

**Nothing behaves differently until Phase 3.** Phases 1 and 2 add the vocabulary, fix a latent ordering
defect, and change how a save numbers repetitions, all while every repeater still collects at the end.
The suite is green at the end of Phase 2 and no screen has changed. That is deliberate: numbering from
what arrived is a change to how saving works, and drawing blanks elsewhere is a change to what arrives.
Landing them together would leave no state in which either could be blamed on its own.

**One task is blocked and it is not the interesting one.** T020 makes the notes repeater declare newest
first. Notes became a repeater in a separate change that is not yet on the default branch. Every other
task here, T014 included, is provable with a repeater declared newest first inside a test.

## What actually happened, against this list

Four things went differently. Two are findings, one is scope the list underestimated, and one is
outstanding.

**T014 passed instead of failing, and the risk it guards was overstated.** The list said a save could
delete notes if a block were drawn in one order and matched in another, and made that the one ordering
not to relax. Reading the form again shows why it cannot: every repetition the record already holds
carries its identity in a hidden field beside it, and the save matches on that identity rather than on
order. So drawing the blanks elsewhere cannot lose anything. The test is kept as a regression guard,
because it would catch somebody removing that hidden field, but it did not fail first and no claim is
made that it did.

**The test that could fail is at the screen rather than the action.** T017 was written as a component
test and abandoned: the component takes a row shape the view model builds, and faking it proved the fake
rather than the feature. It became `tests/Feature/Controllers/App/Vaults/RepetitionOrderTest.php`, which
renders the real block for a real contact and reads the indices out of it. That one failed for the right
reason before T015 and passed after, and the red was proved a second time by neutralising the direction
and watching it go red again.

**A fourth ordering site, and a fifth that was rightly left alone.** The plan named three places that
sort repetitions and there were three, but `SetFieldValues` sorts value list items by place as well.
That is the order of options in a multiple choice, a different concern, and it keeps its own ordering.

**T026 and T028 were finished later, after the author asked for the UI.** The first pass stopped at
parity in the actions, for the reason below, and the builder control was then added on top of it: a
choice on the repeater in the inspector, a badge on the row when it collects at the front, and the
controller carrying the value so a save that changes a label cannot take the direction off. The
reasoning for stopping first time still stands and is left here, because it is why the panel holds one
control rather than three.

**Why it stopped at parity first.** Nothing configures a
repeater in the builder today: `repeat_min` and `repeat_max` are accepted by `CreateField` and
`UpdateField` and set by no screen at all. Adding the direction to a screen would mean building the
first repeater configuration in the builder, which is a feature of its own. What was done instead is
parity: the direction is carried by both actions exactly as the two bounds are, with tests, so it is
settable wherever they are. T028 has nothing to test until that screen exists.

**T030 found something and is reporting it rather than fixing it, which is what it asked for.** With the
blanks drawn first, the markup order is right: a screen reader meets the new box before the notes, and
the tab order follows. But **Add another** stays below the repetitions, so somebody who activates it
finds the new box above where they are, reachable only by tabbing backwards. The fix is to draw that
control at the same end as the blanks. It is small, it is in the same file, and it is left out of this
branch because moving a control is a change to how somebody reaches it and belongs with a decision
rather than inside a numbering change.

**T035 was done through the suite, not a browser.** Steps 2, 3, 4, 6 and 7 of the quickstart each have a
test that asserts the same thing, and step 5 is
`it_writes_no_place_when_a_save_moves_nothing`. Nobody clicked through a running application, and that
is worth doing before this merges.

**T020 and T021 were finished once the author asked for the notes to read newest first.** The branch that
made notes a repeater was merged into this one to do it, so this branch now carries both changes. The
notes repeater declares the direction where a default field declares everything else about itself, and
the seeder writes it the way it writes the rest. Three tests take it from the template an account is
given to what somebody sees on a contact after writing a second note.

**T035 is the only thing left.** Nobody has clicked through a running application. Every step of the
quickstart has a test that asserts the same thing, but that is not the same as looking at it.

---

## Phase 1: Setup

- [X] T001 Run `php artisan test --compact` from the repository root and confirm the suite passes before any edit, so a later failure is known to belong to this branch

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: the vocabulary for a direction, a stable read, and a save that numbers what arrives. All of
it behaviour preserving, because every repeater still says newest last.

**⚠️ CRITICAL**: no user story work begins until this phase is complete and the suite is green.

- [X] T002 Ask the author whether to add the column by editing `database/migrations/..._create_fields_table.php` or by adding a new migration. `.claude/CLAUDE.md` says an existing migration is edited until it has run in production, and this is the one decision in this feature that cannot be undone by editing code afterwards. Do not guess
- [X] T003 Create `app/Enums/RepetitionOrderEnum.php`, a string backed enum with two cases, `NewestLast` and `NewestFirst`, implementing `Translatable` the way the other field enums do, with a `label()` whose words say what somebody will see rather than naming a direction of sort
- [X] T004 [P] Create `tests/Unit/Enums/RepetitionOrderEnumTest.php` asserting the two backing values and the labels, following the shape of the sibling files in `tests/Unit/Enums/`
- [X] T005 Add the column to the fields table per T002's answer, nullable or defaulted to newest last, with a comment saying it is which end a new repetition is added at and that it means nothing for a field that is not a repeater; then cast it on `app/Models/Field.php` and say the same in the property docblock beside `repeat_min` and `repeat_max`
- [X] T006 [P] Add cases to `tests/Unit/Models/FieldTest.php` asserting the cast returns the enum, that a field created without the column reads as newest last, and that the value survives a round trip through the database
- [X] T007 [P] Add a tiebreak to the read in `app/ViewModels/Vaults/ContactViewModel::repetitions()`: order by the place a repetition holds, then by its identity
- [X] T008 [P] Add the same tiebreak to `app/Actions/SetFieldValues::repetitionsOf()`
- [X] T009 [P] Add the same tiebreak to the sibling query in `app/Actions/UpdateFieldValueRowPosition::update()`, which renumbers from what it reads and is therefore unstable in exactly the same way
- [X] T010 Add a case to `tests/Unit/Actions/UpdateFieldValueRowPositionTest.php` (or `tests/Unit/Models/FieldValueRowTest.php` if one exists) asserting that two repetitions holding the same place read in the same order twice, which is FR-011 and is false today
- [X] T011 Change `app/Actions/SetFieldValues.php` so that after a repeater's repetitions have been matched, created and removed, each one holds the place matching the order it arrived in, counting from 1. Write only where the number differs. The order things arrive in is the order they were drawn in, which the form already carries as an ascending numeric index. Say so in the docblock, since it is now load bearing
- [X] T012 Add cases to `tests/Unit/Actions/SetFieldValuesTest.php`, with every repeater still saying newest last: the order of repetitions is unchanged by a save that changes only a value; no place is written when nothing moved; a removal closes the gap so the places run 1 to n with no hole; and a value never moves from one repetition to another
- [X] T013 Run `php artisan test --compact` and confirm the whole suite is green. **If any order changed anywhere, the numbering in T011 is wrong**, and nothing built on top of it can be trusted

**Checkpoint**: a direction can be declared, reading is stable, and saving numbers what arrives. Nothing
a person sees has changed.

---

## Phase 3: User Story 1 - A new note appears where somebody is already looking (Priority: P1) 🎯 MVP

**Goal**: a repeater that says newest first draws its blank at the top, and a new repetition ends up
first, losing nothing on the way.

**Independent Test**: add a second note to a contact that already has one, save, and confirm the new
note is above the old one when the section is read and when it is opened to be changed.

- [X] T014 [US1] Add the round trip test to `tests/Unit/Actions/SetFieldValuesTest.php`, on a repeater declared newest first: a save carrying one repetition the record already holds, one somebody added and one somebody removed, asserting all three outcomes and that the two survivors keep their order relative to each other. **Run it and watch it fail.** A test nobody has seen fail is a test nobody knows is wired up, and the failure this one guards is silent deletion rather than a wrong order
- [X] T015 [US1] Change `resources/views/components/field-repetitions.blade.php` to draw the blank repetitions before the repetitions the record already holds when the repeater says newest first, and after them otherwise, keeping the numeric index ascending down the form so that the order things arrive in stays the order they were drawn in
- [X] T016 [US1] Run T014 again and confirm it passes, and that it passes for a repeater saying newest last as well, so the change is a direction rather than a reversal
- [X] T017 [US1] Create `tests/Feature/Views/Components/FieldRepetitionsTest.php` (or add to it if it exists) asserting where the blanks are drawn in each direction, that each existing repetition still carries the hidden field holding its identity, and that the indices ascend down the form in both directions
- [X] T018 [US1] Add a case to `tests/Unit/Actions/SetFieldValuesTest.php` for two repetitions added in one save on a newest first repeater: both end up above everything older, and the one typed first stays above the one typed second. This is the case that rules out numbering per insertion
- [X] T019 [US1] Add a case to `tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php` asserting that reading a block and opening it as a form show the repetitions in the same order, which is FR-005 and the reason a reader edits what they were looking at
- [X] T020 [US1] Make the notes repeater say newest first in `app/Enums/DefaultFieldEnum.php`, and seed it in `app/Jobs/CreateDefaultTemplate.php` if the seeding does not already read the property off the enum. **Blocked**: notes is a repeater only on the branch that made it one, which is not yet on the default branch
- [X] T021 [US1] Add a case to `tests/Unit/Jobs/CreateDefaultTemplateTest.php` asserting the notes repeater is seeded newest first and the documents and photographs repeaters are not. Blocked with T020

**Checkpoint**: a note lands where somebody is looking, and the save that put it there lost nothing.

---

## Phase 4: User Story 2 - Each repeater keeps the order that suits what it holds (Priority: P2)

**Goal**: the direction belongs to one repeater, so nothing that did not ask for it changes.

**Independent Test**: on the template an account starts with, add a note and a document to a contact and
confirm the note goes to the top of the notes and the document goes to the bottom of the documents.

**Note**: the mechanism was built in Phases 2 and 3. This phase is what proves it is a property of one
repeater rather than a rule applied to every repeater at once, which is the difference between a feature
and a change nobody asked for.

- [X] T022 [P] [US2] Add a case to `tests/Unit/Actions/SetFieldValuesTest.php` asserting a repeater whose direction was never set collects at the end, so nothing already built changes behaviour by being left alone
- [X] T023 [P] [US2] Add a case to the same file with two repeaters in one section, one in each direction, saved in one request: each obeys its own direction and neither disturbs the other
- [X] T024 [P] [US2] Add a case to `tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php` asserting that adding a document puts it last, on the template an account starts with, which is the regression somebody would actually notice
- [X] T025 [US2] Run `php artisan test --compact tests/Unit/Actions/SetFieldValuesTest.php tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php` and confirm green

**Checkpoint**: notes read newest first and nothing else moved.

---

## Phase 5: User Story 3 - Somebody who builds a template chooses the direction (Priority: P3)

**Goal**: the direction is something somebody can set on a repeater they invented.

**Independent Test**: create a repeater in the builder, choose newest first, put a record on the
published version, and confirm a new repetition goes to the top.

**Note**: this phase may be dropped without touching anything above it. The product still declares the
direction for its own template, and a repeater somebody invents collects at the end, which is what they
get today.

- [X] T026 [US3] Offer the direction where a repeater is configured in the template builder, worded as what somebody will see rather than as a number or a flag, defaulting to newest last, and carried through whatever writes a field so that publishing makes a new version like every other change to a structure
- [X] T027 [US3] Run `php artisan monica:localize` and translate the words of the control in every configured locale, per the checklist in `.claude/CLAUDE.md`
- [X] T028 [US3] Add a case to the builder's own test asserting the direction is offered, that leaving it alone gives newest last, and that changing it on a published version makes a new version rather than altering the one records are on
- [X] T029 [US3] Run the builder tests and confirm green

---

## Phase 6: Polish and Cross-Cutting Concerns

- [X] T030 Check the keyboard order on a block whose blanks are drawn first, per Principle IX: somebody adding a note should reach the new box by tabbing forward from the top of the block rather than past every note they already wrote. If the markup order and the sensible reading order disagree, say so rather than papering over it
- [X] T031 [P] Run `vendor/bin/pint --dirty --format agent` from the repository root and commit whatever it reformats
- [X] T032 [P] Run `vendor/bin/phpstan analyse` and confirm it reports nothing new over `app/`, `routes/` and `database/`
- [X] T033 Run `php artisan monica:localize --check` and confirm nothing is missing across the nine locales
- [X] T034 Run the whole suite with `php artisan test --compact`, and remember that this is one database of the three CI runs. The tiebreak from Phase 2 is the thing most likely to pass here and fail elsewhere
- [ ] T035 Walk steps 2 through 7 of [quickstart.md](quickstart.md) against a running application, in particular step 4, where a save carrying an addition and a removal at once must lose nothing, and step 7, where an account seeded before this change must behave exactly as it did
- [X] T036 Confirm nothing in this branch touched a value a person wrote. The feature writes an order and nothing else, and a test showing a value in the wrong repetition means it is wrong rather than untidy

---

## Dependencies

```text
T001 (baseline)
  └─> Phase 2 Foundational
        T002 (ask about the migration)   [answer needed before T005]
        T003 (the enum) ──> T004 (its test)
        T002, T003 ──> T005 (the column and the cast) ──> T006 (its test)
        T007, T008, T009 (three tiebreaks, parallel) ──> T010 (stability test)
        T005 ──> T011 (numbering) ──> T012 (numbering tests) ──> T013 (whole suite green)
              └─> Phase 3 US1
                    T014 (the round trip test, watched failing)
                          └─> T015 (blanks at the declared end) ──> T016 (watched passing)
                                └─> T017, T018, T019
                                      └─> T020, T021 (notes, BLOCKED on the other branch)
                          └─> Phase 4 US2
                                T022, T023, T024 (parallel) ──> T025
                                      └─> Phase 5 US3
                                            T026 ──> T027 ──> T028 ──> T029
                                                  └─> Phase 6 Polish
                                                        T031, T032 parallel; T030, T033 to T036 in order
```

**The one ordering that must not be relaxed**: T014 before T015, and T014 seen failing. It is the only
step that distinguishes a feature that reorders notes from one that deletes them.

**The second**: T013 before any of Phase 3. If numbering alone moved anything, the numbering is wrong,
and every later test would be measuring a broken foundation.

**Story order**: US1 before US2 before US3. US2 has nothing to prove until a direction can be declared
and honoured, and US3 puts a control on something the first two stories made work.

## Parallel execution examples

**Phase 2**: T007, T008 and T009 are the same one line change in three different files and can be done
together. T004 and T006 are tests of two different things and are independent of each other.

**Phase 4**: T022, T023 and T024 are three independent cases, though the first two edit one file, so they
parallelise as thinking rather than as writing.

**Phase 3 holds no parallel work.** It is a chain by construction: the test, then the change, then the
test again.

## Implementation strategy

**MVP**: Phase 1 plus Phase 2 plus Phase 3, T001 to T019. That is the complaint answered: a repeater can
say it collects at the front, a note lands where somebody is looking, and the save that put it there
loses nothing. T020 and T021 finish it for the product's own template and wait on another branch.

**Increment two**: Phase 4, four tasks, all of them tests. It proves the change is a property of one
repeater. Cheap, and it is what somebody would notice if it were missing, because their documents would
have been shuffled.

**Increment three**: Phase 5, which can be dropped entirely without any of the above being wrong.

**Then**: Phase 6. T033 and T034 are checklist items in `.claude/CLAUDE.md` and gates in CI. T030 is not
cosmetic: drawing blanks first changes the order somebody reaches controls in, and that is a Principle IX
question rather than a preference.

**Total**: 36 tasks. 1 setup, 12 foundational, 8 for US1, 4 for US2, 4 for US3, 7 polish.
