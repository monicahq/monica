# Data Model: Composite Email and Phone Fields

No migration. Both parts land in columns that already exist.

## What holds what

| Part | Column | Notes |
| --- | --- | --- |
| The address or number | `field_values.value_string` | Unchanged. Sanitised by `TextSanitizer` as today. |
| The chosen label | `field_values.value_list_item_id` | Was written only by `select` and `radio`. Now also by `email` and `phone`. |
| The list labels come from | `fields.value_list_id` | Was kept only for the choice types. Now kept for `email` and `phone` too. |
| A wording somebody typed | `field_values.label_wording` | New. Holds what was typed beside the open label, and nothing otherwise. |

## Rules

| Rule | Where it lives |
| --- | --- |
| The address must be a valid address, the number is taken as written | `FieldValueRules::forOne()`, unchanged |
| The label must be an active item of the list the field names | `FieldValueRules::choiceRule()`, reused as it stands |
| A label given with no address or number is discarded, and the field counts as unfilled | `SetFieldValues::isBlank()` reads the value part only |
| `is_required` is satisfied by the address or number alone | `SetFieldValues::refuseAnEmptyRequiredField()`, unchanged |
| `is_unique` compares the address or number and ignores the label | `SetFieldValue::refuseADuplicate()`, unchanged, since it compares the primary column |
| A field naming no list is complete and offers no label | `CreateField` guard stays limited to `usesValueList()` |
| The open label is the item keyed `custom...`, and it is the only one that takes a typed wording | `ValueListItem::OPEN_KEY`, asked through `takesATypedWording()` |
| A wording typed beside any other label is dropped rather than stored | `SetFieldValue::theLabel()` |
| A typed wording is what the reader, the card and the record all show | `FieldValueFormatter`, `VCardMerger`, `x-field-value` |
| Writing the value must not clear the label | `SetFieldValue::emptyColumns()` keeps `value_list_item_id` for these two types |

## Enum changes

| Method | Email and Phone answer |
| --- | --- |
| `valueColumn()` | `value_string`, unchanged |
| `hasAnInput()` | true, unchanged |
| `usesValueList()` | false, unchanged, since the list is not the value |
| `usesALabelList()` | true, new |

## Defaults

`DefaultFieldEnum::valueList()` gains `Email => EmailLabels` and `Phone => PhoneLabels`, so every
account created from the default template gets both fields already pointing at the supplied list.
`CreateDefaultTemplate` reads that method and needs no change of its own.

`EmailLabelEnum` supplies Personal, Work, School and Other. `PhoneLabelEnum` supplies Mobile, Home,
Work, Fax and Other. Both already ship and are seeded into every account.
