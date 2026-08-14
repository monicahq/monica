# 017. Field

**Status:** exists (`app/Models/Field.php`), built as far as the models it depends on allow
**Spec:** 5.2, 5.5, 5.6, 3.5, 10.2, 13

## What it is

The smallest unit of the personalisation engine: "Street", "City", "Shoe size", "Diet". A field belongs to a section version, has a type, and carries its own constraints. It does not belong to a page directly. The section placement decides which page renders it.

It is where three separate parts of the product meet. Section 3.5 makes accessibility a delivery condition and points out why it lands here: because pages are generated from structures users invented, a field type has to render accessibly by construction, once, or every template anybody builds becomes a new source of problems nobody can fix on their behalf. Section 10.2 makes any recurring date field a possible reminder source, not just a birthday. Section 13 requires every field to be searchable and filterable with no configuration at all.

## Fields

```
id                          int                             primary key
account_id                  int                             account the field belongs to
section_version_id          int                             section version the field belongs to
type                        string (32)                     what kind of field it is, from the catalogue below
key                         string (64)                     stable identifier within the section, used by the API and by imports
label                       string (255)                    label shown next to the input, null when it falls back to its translation key
label_translation_key       string (255)                    translation key of a supplied label
help_text                   string (255)                    clarification shown under the input
placeholder                 string (255)                    hint shown inside an empty input
position                    int                             order the field appears in inside the section
is_required                 boolean                         whether a value must be given before the record can be saved
is_sensitive                boolean                         whether the value is masked until revealed, and excluded from share links
is_highlighted              boolean                         whether the field also appears in the header area of the record
is_unique                   boolean                         whether two records in the vault may not hold the same value
value_list_id               int                             list the choices come from, for the choice types, null otherwise
default_value               json                            value a new record starts with, null when it starts empty
options                     json                            settings specific to the type, such as min, max, decimals, accepted mime types
validation                  json                            extra constraints, expressed so the API and the interface enforce the same ones
is_repeatable               boolean                         whether the field is a repeater, letting the same block be filled several times
repeat_min                  int                             fewest repetitions allowed, null for no floor
repeat_max                  int                             most repetitions allowed, null for no ceiling
parent_field_id             int                             repeater this field sits inside, null when it sits directly in the section
is_reminder_source          boolean                         whether a recurring date field here may drive a reminder rule
relation_target             enum (contact, entity)          what a relation field points at, null for other types
relation_entity_type_id     int                             entity type a relation field points at, null when it points at contacts
created_at                  datetime
updated_at                  datetime
```

## The type catalogue

Section 5.5 lists the families. The ones in the first version (15) are marked.

| Family | Types | In v1 |
|---|---|---|
| Simple | `text`, `textarea`, `number`, `boolean`, `url`, `email`, `phone` | yes |
| Choice | `select`, `multiselect`, `radio` | yes |
| Choice | `tags` | later |
| Date | `date`, `recurring_date` | yes |
| Date | `duration` | later |
| Relation | `relation` | yes |
| Relation | `qualified_relation` | later |
| File | `file`, `image` | yes |
| File | `avatar` | yes |
| Composite | `address`, `repeater` | yes |
| Composite | `money`, `full_name` | later |
| Layout | `divider`, `heading`, `help` | yes |
| Other | `rating`, `rich_text`, `colour`, `location` | later |
| Calculated | `computed` | later |

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `belongsTo` [SectionVersion](015-section-version-in-progress.md)
- `belongsTo` [ValueList](009-value-list-in-progress.md)
- `belongsTo` self as the parent repeater
- `belongsTo` [EntityType](011-entity-type-done.md) as a relation target
- `hasMany` [FieldValue](022-field-value-done.md)
- referenced by [Reminder](040-reminder-not-yet.md) as a source

## Rules

- Every field type renders accessibly by construction (3.5). The label is bound to the input, the error is announced, the required state is programmatic and not only visual, and the whole thing works with a keyboard alone. This is a property of the type, implemented once, not something checked per template. The builder screen itself is held to the same standard.
- `recurring_date` allows an unknown year (5.5). A birthday whose year nobody knows is a normal case, not an error, and the storage in [FieldValue](022-field-value-done.md) reflects that.
- A `recurring_date` field is a reminder source (10.2). A birthday is one case among many, and nothing in the reminder machinery may special case it.
- The layout types store nothing. They carry no value row and are skipped by search, export and share links.
- A repeater holds its children through `parent_field_id`. A repeater inside a repeater is refused. Section 16 already names the repeater as the most exposed field for accessibility, and nesting it would compound that for no stated need.
- `is_sensitive` masks on screen and excludes from share links by default (5.6, 4.4). It is protection from a glance over the shoulder, not an authorisation mechanism, and nothing in the product may treat it as one. The owner can always reveal it.
- `is_highlighted` puts the field in the header area, rendered according to its type: a pill for tags, a countdown for a recurring date (5.6).
- A highlighted field stays in the record header even when its section belongs to a page that is not selected. Page navigation never changes which highlighted values are visible.
- A field is created, edited and deleted only on a draft section version. On a published one it is frozen (6).
- Removing a field from a new version does not delete its stored values. They are marked as detached and remain restorable (3.3). Any reminder rule that read from it is disabled with an explanation rather than removed (10.3).
- `key` is stable and unique inside the section. Renaming a label never changes the key, which is what lets an import, an export or an API caller keep working after a rename.

