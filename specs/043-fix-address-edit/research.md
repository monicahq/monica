# Research: Editable Address Field

## How the address is submitted

**Decision**: `fields[{id}]` is an array of the seven keys `value_json` holds, and inside a repetition
`fields[{repeater}][{n}][values][{child}][{part}]`. The names are in
[contracts/section-form.md](contracts/section-form.md).

**Rationale**: `FieldValueRules` already keys rules by `fields.{id}` and adds `fields.{id}.*` for a
multiselect, `SetFieldValues::given()` already hands an array through, and `SetFieldValue` already
writes `value_json => $this->value`. The stored shape and the submitted shape are one shape.

## What counts as empty

**Decision**: `SetFieldValues::isBlank()` treats an array whose every value is blank after trimming as
blank, and keeps treating `[]` as blank.

**Rationale**: `saveOne()` then clears the value, `refuseAnEmptyRequiredField()` then refuses seven
empty boxes, and a label picked beside them is dropped with them: three requirements from one line.

## How the parts are stored

**Decision**: `SetFieldValue::readTheColumn()` maps the seven keys through `TextSanitizer` and writes
all seven, an empty string for an empty part, in the card's order.

**Rationale**: `ImportVCard::address()` already writes that shape, so a value has one shape whoever
wrote it, and the formatter and the merger read `?? ''` unchanged. Filled keys only would be a second shape.

## The label rides on the enum

**Decision**: `FieldTypeEnum::usesALabelList()` gains Address, and `VCardMerger::standardType()` maps
the ADR keys `home` and `work` to the standard types of the same name.

**Rationale**: every reader of `usesALabelList()` follows without a line of its own: `SetFieldValue`,
`FieldValueRules`, `CreateField`, `UpdateField`, the field type screen, `ImportVCard::labelOf()` and
`VCardMerger::label()`, whose `LABELLED` list already names ADR.

## The country pick

**Decision**: the country box is a native select over the active items of the account's Countries
list, found by `value_lists.key`. It submits the item id, or the word `keep` when the stored country
names no active item and is being left alone. `SetFieldValues` turns the id into the item's label and
`keep` into the country already stored, so `value_json.country` stays a string.

**Rationale**: settled by clarification. A string is what a card writes and reads, so the round trip
is exact. The sentinel lets a device's country survive a save without a rule that knows the record.

**Alternatives considered**: submitting the label text, rejected because a forged request could then
type a country. Storing the item id in `value_json`, rejected because a card cannot read an id.

**Finding**: `CountryEnum` supplies ten countries. An account can add items, and a device country that
matches none is kept as it arrived, but a fuller supplied list is a change of its own.

## What the record shows

**Decision**: `FieldValueFormatter::format()` returns the joined line with the label in parentheses,
exactly as for an email. A public static `FieldValueFormatter::addressLine(array)` returns the line
alone, and `x-field-value` draws an address as it draws an email: the line and a badge.

**Rationale**: plain readers keep going through `format()`. The static gives the component the bare
line without copying the seven keys into Blade or building a formatter, which needs a user.

## The supplied list on a new field

**Decision**: `FieldTypeEnum::suppliedLabelList(): ?ValueListEnum` names the three supplied label
lists, `DefaultFieldEnum::valueList()` gains `Address => AddressLabels`, and the field type screen
preselects the supplied list when the picked type names one and nothing was chosen. Nothing preselects
a list for a new email or phone field today, so this completes 040 for those two.

## The catalogue illustration

**Decision**: the `rows` preview takes an optional `label` drawn as the closed list box above the rows,
and the address shows Home above Street, City, Postcode and Country. Every row drawn is a box the
control has, and seven rows would make the tile taller than the others for no added truth.

## What needs no change

`WriteDefaultValues`, since an address offers no default. `FieldSettingsCatalogue`, since the label
list is a matter for the field type screen. `ImportVCard`, since `labelOf()` reads the enum.
`ExportContactVCard`, which delegates to `VCardMerger`. `DetectDuplicateContacts`. Both tables.
