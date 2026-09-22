# Research: Composite Email and Phone Fields

## Where the label is stored

**Decision**: `field_values.value_list_item_id`, beside the address or number in `value_string`.

**Rationale**: three requirements come free. `DetectDuplicateContacts` reads `value_string` and needs
no change, which is FR-010. `SetFieldValue::refuseADuplicate()` compares the primary column and
ignores the label, which is FR-017. The `field_values_filter_string_index` keeps covering searches on
the address. The column already carries a foreign key onto `value_list_items` with `nullOnDelete`.

**Alternatives considered**: `value_json` holding both parts, rejected because it breaks duplicate
detection, the uniqueness check and the filter index at once. A new `value_label_id` column, rejected
because `value_list_item_id` is already the column for a chosen list item and a second one would mean
two ways to say the same thing.

## How a type says it has two columns

**Decision**: `FieldTypeEnum::valueColumn()` keeps returning the primary column, and a new
`usesALabelList(): bool` answers for email and phone. `SetFieldValue::emptyColumns()` stops clearing
`value_list_item_id` when it is true.

**Rationale**: `valueColumn()` returning a list would touch every caller for two types. The enum is
already where a consequence of a type is asked, next to `usesValueList()` and `holdsAFile()`.

**Alternatives considered**: widening `usesValueList()` to cover email and phone, rejected because it
drives `typesNeedingAValueList()`, `FieldSettingsCatalogue::offersChoices()` and the guard in
`CreateField` that refuses a choice field with no list. FR-006 says an email field with no list is
complete, so the two questions have different answers and cannot share a method.

## How the label is submitted

**Decision**: a sibling array `labels[{fieldId}]`, and `labels` beside `values` inside a repetition
row.

**Rationale**: the form already carries `year_is_unknown[{fieldId}]`, `month_is_unknown[{fieldId}]`
and `reminders[{fieldId}]` this way, and the recurring date is the existing case of a value with a
second part. `SetFieldValues` already takes those as constructor arrays.

**Alternatives considered**: making `fields[{id}]` an array of `value` and `label`, rejected because
every reader of `fields.{id}` would change, including the repeater nesting and the validation
attribute names, for no gain.

## Which label reaches the card

**Decision**: the value's label, as a standard `TYPE` parameter where it matches one vCard knows, as
`X-ABLabel` otherwise, with the field's own name written only when the value carries no label. The
mapping is in [contracts/vcard-mapping.md](contracts/vcard-mapping.md).

**Rationale**: settled by clarification. Standard types are what every address book renders natively.

**Alternatives considered**: writing both the type and the field name every time, rejected because
the supplied field is called "Phone" and repeating that on every number is noise.

## The API gap

**Finding**: no endpoint reads or writes a contact's field values, for any type. FR-013 cannot be met
for writing without building that surface first.

**Decision**: ship without it, keep the logic in `SetFieldValue`, and record the deviation in
[plan.md](plan.md). The vCard endpoint already returns both parts.

## What needs no change

`DetectDuplicateContacts`, the `field_values` table, the `fields` table, `WriteDefaultValues`,
`FieldValueRules::choiceRule()` which the label rule reuses as it stands, and `ExportContactVCard`,
which delegates to `VCardMerger` so there is one writer to change rather than two.
