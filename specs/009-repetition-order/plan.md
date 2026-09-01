# Implementation Plan: Which end a new repetition goes to

**Branch**: `2026-09-01-repetition-order` | **Date**: 2026-09-01 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/009-repetition-order/spec.md`

## Summary

A repeater says which end a new repetition is added at. Notes say newest first, so a note somebody
writes appears where they were already looking. Documents and photographs say nothing and go on
collecting at the end, which is what every repeater does today.

Two changes carry it. **The form draws its blank repetitions at the top** for a repeater that collects
at the front. **The save gives each repetition the place matching the order it arrived in**, writing
only where that number changes. Reading already sorts by that place, so reading follows without a
second code path that could disagree with the first.

The design turns on keeping one meaning for the place a repetition holds: the order it appears in, which
is what its column already claims. Position 1 is the top for every repeater, in either direction. That
is what lets the operation that moves a repetition, which already exists and is already tested, go on
meaning what it says.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12, Alpine.js for the blanks the form reveals. No new dependency.

**Storage**: one column on the fields table. No data migrated, no table added.

**Testing**: PHPUnit, `php artisan test --compact`

**Target Platform**: Linux server, self hosted and hosted alike

**Project Type**: Server rendered Laravel web application

**Performance Goals**: numbering a repeater's repetitions is at most one small write per repetition, in
the transaction the save already opens, and only where a number changes. Reading gains a second sort
column and no query.

**Constraints**: the suite passes on SQLite, MySQL and PostgreSQL, which is why the read order needs a
tiebreak rather than relying on what a driver happens to return. The block keeps its promise that
nothing is written until it is saved, so nothing here writes on adding or removing a blank.

**Scale/Scope**: one column, one enum, one form partial, one save path, two read paths, and the tests.
Roughly 10 files.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Verdict | How this feature satisfies it |
| --- | --- | --- |
| I. Actions hold the business logic | **Pass** | The numbering belongs to the operation that saves a block, which is where adding and removing repetitions already live. No controller learns anything, and the two existing operations on a repetition keep their jobs. |
| II. Simplicity over abstraction | **Pass** | Two changes and no third. The alternative that looked smaller, reversing the reading, needed a second meaning for the place a repetition holds and would have broken an operation that already ships. Recorded in [research.md](research.md) section 2. |
| III. Tested before merged | **Pass** | The save path, the creating operation and the moving operation all have tests already and gain cases. One test exists for the failure that matters: a save with an existing repetition, a new one and a removed one, on a repeater in each direction, losing nothing. |
| IV. Laravel conventions are the default | **Pass** | A column with a default, cast through an enum, read in the model. Migration written with `up()` only and no `constrained()`. Enum named with the `Enum` suffix and PascalCase cases. |
| V. Localized and safe user experience | **Pass** | No new string unless User Story 3 is built, and then it is the builder control's words, translated through the mechanism every other enum uses. Nothing destructive: this writes an order, never a value. |
| VI. Nothing changes under anybody's feet (NON-NEGOTIABLE) | **Pass, and it constrained the design** | A published structure is frozen, so a repeater seeded before this keeps collecting the way it did and no existing list reorders under anybody. This is why the direction is a property of a field on a version rather than a setting anywhere else. Step 7 of [quickstart.md](quickstart.md) is the check. |
| VII. A stable core, unlimited personalisation | **Pass** | It extends the one personalisation mechanism rather than adding a second: a repeater gains a property beside the two it already has for how many repetitions are allowed. No schema change is needed for anything a user invents. |
| VIII. Nothing disappears silently | **Pass, and it is the whole risk** | The save understands a repetition that does not arrive as one taken away. This feature draws the same repetitions in a different order, so the identity of each has to survive the round trip or notes are deleted rather than reordered. FR-009 carries a test for exactly that, and it is the one thing in the task list that may not be relaxed. |
| IX. Accessible to everybody (NON-NEGOTIABLE) | **Pass, with one thing to get right** | The order is the reading order in the markup, not a visual reversal, so a screen reader and a person reading the page get the same sequence. Nothing is conveyed by position alone that is not also conveyed by the order the fields are read in. Drawing the blanks first must keep the tab order sensible: somebody adding a note should reach the new box by tabbing forward from the top of the block, not by tabbing past every note they already wrote. |
| X. Everything goes through the API | **Partial, and stated** | There is no API for building a template yet, so there is nothing to extend and nothing to keep in step. When one arrives it has to carry this property like every other property of a field. Recorded under Complexity Tracking rather than waved past. |

**Gate result: passes**, with the Principle X note recorded rather than dismissed.

## Project Structure

### Documentation (this feature)

```text
specs/009-repetition-order/
├── plan.md              # This file
├── spec.md              # Feature specification
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── repetition-order.md
├── checklists/
│   └── requirements.md
└── tasks.md             # Phase 2 output (/speckit-tasks, NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
app/
├── Enums/
│   ├── RepetitionOrderEnum.php               # new: newest last, newest first
│   └── DefaultFieldEnum.php                  # edit: the notes repeater says newest first
├── Models/
│   └── Field.php                             # edit: the column, its cast, its docblock
├── Actions/
│   └── SetFieldValues.php                    # edit: number the repetitions from what arrived
├── Jobs/
│   └── CreateDefaultTemplate.php             # edit: seed the direction
└── ViewModels/Vaults/
    └── ContactViewModel.php                  # edit: the tiebreak on reading

