# Research: Last in touch

## R1. Where the date lives

- **Decision**: keep `contacts.last_interaction_at` and start writing it. Store the day at midnight,
  like `last_life_event_at`.
- **Rationale**: the header, `ContactListCells`, `ContactSortEnum` and `ContactResource` already read
  it, so fixing the writer fixes all four (FR-003).
- **Alternatives**: computing it on read (the sort needs a column); reusing `last_life_event_at`
  (counts every type, which story 2 refuses).

## R2. How it is recomputed

- **Decision**: extend `RecountsLifeEvents::recountLifeEventsOf()` to also set `last_interaction_at`:
  the latest `happened_on` that is not after today, among the contact's life events whose type has
  `updates_last_in_touch`. `CreateLifeEvent::countOn()` keeps its cheap increments: it moves the date
  forward only when the type qualifies and the day is not in the future.
- **Rationale**: the six actions that already recount (Update, Destroy, Restore, Merge, UndoMerge,
  RollBackAccountImport) get the date for free (FR-002). One query, one place.
- **Alternatives**: an observer on `LifeEvent` (hidden, against principle I); a second trait (two
  places to keep in step).

## R3. A future life event whose day arrives

- **Decision**: a daily job, `RecordLastInTouchOfTheDay`, at 02:15 on `low`, recounts the
  participants of the qualifying life events dated yesterday or today.
- **Rationale**: nothing else writes when a date passes. Two days of overlap covers a missed run and
  time zones ahead of UTC. A missed day only delays a date, it never makes one wrong.
- **Alternatives**: counting future events (spec says no); recounting every contact nightly (wasteful).

## R4. Approximate dates

- **Decision**: count from `happened_on`, which already holds the first day an approximate date
  stands for. The header keeps its relative wording ("3 months ago") whatever the precision.
- **Rationale**: `last_interaction_at` holds a date and no precision; carrying the precision of the
  winning life event onto the contact would be a second column for a rare case.

## R5. The setting on the type

- **Decision**: a boolean column `life_event_types.updates_last_in_touch`, default `true`. It is set on
  the type form, by `CreateLifeEventType`, `UpdateLifeEventType` and `ImportLifeEventType`. Changing
  it dispatches `RecountLastInTouchOfType` on `low`, which recounts every participant of that type.
- **Rationale**: a type is configuration of a fixed concept, which principle VII allows; a field on a
  template would be a second mechanism and could disagree between two contacts (FR-005).
- **Alternatives**: a flag on the category (too coarse: Social holds both Meal and Argument).

## R6. Supplied defaults

`false` for news about one person, `true` for moments shared (FR-007). Keys from `LifeEventTypeEnum`:

| Updates Last in touch | Types |
| --- | --- |
| Yes | call, message, meal, visit, time_together, argument, meeting, restaurant, concert, cinema, game, exercise, hobby, trip, holiday, hike, excursion, other_life_event |
| No | marriage, birth, separation, interview, new_job, class, training, graduation, medical_appointment, therapy, illness, hospital_stay, move, home_improvement, major_purchase, household_task, reading, reflection, decision, achievement, memory, spiritual_event |

The enum gains `updatesLastInTouch(): bool`, read by `CreateDefaultLifeEventTypes`. The migration
copies the "No" list as literal keys, since a migration must not read code that may change.

## R7. Wording (FR-006)

| Where | English |
| --- | --- |
| Switch label | Updates “Last in touch” |
| Line under it | When you record this life event with somebody, its date becomes the one their page shows under Last in touch. |

The label quotes the header's wording exactly. Translations are deliberately left out of this change
at the user's request, so `monica:localize --check` fails until they are run.

## R8. Shipping for existing accounts

- **Decision**: one migration adds the column, one backfill migration sets the "No" keys to `false`
  on system types with `DB::table()`, then puts `RecountLastInTouchOfType` on the `low` queue for
  every type that updates Last in touch and holds life events, delayed by five minutes (FR-004).
- **Rationale**: the job already works the date out again for everybody who took part in a life event
  of one type, and running it twice changes nothing. It is delayed because the migration runs while
  the queue workers still run the previous release, which does not have the job class yet (the rule
  of the migrations skill). The dates appear a few minutes after the deploy rather than at its end.
