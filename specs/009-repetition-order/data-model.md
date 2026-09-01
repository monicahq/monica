# Phase 1 Data Model: Which end a new repetition goes to

**Feature**: [spec.md](spec.md) | **Date**: 2026-09-01

One column added to a table that already exists. Nothing else about storage changes, and no data is
migrated.

## What a repeater gains

A field that is a repeater says which end a new repetition is added at. Every other kind of field
ignores it, the way they already ignore the floor and ceiling on how many repetitions are allowed.

| | |
| --- | --- |
| Where | the fields table, beside the two that say how many repetitions are allowed |
| Values | two, named rather than a boolean's true and false: newest last, and newest first |
| Default | newest last, which is what every repeater does today |
| Frozen | yes, with the version the field belongs to, like everything else on that table |
| Who may set it | the product, for the template an account starts with; and the builder, once User Story 3 lands |

**Why named values rather than a flag.** A boolean would have to be called something like "reversed",
which is a fact about an implementation rather than about what somebody sees, and it has no room for a
third answer if one is ever wanted. Two named cases read the same way in the code, in the reference and
on the builder control.

**Why not on the section or the template.** Two repeaters on one page can want different directions,
which is User Story 2, so it belongs to the repeater.

## What a repetition keeps

Unchanged. It already carries:

| | |
| --- | --- |
| Its identity | which is what the form sends back and what the save matches on |
| The place it holds | whose column says it is the order the repetition appears in, and that the user can reorder it |
| What it belongs to | the record and the repeater |

**The place a repetition holds keeps exactly one meaning: the order it appears in.** That is the whole
design. It was true before this feature for repeaters that collect at the end, and it stays true for
repeaters that collect at the front. Position 1 is the top, always, which is what lets the operation
that moves a repetition go on meaning what it says.

## What the save does with it

After a block is saved, the repetitions of each repeater in it hold the places 1 to n, in the order
they arrived in the request. The order they arrive in is the order they were drawn in, which is the
order somebody was looking at.

Written only where a number differs, so:

- a save that changes a value and moves nothing writes no place at all;
- a save that adds a repetition to a newest first repeater writes the new one and renumbers the rest;
- a save that removes one closes the gap.

## Ordering, when read

Both paths that read repetitions sort by the place, then by identity.

The tiebreak is new and is not decoration. Sorting by the place alone leaves two repetitions holding
the same number in whatever order the database chose, which can differ between two reads and between
two of the three databases the suite runs on. FR-011 asks for the same record to read the same way
twice, and the tiebreak is what makes that true rather than usually true.

## What is deliberately not stored

| Not stored | Why |
| --- | --- |
| When a repetition was added | The place it holds is the order, and a second source of order is a second answer to one question. FR-008. |
| A sort key derived from a value inside a repetition | Same reason, and a value can be empty. FR-008. |
| Anything on the record | The direction belongs to the structure, not to what somebody filled in. FR-012. |
