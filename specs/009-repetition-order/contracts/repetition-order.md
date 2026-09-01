# Contract: what somebody sees, and what a save may do

**Feature**: [../spec.md](../spec.md) | **Date**: 2026-09-01

This feature adds no endpoint and no address. What it has is a behaviour a person can rely on, and a
guarantee about what a save is allowed to touch. That is the contract.

## Reading a repeater

```
NEWEST LAST (every repeater today)      NEWEST FIRST (the notes repeater)

Documents                               Notes
  1  passport, scanned in March           1  what they said on Tuesday   <- newest
  2  lease, scanned in June                2  the thing about the move
  3  visa, scanned last week   <- newest   3  where we met
     [ Add another ]                          [ Add another ]
```

- The place a repetition holds is the order it appears in, in both directions. First is top.
- Reading a block and opening it as a form show the same order. What somebody edits is what they were
  looking at.
- The same record read twice reads the same way, whichever database is underneath.

## Adding one

| The repeater says | Where the blank is drawn | Where the new one ends up |
| --- | --- | --- |
| nothing | after what is already there | last |
| newest last | after what is already there | last |
| newest first | before what is already there | first |

Adding never disturbs the relative order of what was already there. On a newest first repeater the
numbers shift down by one; the notes stay in the same order among themselves.

Adding two in one save keeps them in the order they were typed. On a newest first repeater both go
above everything older, and the one typed first stays above the one typed second.

## What a save may and may not do

| | |
| --- | --- |
| MAY | change the place a repetition holds, so that the places run 1 to n in the order they arrived |
| MAY | add a repetition somebody filled in, and remove one somebody took away |
| MUST NOT | lose a repetition somebody was shown and did not remove |
| MUST NOT | duplicate one |
| MUST NOT | move a value from one repetition to another |
| MUST NOT | change the relative order of repetitions somebody did not move |
| MUST NOT | write a place that has not changed |

The third row is the one worth stating as a prohibition rather than a hope. A save understands a
repetition that does not arrive as one somebody took away, so a rendering that loses track of which
repetition is which does not produce a muddled list. It deletes.

## What a structure may say

- A repeater may say which end it collects at. Every other kind of field may not, and saying nothing
  means the end, which is what every repeater does today.
- The direction is frozen with the published version it belongs to. A record already on a version reads
  as it did before this feature existed, and changing the direction makes a new version like every other
  change to a structure.
- Once User Story 3 lands, somebody building a repeater chooses the direction in the words of what they
  will see, not as a number and not as a flag.

## Not in this contract

- A screen for moving a repetition by hand. The operation for it exists and is tested; what it lacks is
  a way to reach it. This feature makes it coherent by keeping first meaning top, and builds nothing.
- Sorting a repeater by a value inside its repetitions.
- Anything about which fields a repeater asks on each pass, the date on a note included.
