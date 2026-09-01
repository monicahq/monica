# Phase 0 Research: Which end a new repetition goes to

**Feature**: [spec.md](spec.md) | **Date**: 2026-09-01

Nothing was left as NEEDS CLARIFICATION. What follows is the design this feature has to settle, plus
three things reading the code changed about the spec's own reasoning.

## 1. What is already built, which is more than the spec assumed

| Already there | Where |
| --- | --- |
| A repetition carries the place it holds, and the column says it is the order it appears in and that the user can reorder it | the repetitions table |
| An index covering record, field and place | the same table |
| Both paths that read repetitions sort by that place | the display path and the saving path |
| **An operation that moves one repetition and renumbers its siblings from one**, with a test | `app/Actions/UpdateFieldValueRowPosition.php` |
| An operation that adds a repetition at the end, whose docblock says where it sits afterwards is the moving operation's business | `app/Actions/CreateFieldValueRow.php` |
| A form that names each repetition by a numeric index and carries the identity of an existing one in a hidden field | `resources/views/components/field-repetitions.blade.php` |

The fourth row is the one that matters, and the spec was written without it. Reordering by hand is not
a future idea to leave room for: the operation exists, it is tested, and it renumbers siblings as a
matter of course. It has no screen yet, and no route calls it.

**Consequence**: renumbering siblings is not a new kind of write this feature would introduce. It is
what the product already does when a repetition moves.

## 2. The direction decides where the blanks are drawn, and the save numbers what arrives

**Decision**: two changes, and no third.

- **The form draws its blank repetitions at the top** for a repeater that says newest first, and at the
  bottom for one that says nothing. The index each repetition is named by follows the order it is
  drawn in.
- **The save gives each repetition the place matching the order it arrived in**, writing only where
  that differs from the place it held. The order things arrive in is the order they were shown, which
  is the order somebody was looking at.

Nothing else changes. Reading already sorts by the place a repetition holds, so reading follows for
free, and that is FR-005 satisfied by construction rather than by a second code path that could
disagree with the first.

**Rationale**: the place a repetition holds keeps one meaning, the one its column already claims: the
order it appears in. Position 1 is the top, for every repeater, whichever direction it collects in.
That is what lets the moving operation go on meaning what it says, and it is what a screen for
reordering will need when it arrives.

**Alternative rejected: reverse the reading.** Leave the place alone, add nothing to the form, and sort
one repeater's repetitions the other way when they are read. It is the smallest diff and it was the
spec's own assumption. It was rejected because it gives the place two meanings, order added for some
repeaters and order shown for others, and because it silently breaks the operation from section 1:
moving a repetition to position 1 would draw it last. A future screen with a "move to top" that moves
things to the bottom is a trap laid deliberately.

**Alternative rejected: insert at the front, one repetition at a time.** Have the adding operation put
a new repetition at 1 and push the rest down. It keeps the single meaning, and it breaks on two notes
added in one save: each insert goes in front of the last, so the second note somebody typed ends up
above the first, reversing the order they wrote them in. Numbering once, from what arrived, has no such
case.

## 3. Where the direction is declared

**Decision**: a column on the fields table, defaulting to the behaviour every repeater has today, read
through an enum with two cases so the two directions have names rather than a boolean's true and false.

**Rationale**: it is a property of one repeater, it has to be frozen with the version it belongs to,
and both are true of everything else on that table. A published structure is frozen, so a repeater
seeded before this change keeps collecting the way it did, which is FR-010 without any work.

**On editing the migration rather than adding one**: this project edits an existing migration until it
has run in production, and says so in `.claude/CLAUDE.md`. The fields table migration is the one to
change. The plan should confirm that is still the standing instruction before doing it, because it is
the one decision here that cannot be undone by editing code.

**Alternative rejected: derive it from the field's kind.** Notes newest first because it is notes. It
needs no column and it is wrong: the kind of a field says what it holds, not how a collection of them
reads, and somebody inventing a meeting log would have no way to say what they want.

## 4. Two repetitions holding the same place

**Finding**: reading sorts by the place alone. Two repetitions that hold the same number come back in
whatever order the database chose, which can differ between two reads and between two drivers.

**Decision**: sort by the place, then by identity, on both read paths. It costs nothing, it makes FR-011
true rather than usually true, and the suite has to pass on three databases where "usually" is how a
test fails on one of them and not the others.

This is a latent defect rather than something this feature introduces. It becomes reachable here
because numbering is written more often, so it is fixed here.

## 5. What protects the repetitions nobody touched

**Finding**: the save understands a repetition that does not arrive as one somebody took away. That is
deliberate and documented where the saving happens: adding and removing are part of saving the block,
which is what lets the block promise nothing is written until it is saved.

**Decision**: nothing about the identity of a repetition changes. The form already carries it in a
hidden field beside each existing repetition, and the save already matches on it rather than on order.
Drawing the blanks somewhere else moves nothing that identifies anything.

**Why it is still a requirement with a test on it**: because the failure is silent and total. A change
that renders in one order and matches in another does not produce a muddled list. It produces a save
that deletes the notes it could not account for. FR-009 is the reason the round trip is proven for a
repeater in each direction, with an existing repetition, a new one and a removed one in the same save.

## 6. What the spec got wrong, and what has been corrected

- **"Adding a repetition does not renumber the ones already there."** The chosen design does renumber,
  where a number changes. The assumption was written to avoid writing to rows somebody did not touch,
  and the reasoning behind it is answered instead: the write happens inside a save somebody asked for
  on that block, it writes only what differs, and it is the same renumbering the moving operation has
  always done. The spec's Assumptions have been amended to say so, and FR-006 needed no change: it asks
  that relative order be kept, which numbering from what arrived does exactly.
- **"Reordering by hand is out of scope, and the door is left open."** Still true as scope, but the door
  is not a door. The operation exists and is tested. The plan should say plainly that this feature makes
  that operation reachable and coherent rather than merely unblocked.

## 7. Localization

One new user-facing string at most, and only if User Story 3 is built: the words on the builder control.
The two directions are named in an enum, which this project translates through the same mechanism as
every other enum. Nothing else here is copy. `php artisan monica:localize --check` runs regardless.

## 8. Risks

- **The order things arrive in has to be the order they were drawn in.** It is today, because each
  repetition is named by a numeric index and the indices ascend down the form. Anything that renames
  those inputs breaks the numbering silently, so a test asserts the order and not only the contents.
- **A repeater in a section with other fields.** Numbering touches only repetitions, and only those of
  the repeater being saved. A test covers a section holding a repeater beside ordinary fields.
- **Three databases.** Numbering inside a save is several small writes; they belong in the transaction
  the save already opens, and the tiebreak from section 4 is what keeps two reads agreeing on any of
  the three.
- **Nothing about a record's meaning changes.** No value moves between repetitions, no repetition
  changes what it holds. If a test ever shows a value in the wrong repetition, the feature is wrong and
  not merely untidy.