## Not done yet

- **Fifteen of the eighteen types render, and three do not.** `<x-field-input>` is where a type becomes a control, which is what keeps the rule that a type renders accessibly by construction true in one place rather than per screen. `relation`, `address` and `avatar` are not there: a relation needs a search across the vault, an address is several inputs that travel together, and an avatar is the one file type nothing asks for, since a contact carries its own. `FieldTypeEnum::hasAnInput()` is what the page, the validator and the save all read so none of them can disagree about it.
- **The three layout types are built and never drawn on a record.** A divider, a heading and a help block carry no value, and the record keeps only the fields that do, so a section reads as its answers with nothing breaking them up. The builder lists them like any other field, so they can be placed and are the one thing placing has no effect on: they are what a long page needs, and the page they would break up is the one that does not draw them.
- **The three things a field promises about stored values are all kept.** `is_unique` is enforced across the vault by `SetFieldValue`, `default_value` is written to a new record by `WriteDefaultValues`, and `is_sensitive` renders masked. Values survive the removal of a field as detached and restorable (3.3), and there is now a screen that lists them.
- **`is_reminder_source` is stored and read by nothing.** [Reminder](040-reminder-not-yet.md) is the model that reads it.
- **`validation` is still read by nothing, and deliberately.** What a form enforces is built from the rest of the row by `FieldValueRules`: the type, `is_required` and the list a choice comes from. Whether `validation` is a free form structure or a fixed vocabulary is the open question below, and section 3.6 requires the API and the interface to enforce the same rules, so enforcing half of an undecided thing is worse than enforcing none of it.
- **`options` is interpreted for a file and for nothing else.** `Field::acceptedMimeTypes()` reads `accepted_mime_types`, the upload box offers those kinds and `FieldValueRules` refuses anything else, so what a field takes is a property of the row rather than of whoever drew the control. Every other setting a type might want is written as given and read by nobody.
- **Search and filtering with no configuration (13) is untouched.** [SearchIndexEntry](054-search-index-entry-not-yet.md) is where it starts.
- **Ten of the types named in section 5.5 are not in the enum.** `tags`, `duration`, `qualified_relation`, `money`, `full_name`, `rating`, `rich_text`, `colour`, `location` and `computed` each arrive with the storage and the rendering they need, rather than as a string somebody can already write into the column.
- **No API.** The screens exist: the catalogue that picks a type, and the inspector beside the builder that edits the field.
- **The inspector edits four things and clears the rest.** It offers the label, the help text, the placeholder and the three switches, and it sends nothing else; `UpdateField` writes every column it was given, so saving the inspector wipes `is_unique`, `default_value`, `options`, `validation`, the repetition limits and `is_reminder_source` on the field being edited. Renaming the supplied "File" field therefore forgets which kinds of file it takes. The value list is the one that survives, because the controller reads it off the field and hands it back. This is a fault rather than a decision, and it is what the screen for the rest of the row has to fix.

## Decisions taken while building it

- **The type catalogue is an enum, and it carries the behaviour that follows from a type.** Whether a type stores a value, reads from a value list, points at another record, can drive a reminder or is a repeater is asked of the enum rather than repeated at every call site. Only the types the first version ships with are cases: an unimplemented type that can already be written to the column is a promise the product cannot keep.
- **Settings a type cannot honour are dropped rather than stored.** A field that is not a choice keeps no value list, one that is not a relation points at nothing, one that is not a repeater carries no repetition limits, and only a `recurring_date` gets `is_reminder_source`. Storing a setting the type will never read would be storing a lie, and the next person to read the row would believe it.
- **`is_repeatable` is derived from the type and never asked for.** It is not a second, independent switch: the repeater type is what makes a field repeatable, and letting the two disagree would mean deciding which one wins.
- **The key is settled once, in underscores, and never rebuilt.** It is unique within the whole section version rather than among a field's siblings, so a field moving in or out of a repeater does not have to change identity. Underscores rather than the dashes used for a slug, because what reads a key is code and not a person.
- **The type cannot be changed after the field is added.** Changing it under values already written would quietly turn them into something else, and every type-dependent setting on the row would have to be re-derived. Removing the field and adding the one that was wanted is the honest path, and it is the one the builder should offer. The value list a choice field reads from can be swapped, since the values keep meaning what they said.
- **A choice field must name the list it offers.** A field of that type with nothing to offer is not a field somebody is part way through configuring; it is a field that cannot render.
- **Both the freeze and the repeater rule are enforced on the model,** for the reason given in [TemplateVersion](013-template-version-done.md): the specification says the application refuses the write on every path, and one guard below the actions is the only way to mean it.

## Open questions

- The builder interface itself, which section 16 calls the most delicate point in the product. Nothing above depends on how it looks, but it is where the personalisation either becomes a superpower or an unusable machine.
- Whether `validation` is a free form structure or a fixed vocabulary. Free form is flexible and cannot be enforced identically by the API and the interface, which section 3.6 effectively requires. A fixed vocabulary is the safer choice.
- Whether a field can be conditional on another field's value. Not in the specification, commonly wanted, and it would change how required fields are evaluated. Worth deciding before the builder is built rather than after.
