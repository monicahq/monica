# Research: Default Possession Types

## R1. Where the catalogue lives in code

- Decision: one enum, `PossessionTypeEnum`, one case per type of the spec's catalogue, implementing
  `Translatable`. Each case answers `label()`, `colour()` and `blocks()`: the standalone fields, then
  the groups, each field a name, a `PossessionFieldKindEnum` and, for a select, its options.
- Rationale: `monica:localize` discovers the strings of a `Translatable` enum, so every name reaches
  every locale with no list kept by hand. `RelationshipTypeEnum` and `LifeEventTypeEnum` hold their
  catalogues the same way.
- Alternatives: a configuration file (localize would not see it); the 088 export format replayed
  through `ImportPossessionType` (one journal entry per type, strings invisible to localize).

## R2. Option codes stay untranslated

- Decision: the enum names the codes once (`PSA 10`, `MS-70`, `NTSC`, `33`, `BMX`, the region digits,
  the platforms and the comic publishers). `translationKeys()` returns every name except those, and
  the action writes a code as it is, every other option through `__()`.
- Rationale: translators never see "33". Passing a code through `__()` is not enough, because a code
  can be a word the product translates elsewhere: German turns the publisher "Image" into "Bild".
- Alternatives: a per option flag in `blocks()` (noisier for 18 coin grades); translating codes
  (translators would copy them 30 times).

## R3. Which language the names are written in

- Decision: `__($name, [], $user->locale ?? config('app.locale'))`, at the moment the action runs.
- Rationale: the job runs on the queue, where the application locale is the default, not the user's.
  `CreateTemplate` resolves the locale with the same fallback. A possession type holds a plain name
  (088), so the name is written once in the user's language and is theirs afterwards.
- Alternatives: a translation key column like relationship types (a schema change for seeded rows
  only, and a seeded name would then change under the user when they switch language).

## R4. Action and job

- Decision: the action `CreateDefaultPossessionTypes(user)` writes the rows; the job
  `SeedPossessionTypes(user)` calls it, last in the `CreateAccount` chain on the `high` queue.
- Rationale: principle I; `SeedRelationshipTypes` with `CreateDefaultRelationshipTypes` is the same
  pair. The action returns early when the account holds any possession type, which also makes a
  retried job harmless, and writes everything in one transaction.
- Alternatives: calling `CreatePossessionType`, `CreatePossessionFieldGroup` and
  `CreatePossessionField` 25 times (about 300 journal entries, against FR-008).

## R5. The journal

- Decision: one new case, `UserActionEnum::PossessionTypesSeeded` (`possession_types_seeded`), worded
  "Set up the possession types the account starts with", dispatched on `low` like its siblings.

## R6. Colours

- Decision: by family, from `VaultColourEnum`; the table is in [data-model.md](data-model.md).
- Rationale: the list is sorted by name, so a colour per family is what makes it scannable.

## R7. Existing accounts

- Decision: `2026_10_26_000001_backfill_possession_types`, after the migration creating the tables.
  It walks the users whose account holds no type, by id in chunks of 200, and runs
  `CreateDefaultPossessionTypes` for each, synchronously.
- Rationale: the person deciding chose a migration over a command, so deploying is enough and nobody
  has to remember a step. Running the action rather than queueing the job means the migration ends
  with the rows written, and its guard makes a second run harmless. `backfill_notification_preferences`
  is the precedent.
- Alternatives: a command (rejected by the person deciding); dispatching the job (the migration would
  finish before the work).

## R8. No API or MCP change

- Decision: none. Seeded types are ordinary rows, reached by the 088 endpoints and tools. Seeding is
  part of account creation, which the API does not expose.

## R9. The spec exceeds its cap

- Decision: the catalogue stays in `spec.md` (255 lines against 120). It is the one statement of what
  ships; the enum mirrors it and a test compares them (quickstart). The pull request says so.
- Alternatives: moving it to `data-model.md` (cap 60, and it is a requirement, not a model).
