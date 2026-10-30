# Research: Tag contacts with categories

## R1. One action finds or creates a category from a wording

**Decision**: a new action `FindOrCreateValueListItem` takes a list, a wording and the items the field
already holds. It trims the wording and compares it with `mb_strtolower` to every item of the list
(FR-002). It returns an active match. It returns a stopped match only when the field already holds it,
and otherwise refuses with `ValueListItemIsStopped` (FR-003). With no match it calls
`CreateValueListItem`, which already asks `addItem` (FR-004) and journals `ValueListItemCreation`
(FR-010).

**Rationale**: three callers need the rule (R2, R4, R5). `ImportVCard::labelOf()`
(`ImportVCard.php:347-375`) already matches this way, in PHP. **Alternatives**: SQL `LOWER()` (no
precedent, three collations); one match per caller.

## R2. Inline creation rides on the contact's save

**Decision**: `SetFieldValues` takes `newChoices`, posted as `new_choices[{fieldId}][]`. Inside its
`DB::transaction` (`SetFieldValues.php:75`), each wording goes through R1 and joins the chosen ids. An
abandoned form sends nothing, and a refused save rolls back what it created (FR-001, US1.5). The offer
belongs to the Multiselect type in `x-field-input` (`field-input.blade.php:236-261`) and is drawn when
the user `can('addItem', $list)`. It therefore serves every multiselect over a list that accepts
values, because constitution IX builds accessibility into a field type once.

**Alternatives**: create the item at once over Alpine Ajax (an abandoned form would still have created
it); a control for Category only (a second editor for one field type).

## R3. The filter is a query string over the JSON column

**Decision**: `Contact::scopeHoldingAnyCategory(array $itemIds)` adds a `whereExists` on `field_values`
with one `orWhereJsonContains('value_list_item_ids', $id)` per id. The ids arrive as `?categories[]=`,
beside the existing `sort` and `query`, validated against the account's "Contact categories" list.
`ContactsViewModel::matching()` and `Api\ContactController::index` apply it after `inGrouping`
(FR-005). The offered filters (FR-006) are one query of the vault's category values, counted per
contact in PHP.

**Rationale**: a multiselect is one `field_values` row holding a JSON array, with no pivot
(`create_field_values_table.php:44`). `whereJsonContains` runs on all three databases, and 1,000
contacts need no index (SC-002, measured in quickstart).

**Alternatives**: a pivot mirroring the JSON (two copies of the truth); a route segment (taken by the
grouping).

## R4. `CATEGORIES` becomes a managed vCard property

**Decision**: `OwnedFields::propertyFor()` (`OwnedFields.php:37-47`) maps a Multiselect over the
account's "Contact categories" to `CATEGORIES`. Export writes one `CATEGORIES` with the labels of every
such field (FR-007). Import gives its parts to the first such field in structure order through R1,
skipping a refused part. A card without `CATEGORIES` empties the field, as `writeSlot()` does for every
managed property.

**Rationale**: the stored card is kept whole (`keepTheCard`), so a structure without a Category field
round-trips `CATEGORIES` untouched (US3.4). Creating from a device departs from the docblock of
`labelOf()` by the spec's clarification, and that docblock is amended to say so.

**Note**: no vCard file import exists. `ImportVCard` is called only from `AddressBookBackend`, so
"import" in US3 means CardDAV creating a card.

## R5. Monica 4 tags become categories, and the rollback knows what it made

**Decision**: `WritesMonicaFourItems` gives the tags to the contact's first Category field through R1
and `SetFieldValue`. It writes the note only when no such field exists or `addItem` refuses (FR-008).
Each item R1 creates is recorded in the import item's `payload['value_list_items']`.
`RollBackAccountImport` deletes those after `releaseTheChoicesOf()`, keeping any with a `usage_count`
above zero (FR-009). The ledger already carries `value_lists` and `entity_types`
(`RollBackAccountImport.php:246-247`), and nothing tracks items created inside an existing list today.

## R6. The API mirrors the section save and the list

**Decision**: the API gains a section save with `new_choices`, a `categories[]` filter on the list, a
list of the offered categories, and `categories` on `ContactResource`. MCP's `ListContacts` takes
`categories`. See [contracts/api.md](contracts/api.md).

**Rationale**: no API route writes a contact's fields today (`routes/api.php:240-286`), and
`ContactResource` exposes no field value, so a client could neither tag nor find an id.
**Alternatives**: `PUT contacts/{contact}/categories` of labels (a second write path for one type).

## R7. No flag

Each story stands alone, and the two behaviours that change are what the spec asks for.