database/migrations/
└── ..._create_fields_table.php               # edit: the column, per the standing instruction

resources/views/components/
└── field-repetitions.blade.php               # edit: draw the blanks at the declared end

tests/
├── Unit/Actions/
│   ├── SetFieldValuesTest.php                # edit: numbering, and the round trip
│   └── CreateFieldValueRowTest.php           # edit: it still adds at the end on its own
├── Unit/Enums/
│   └── RepetitionOrderEnumTest.php           # new
├── Unit/Jobs/
│   └── CreateDefaultTemplateTest.php         # edit: the notes repeater's direction
├── Unit/Models/
│   └── FieldTest.php                         # edit: the cast and the default
└── Feature/Views/Components/
    └── FieldRepetitionsTest.php              # new or edit: where the blanks are drawn
```

No new top-level folder, so no amendment is needed.

**Structure Decision**: everything lands where its kind already lives. The direction is an enum beside
the other field enums, the numbering is in the operation that already adds and removes repetitions, and
the rendering is in the partial that already draws them.

## Implementation Notes

### Order of work

1. The enum, the column, the cast, and their tests. Nothing behaves differently yet: every repeater
   reads as newest last, which is what it already does.
2. The read tiebreak, on both paths. Also behaviour preserving, and it is what makes every later test
   stable on three databases.
3. The numbering in the save, with every repeater still saying newest last. **The suite must be green
   here.** If numbering alone changes any order, the numbering is wrong and nothing built on top of it
   can be trusted.
4. The form drawing blanks at the declared end, plus the round trip test in both directions.
5. The notes repeater says newest first, and the seeder test says so.
6. User Story 3, the builder control, which can be dropped without touching any of the above.

Step 3 before step 4 is the point of this ordering. Numbering from what arrived is a change to how
saving works; drawing the blanks elsewhere is a change to what arrives. Landing them together would
leave no state in which either could be blamed on its own.

### The one thing that may not be relaxed

A save carries an existing repetition, a new one and a removed one at the same time, on a repeater in
each direction, and loses none of them. That test exists before the form changes, and it fails before
it passes. Everything else here is order; this is the difference between reordering notes and deleting
them.

### The dependency

FR-003 names the notes repeater, which arrives in a separate change that is not yet on the default
branch. Steps 1 to 4 and 6 need nothing from it. Step 5 is the only one that waits, and until it lands
the same behaviour is provable with the documents repeater declared newest first inside a test.

### Two things the plan should confirm rather than assume

- **Editing the fields table migration** rather than adding one is what `.claude/CLAUDE.md` says to do
  until a migration has run in production. It is the one step here that cannot be undone by editing
  code, so it is worth confirming the instruction still holds before doing it.
- **The tab order** once blanks are drawn first. Adding a note should not mean tabbing past every note
  already written. If it does, the markup order is right and the reading order needs thought, which is
  a question for Principle IX rather than for this feature's convenience.

## Complexity Tracking

| Departure | Why | What was rejected |
| --- | --- | --- |
| Principle X is only partly satisfied | There is no API for building a template, so there is nothing this feature could extend. The property has to be carried when that API is built. | Building the template API here, which is a far larger feature than this one and unrelated to what somebody asked for. |

Nothing else departs from the constitution.

## Constitution Re-check (post Phase 1 design)

Re-run after [research.md](research.md), [data-model.md](data-model.md),
[contracts/repetition-order.md](contracts/repetition-order.md) and [quickstart.md](quickstart.md).
**Still passes**, with four things the design settled that the first pass could only assume:

- **The spec was wrong twice, and both are corrected.** It assumed adding a repetition must never
  renumber the others, and it assumed reordering by hand was a distant idea. The product already
  renumbers every sibling when a repetition moves, and the operation that does it exists and is tested.
  The Assumptions now say so. Had the first assumption stood, the design would have been the one that
  reverses reading, which breaks that operation.
- **Principle VIII is the whole risk, and it is not about ordering.** Reading the save path showed the
  failure mode is deletion, not disorder. That moved a test from the polish phase to before the change
  it guards.
- **FR-011 turned out to be false today.** Reading sorts by the place a repetition holds and by nothing
  else, so two repetitions holding the same number come back in whatever order a driver chose, and that
  can differ between two reads. It is a latent defect rather than one this feature introduces, and this
  feature makes it reachable by writing numbers more often, so the tiebreak is part of the work rather
  than a note for later.
- **Principle IX gained a question the first pass did not see.** Drawing blanks before existing
  repetitions is correct for reading order, and it puts the new box first in the tab order too. That is
  probably right and it is deliberately called out, because a change to markup order is a change to how
  somebody reaches a control.

No new dependency, no table added, no folder added, and no value a person wrote is touched.
