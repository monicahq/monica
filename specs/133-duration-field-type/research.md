# Research: Duration field type

## R1. Storage

**Decision**: `field_values.value_number`, no migration.

**Rationale**: the column's own comment in `2026_08_11_000002_create_field_values_table.php` already
reads "value for the number, rating and duration types", and it is indexed per field. Principle VII
forbids a column per field anyway.

## R2. Entry and display follow life events

**Decision**: one number box in minutes; display `trans_choice(':count minute|:count minutes', $n)`.

**Rationale**: life events already take `duration_in_minutes` in one box
(`_life-event-panel.blade.php:273`) and show it with that phrase (`ContactTimelineBlock.php:266`),
which is translated in every locale. Two durations in one product that read differently is the
inconsistency to avoid, and the existing phrase costs no new translation.

**Rejected**: hours and minutes in two boxes (an Alpine control, two new strings, and a second
behaviour beside life events); Carbon `CarbonInterval::forHumans()` (its locale data is not the
application's `lang/*.json`, so wording would escape the glossary).

## R3. Validation

**Decision**: `['integer', 'min:1', 'max:100000']` in `FieldValueRules::forOne()`.

**Rationale**: the bounds of `SaveLifeEventRequest` and the life event API controllers.

## R4. What the enum case carries for free

Adding `case Duration = 'duration'` reaches, with no other change: the picker's `isAvailable`
(`FieldCatalogueViewModel.php:199`), the web and API field creation rules (`Rule::enum`), the MCP
`create-template-field` and `replace-life-event-type-fields` schemas, the life event type editor,
the structure file reader, the account export (`WriteAccountExport.php:564` reads `valueColumn()`)
and its import (`WritesMonicaItems.php:748`). The settings panel takes the `default => []` branch:
a duration has no settings.

What it does not reach, because each is an explicit `match` or list: `valueColumn()`,
`hasAnInput()`, `FieldValueRules::forOne()`, `FieldValueFormatter::format()`, the `field-input`
component, and `ContactListCatalogue`'s list of types a column may show.

## R5. Reading a stored duration back into the box

**Decision**: `(int)` the stored value in `field-input`.

**Rationale**: MySQL and PostgreSQL return the decimal column as `"90.000000"`, SQLite as a float
(see the comment in `FieldValueFormatter::number()`). The Number type shows it raw, which is its own
matter and not touched here.

## R6. Family sentences

**Decision**: delete the `blurb` key from all nine families in `FieldCatalogueViewModel`, its three
`@return` shapes, and the `<p>` at `field-type.blade.php:153`; then `monica:localize` drops the nine
strings from every locale.

**Rationale**: hiding them would leave nine translated strings nobody reads. No test asserts them.
