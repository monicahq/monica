# Research: Full text search

## R1. A Scout engine on every instance

- **Decision** (user, 2026-09-29, revised 2026-10-02): Laravel Scout, with whichever engine the
  operator runs: Algolia, Meilisearch or Typesense, which tolerate typing mistakes, or the database.
  The constitution asks for tolerant search on every deployment, which the database engine does not
  meet: it finds every word or the start of one, and no typing mistake.
- **The database engine**: a full text index needs real columns, so `MaterializedDatabaseEngine`
  writes the document to four `search_*` columns on every sync, which `SearchUsingFullText` names.
  MySQL and MariaDB match `+word*`, PostgreSQL `word:*` (`simple`), SQLite every word with `LIKE`.
- **What makes an engine supported**: it meets the requirements table of data-model.md; only
  `config/scout.php` and `SearchRecords::engineOptions()` name an engine.
- **Alternatives**: trigram `LIKE`s (slow), phonetic keys (English only), a join over the values at
  query time (a full text index cannot cover a computed column).

## R2. One document per record, built when it is synced

- **Decision**: Contact, Entity and LifeEvent `use Searchable`, one index each. No column, no
  table: `toSearchableArray()` builds the document as Scout sends it, from the values as
  `FieldValueFormatter` writes them for the account's user, in the account's locale. The document
  and what each attribute holds are in data-model.md.
- Accents, case and tolerance are the engine's, on its defaults.

## R3. Every word, ranking, sensitive values

- **Ranking**: each engine searches `name`, `body`, `sensitive_body`, `linked_names` in that order
  and ranks the matched attribute before the typo count. That is FR-008: a tolerant name match ranks
  above an exact value match, and within one attribute an exact match comes first.
- **Every word**: each engine is told not to drop words when too few records hold them all (FR-003).
- **Sensitive**: `sensitive_body` is left out of the searched attributes unless the caller may read
  it: the screen may; an API key without `ApiKeyScopeEnum::SensitiveFields` may not, so search cannot
  be used to probe a value the key cannot read.
- The setting or option each engine takes for each of these is in data-model.md.

## R4. Keeping documents current

- **Saving**: every value write `$touches` its record, so the record's `saved` event reaches Scout,
  which queues the sync (`queue` true on `high` for SC-002, `after_commit` true,
  `MakeSearchableUniquely`). Saving adds one queued job and nothing else (SC-002).
- **Deleting and restoring**: Scout removes a soft deleted record and adds it back on restore.
- **Other records whose document changes**, each through `searchable()` on a query, queued by Scout:
  | Change | Records resent |
  | --- | --- |
  | a contact or entity renamed (`search_key` changed) | life events it takes part in, related contacts, records whose relation value points at it |
  | `UpdateValueListItem` | records holding a value of that item |
  | `UpdateLifeEventType` | the type's untitled life events |
  | `UpdatePreferences`, locale or date format | every record of the account |
  | `MoveRecordToStructureVersion`, `DestroyFieldValueRow`, `UndoContactMerge` | the record, since they write in bulk |
  | `DestroyVault` | the vault's records, `unsearchable()` |

## R5. When the engine is unreachable, or none is configured

- Scout's queued jobs retry, so a save never waits and nothing is lost. `SearchRecords` catches the
  failure to reach the engine: the screen shows "Search is unavailable right now", the API answers
  503, the MCP tool reports the same. Documents sent late catch up when the engine returns.
- `SCOUT_DRIVER` defaults to `database`, so search works with nothing else to run; `null` stores
  nothing and makes search say it is unavailable.

## R6. Groups, excerpt, surfaces

- **Groups**: People, one per entity type (filter `entity_type_id`), Life events; 20 per page.
- **Excerpt**: engines report matches differently, so the excerpt is found in PHP. For each hit on
  the page, the document is rebuilt from the hydrated record and each word is compared, folded with
  `LocaleCollation::foldForSearch()`, to the words of `name`, then each value, then each linked name,
  within one edit from 5 letters and two from 9. The first attribute, in that order, holding a word gives the
  label and excerpt, the label alone for a sensitive value, nothing for the name.
- **Search box**: `x-shell` resolves the route's `Vault` and draws the form in the top bar.

## R7. Tests and delivery

- **Suite**: documents and excerpts are unit tested without an engine; what is resent, with a
  recording engine. Matching, tolerance, ranking and scoping are what the engine decides, so they
  are tested against one real engine, Meilisearch, chosen because it is free and runs as one binary:
  a service in `.github/workflows/tests.yml`, a binary in the devcontainer. Algolia and Typesense are
  checked by hand (quickstart.md section 4).
- **Dependencies**: `laravel/scout` required. The Algolia, Meilisearch and Typesense clients are
  suggested, never required, as the constitution asks of a package reaching an outside service, and
  the Meilisearch client is in `require-dev` so the suite covers the working path.
