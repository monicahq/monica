# Research: Knowledge example content

## R1. Where the content lives

- **Decision**: a data class `ExampleKnowledge` next to `ExampleGroups`, read by the seeder.
- **Rationale**: every other example section does this; the seeder stays a sequence of steps.
- **Alternatives**: inline in `ExampleAccountSeeder` (already 1,056 lines); a JSON fixture (no type checking).

## R2. Writing through actions

- **Decision**: call the Knowledge actions, never `Model::create()`.
- **Rationale**: FR-001 wants the journal and search entries a person's content produces; the seeder
  already sets the queue to `sync`, so they are written during setup.
- **Alternatives**: factories (skip the journal and the sanitising).

## R3. Language of the content

- **Decision**: English, not passed through `__()`.
- **Rationale**: it is content the account holds, like the example possessions; wrapping it in `__()`
  would add 30 interface strings to translate into 32 locales for data nobody reads as copy.
- **Alternatives**: translated content (cost above, no reader).

## R4. Columns of a list

- **Decision**: rename the two starting columns, then add the others.
- **Rationale**: `CreateKnowledgeList` always creates "Name" and "Note"; renaming keeps their uuids and
  needs no deletion.
- **Alternatives**: delete and recreate (two extra journal entries per list).

## R5. Folder before content

- **Decision**: create each folder first and pass it to every note and list.
- **Rationale**: without a folder, `CreateKnowledgeNote` and `CreateKnowledgeList` create "General".
