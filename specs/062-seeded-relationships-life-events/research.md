# Research: Relationships and life events in the example account

## R1. Where the data lives

- **Decision**: `ExampleAccountSeeder` keeps the writing. The two long lists move into two plain classes
  beside it, `database/seeders/ExampleRelationships.php` and `database/seeders/ExampleLifeEvents.php`,
  each with one public method returning an array. The seven new people join the existing `cast()`.
- **Rationale**: about 200 events at five lines each would push the seeder past 1,500 lines, which
  would bury its writing logic. Keeping the lists as data, with no logic in them, is the smallest
  split that keeps each file readable.
- **Rejected**: everything in one file (too long); two `Seeder` subclasses (they need the user and the
  vault, which `$this->call()` cannot pass).

## R2. The twins take part in events after the finale

- **Decision**: Jack and Erica Bing are born on 2004-05-06, the day of the finale. Their twenty events
  run from 2004 to 2010: the move to Westchester, birthdays, first steps, the first day of school,
  holidays with the Gellers. These are invented, and they stay consistent with where the finale leaves
  the family. Every event after 2004-05-06 includes the twins.
- **Rejected**: exempting the twins from the twenty (breaks FR-007); dating their events before their
  birth (breaks FR-011).

## R3. Chandler's parents

- **Decision**: Nora Bing (woman, the novelist) and Helena Bing (woman). Chandler knew his parent as
  Charles, and Helena Handbasket was the name on the Las Vegas stage. The show's writers and the
  actress have since said the character is a trans woman, so the record uses the name she chose. The
  note carries the stage name. Nora and Helena were spouses, and the marriage ended when they divorced
  at Thanksgiving 1978.
- **Rejected**: "Charles Bing", man (misgenders the character in a product that records gender).

## R4. Type specific fields stay empty

- **Decision**: events pass no `values`. Every shipped field is optional, and a tester can fill them
  in from the app.
- **Rejected**: filling them. That means resolving field ids for about 25 types, which is a lot of
  lookup code for data nobody asked for.

## R5. Ended relationships and dates

- **Decision**: `CreateRelationship` always writes a relationship as current. For each link that
  ended, or that has dates in the show, the seeder follows it with `UpdateRelationship`, passing the
  status, `startedOn` and `endedOn`. The same rules apply as for a person using the app (FR-013).
- **Rejected**: writing the status straight onto the row (skips the rules and the journal).

## R6. Looking a person up

- **Decision**: the seeder keys contacts by full name, since there are two Jacks (Geller and Bing).
  Relationship types are looked up by `RelationshipTypeEnum` key, life event types by
  `LifeEventTypeEnum` key, and the Neighbour type is kept once it is created.

## R7. Role direction

- **Decision**: for a type that isn't symmetric, the first person listed holds role A (parent,
  grandparent, aunt or uncle), which is what `CreateRelationship` does without `swapped`. Symmetric
  types order the sides by id on their own.

## R8. Archived Mr. Heckles

- **Decision**: Mr. Heckles is archived inside the contact loop, before any relationship or event is
  written. Neither `CreateRelationship` nor `CreateLifeEvent` checks whether a contact is archived
  (I searched both for any archive check), so the order stays as it is. If implementation proves me
  wrong, archiving moves to the end of `run()`.

## R9. Runtime

- **Measured**: the existing seeder test file runs 11 seeds in 11 s, so about 1 s per seed. About 200
  events and 60 relationships, each logging synchronously, should add a few seconds. SC-004 caps a
  seed at 10 s. Every test in the seeder file pays this cost, so the new assertions go into few tests.

## R10. What the existing tests constrain

- `it_sets_a_different_reminder_on_every_birthday_but_monicas_own` requires that every birthday other
  than Monica's has a reminder with its own name, and that the reminder moments vary. The twins and Ursula get
  birthdays and reminders. The other four new adults get none.
- The favourites assertion expects exactly three favourites, so no new person is a favourite.
