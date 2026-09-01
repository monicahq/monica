# Quickstart: Validating which end a new repetition goes to

**Feature**: `specs/009-repetition-order` | **Date**: 2026-09-01

How to prove this works once it is built. Shapes and rules are in
[contracts/repetition-order.md](contracts/repetition-order.md) and [data-model.md](data-model.md).

## Prerequisites

- The application running locally (`composer run dev`).
- A migrated database. This feature adds a column, so migrate before anything else.
- An account whose template was seeded after the change, since a published structure is frozen and an
  older account keeps collecting the way it did.
- **The notes repeater has to exist.** It arrives in a separate change. Until that lands, everything
  below can be done with the documents repeater declared newest first in a test, and only the notes
  half of it has to wait.

## 1. Run the tests

The ones that carry the feature:

```bash
php artisan test --compact \
  tests/Unit/Actions/SetFieldValuesTest.php \
  tests/Unit/Actions/CreateFieldValueRowTest.php \
  tests/Unit/Actions/UpdateFieldValueRowPositionTest.php
```

The ones that prove nothing a person sees moved:

```bash
php artisan test --compact \
  tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php \
  tests/Feature/Views/Components/ \
  tests/Unit/Jobs/CreateDefaultTemplateTest.php
```

Then the gates:

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
php artisan monica:localize --check
```

## 2. Watch a note land at the top

Open a contact on the General page, add a note, and save.

**Expect** the note you just wrote above the ones already there, without scrolling, and the older notes
in the same order among themselves as before.

Add a second note. **Expect** it above the first, and the first still above everything older.

## 3. Watch a document land at the bottom

Same contact, the Information page, add a document.

**Expect** it last. This is the step that proves the change is a property of one repeater rather than a
rule applied to every repeater at once. If documents reversed too, User Story 2 has failed and somebody
who never asked for it has had their collection shuffled.

## 4. The one that matters: nothing is lost

With three notes on a contact, open the section, add a fourth, remove the second, and save.

**Expect** four became four: the new one first, the second gone, the other two in their old order
relative to each other. Then reload.

**Expect** the same thing after a reload as before it, because the order is stored rather than an
accident of the response.

Now do it the other way round in the same save: remove the newest and add one. **Expect** the same
coherence.

**A save that returns a muddled list is a bug. A save that returns fewer notes than you accounted for
is the failure this whole step exists to catch**, because a repetition the save cannot account for is
one it understands as taken away.

## 5. Save without touching the order

Open a note, change its words, save.

**Expect** the order unchanged, and no place rewritten. This is worth checking in the journal or by
watching the queries: a save that renumbers on every edit is writing to repetitions nobody touched.

## 6. Two notes typed in one save keep their order

Open the section, add two blanks, type "first" in the upper and "second" in the lower, save.

**Expect** the order you typed, both above everything older. If they come back swapped, the numbering
is being done per insertion rather than once from what arrived.

## 7. An account from before the change

Take an account whose template was seeded before this feature, add a note.

**Expect** it last, exactly as it was. A published structure is frozen, so nobody's list reorders under
them. If it moved, FR-010 has failed and the change is reaching records it should never have touched.

## 8. The builder, if User Story 3 was built

Create a repeater, look at what it asks.

**Expect** the direction offered in the words of what somebody will see, not as a number and not as a
flag, and newest last when they leave it alone.

## What proves the feature is done

- A note lands at the top, a document lands at the bottom, in the same account on the same day.
- Step 4 has been done in both orders and nothing was lost, duplicated or shuffled.
- Two notes typed in one save come back in the order they were typed.
- An account seeded before the change behaves exactly as it did.
- A save that changes only a value writes no order at all.
- The suite passes on all three databases, which is where an unstable tiebreak shows up.
