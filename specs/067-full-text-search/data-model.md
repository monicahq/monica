# Data model: Full text search

No table, no column, no migration. Model 054, `SearchIndexEntry`, is not built. What is stored is
three indexes in the Scout engine the operator runs, `contacts`, `entities` and `life_events`
(prefixed by `scout.prefix`), one document per record, built by `toSearchableArray()` when Scout
syncs it (research R2, R4).

## Document

| Attribute | Content |
| --- | --- |
| `id` | the record's key |
| `account_id`, `vault_id` | scope, filterable |
| `entity_type_id` | the group of an entity, null otherwise, filterable |
| `name` | the record's name |
| `body` | every attached, non sensitive value as `FieldValueFormatter::format()` writes it for the account's user in the account's locale; a relation value gives its target's name; a life event adds narrative and location |
| `sensitive_body` | the values of fields marked sensitive, written the same way |
| `linked_names` | participants' names (life event), related people's names (contact), empty for an entity |

- Contributes nothing: Boolean, Avatar, the layout types, detached values. File and Image give the
  file name, never the contents. A Repeater's children are values like the rest.
- Example: Dwight, French account, `body` reads `Schrute Farms 14 mars 1966 Végétarien`.

## What every engine must be configured to do

Each supported engine gets its settings in `config/scout.php`, synced by `scout:sync-index-settings`
where the engine has one, and its per search options from `SearchRecords` (research R3).

| Requirement | Algolia | Meilisearch | Typesense | Database |
| --- | --- | --- | --- | --- |
| Filter on scope and group | `attributesForFaceting` `filterOnly(...)` | `filterableAttributes` | the three fields in the collection schema, `facet` | the three columns |
| Search the four attributes in FR-008's order | `searchableAttributes` ordered | `searchableAttributes` ordered | `query_by` ordered, `query_by_weights` 4,3,2,1, `text_match_type` `max_weight` | `SearchUsingFullText` on the `search_*` columns, a name holding every word first |
| Level before tolerance (FR-008) | `ranking` with `attribute` before `typo` | `rankingRules` with `attribute` before `typo` | field weight first, `prioritize_exact_match` true | no tolerance |
| Every word (FR-003) | `removeWordsIfNoResults` `none` | `matchingStrategy` `all` | `drop_tokens_threshold` 0 | `+word*` boolean mode, `word:* &`, one `LIKE` per word |
| Typing mistakes (FR-003) | defaults | defaults | `num_typos` 2 | none |
| Leave out sensitive values (R3) | `restrictSearchableAttributes` | `attributesToSearchOn` | `query_by` without `sensitive_body` | `columns` option without `search_sensitive_body` |

The database engine reads the four attributes from `search_name`, `search_body`, `search_sensitive_body`
and `search_linked_names`, written on every sync and hidden from serialisation. `collection` is not supported.

## Models

- Contact, Entity, LifeEvent: `use Searchable`; `toSearchableArray()`; `makeSearchableUsing()` eager
  loading field values with their field and item, participants, relationships.
- `App\Models\Concerns\SearchesRecords`: `matchedField()`, which finds, in PHP and whatever the
  engine, the attribute and value closest to the words searched (research R6).
