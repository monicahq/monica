# Research: Multilingual contact names

## R1. The name columns on `contacts` become a projection

**Decision**: keep `prefix`, `first_name`, `middle_name`, `last_name`, `suffix` and `name_order`, and add `formatted_name`. All seven are copies of the display representation, written only by the contact's `saving` hook and removed from `#[Fillable]`, so no action can write them.
**Rationale**: 7 actions, 6 controllers, 14 view models, the resource, two MCP tools and around 320 test lines read these columns. A projection keeps every list free of a join or an eager load (SC-005), and it is derived the way `sort_name` already is (FR-016).
**Rejected**: dropping the columns and eager loading the display representation everywhere a name is shown.

## R2. One action writes the whole set

**Decision**: `UpdateContactNameRepresentations` takes the full list and the display choice, then creates, updates and deletes rows in one transaction. A row missing from the list is deleted. The web form, `PUT api/v1/.../name-representations`, the MCP tool and the CardDAV full reconciliation (R6) all call it. `UpdateContactName` keeps its parameters and rewrites the display representation's parts, creating one when there is none. `CreateContact` builds the first representation from its parts, or from a list when one is given.
**Rejected**: separate create, update and delete endpoints. That means three actions repeating the display invariant, while the screen already saves the set at once.

## R3. Search

- `scopeMatchingSearch` adds `orWhereExists` on `contact_name_representations.search_key`, folded with `LocaleCollation::foldForSearch` as today.
- `searchableName()` returns every representation, display first, one per line (`search_name`). `searchableLinkedNames()` does the same for linked contacts.
- A representation's `saved` and `deleted` hooks send the contact to search and call `resendTheRecordsNamingIt()`. A secondary change leaves `search_key`, so it needs this trigger.

## R4. Duplicates

- `DetectDuplicateContacts` blocks on each representation's `match_key` (indexed) as well as on `contacts.match_key`, which still covers nickname and maiden name.
- Given names are compared pair by pair between representations that share a key.
- Every kind takes part. The product never merges by itself, so a phonetic match can only ever be a suggestion (FR-010).

## R5. Export (`VCardMerger::writeName`)

- One `N` per representation, and one `FN` per non phonetic one. All carry `ALTID=1` and `PROP-ID` set to the representation's uuid. RFC 9554 makes `PROP-ID` unique per property name, so an `N` and its `FN` share it.
- `PREF=1` on the displayed `FN`. `LANGUAGE` and `SCRIPT` (RFC 9554) when known. `PHONETIC` only on a phonetic `N`, since RFC 9554 allows it on `N` and `ADR` only.
- A transliteration carries `X-NAME-KIND=transliteration`. vCard has no such kind, and SC-001 needs it to survive a round trip.
- Parameters Monica does not manage (`SORT-AS`, unknown `X-` ones) are copied from the kept property with the same `PROP-ID`.

## R6. Import (`VCardReader`, `ImportVCard`)

- Every `N` and `FN` is read. Pairing goes by `PROP-ID`, then by equal `LANGUAGE` and `SCRIPT`, then by order.
- Kind: `PHONETIC` means phonetic, `X-NAME-KIND` means transliteration, anything else is written.
- A card counts as variant aware only when it holds more than one `N`. Then it goes through `UpdateContactNameRepresentations`, matched by `PROP-ID`.
- A card with one `N` updates the representation its `PROP-ID` names, else the displayed one, and touches no other. The input also trusted a lone `PROP-ID` of ours; a client keeping parameters but dropping variants would then delete every other name.
- Display choice: `FN;PREF=1`, else the current display if it is still sent, else the first non phonetic representation.

## R7. vCard 3 clients

Sabre converts to 3.0 when a client asks for it (its default), and copies the `ALTID` variants as they are. vCard 3 allows one `N`.
**Decision**: a subclass of `Sabre\CardDAV\Plugin` overrides `convertVCard()` and keeps only the displayed `N` and `FN` when the target is 3.0. That client then sends one `N` back, which R6 treats as the display representation only.

## R8. Codes

- **Language**: a BCP 47 tag, validated by `^[A-Za-z]{2,3}(-[A-Za-z0-9]{1,8})*$`.
- **Script**: an ISO 15924 code, validated by `^[A-Z][a-z]{3}$`.
- **Phonetic system**: stored as its vCard token (`ipa`, `piny`, `x-romaji`, `x-rr`, `x-other`). A token imported from elsewhere (`jyut`, `script`) is kept verbatim.
- Labels come from ICU (`Locale::getDisplayLanguage`, `Locale::getDisplayScript`) in the reader's locale. So does the extra option a stored code outside the list gets. They need no translation key.

## R9. The display name

- **`name`**: `formatted_name`, else the parts in `name_order`, else Unnamed contact.
- **`buildSortName`, `buildMatchToken`, `nameForAddress` and `initials`**: fall back on `formatted_name` when there are no parts. Otherwise a 김민지 written only as a full name would sort as nameless.
- **Nameless contacts**: the model already accepts them (`buildSortName` returns an empty string), so no refusal has to be removed.

## R10. Merge

- `PreviewContactMerge` adds a `display_name` conflict when both contacts have a name. Its answers are `kept` and `other`, and an unanswered one keeps the survivor's name so existing API callers are not refused.
- `MergeContacts` moves every representation of the absorbed contact that is not an exact duplicate (same kind, codes and texts), with the ledger kind `name_representation_moved`.
- The display choice is written with the ledger kind `display_name_replaced`, which stores `was`.
- Exact duplicates stay on the absorbed contact. It is soft deleted, so they come back with it when the merge is undone (`UndoContactMerge` replays both kinds backwards).

## R11. Snapshots and deletion

- `CreateRecordSnapshot` adds `name_representations` and `display_name_representation_uuid` to `payload.record`. Nothing restores a snapshot (spec/models/023).
- `DestroyContact` soft deletes, and rows stay. `contact_id` cascades on a hard delete, and the contact's pointer is `nullOnDelete`.

## R12. Migrations (in production)

- Three new migrations, no shipped one edited: create `contact_name_representations`; add `display_name_representation_id` and `formatted_name` to `contacts`; `backfill_contact_name_representations`.
- The backfill inserts with the query builder in chunks of 500, computing the keys through `LocaleCollation` per account. It does not re-save contacts, because their hooks would queue a search and duplicate job for each one, and the display name does not change.

## R13. Screen

- An Alpine component, `resources/js/contact-name-editor.js`, holds the staged list and posts `names[i][…]` and `display` as hidden inputs on Save the name.
- The design's `role="radio"` row becomes a native radio input, so arrow keys and the announcement come for free.
- The `mn-*` classes map onto the existing components (`x-button`, `x-select`, `x-page-section`), not onto new CSS.
