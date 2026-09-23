# Data Model: Editable Address Field

No migration. Every part lands in a column that already exists.

## What holds what

| Part | Column | Notes |
| --- | --- | --- |
| The seven parts | `field_values.value_json` | Keys `po_box`, `extended`, `street`, `city`, `region`, `postal_code`, `country`, each a string, empty when not filled. MySQL stores JSON keys in an order of its own, so every reader walks `FieldValue::ADDRESS_PARTS` rather than the stored order. Written by `ImportVCard` today, by `SetFieldValue` now too. |
| The country | `field_values.value_json.country` | The label of the picked item of the Countries list at the time of the pick, or what a device wrote when nobody has picked since. |
| The chosen label | `field_values.value_list_item_id` | Written for `email` and `phone` since 040. Now for `address` too. |
| A wording typed beside the open label | `field_values.label_wording` | Unchanged. |
| The list labels come from | `fields.value_list_id` | Kept for `address` as for `email` and `phone`. |

## Rules

| Rule | Where it lives |
| --- | --- |
| Each part is text no longer than 255 characters | `FieldValueRules`, one rule per nested key |
| The country is an active item of the account's Countries list, or the word `keep` | `FieldValueRules`, on the `country` key |
| Seven empty boxes make an unfilled address, whatever label was picked | `SetFieldValues::isBlank()` |
| A required address is satisfied by one filled part | `SetFieldValues::refuseAnEmptyRequiredField()`, through `isBlank()` |
| The label must be an active item of the list the field names | `FieldValueRules::choiceRule()`, reused as it stands |
| A part is sanitised as plain text before it is stored | `SetFieldValue::readTheColumn()` |
| `keep` resolves to the stored country, an id to the item's label | `SetFieldValues` |
| Writing the address must not clear the label | `SetFieldValue::emptyColumns()`, through `usesALabelList()` |
| `is_unique` is not compared on an address | `SetFieldValue::refuseADuplicate()`, unchanged, since it skips an array |

## Enum changes

| Method | Address answers |
| --- | --- |
| `valueColumn()` | `value_json`, unchanged |
| `hasAnInput()` | true, was false |
| `usesValueList()` | false, unchanged, since the list is not the value |
| `usesALabelList()` | true, new |
| `suppliedLabelList()` | `ValueListEnum::AddressLabels`, new method, answering Email labels and Phone labels for the other two |

## Defaults

`DefaultFieldEnum::valueList()` gains `Address => AddressLabels`, so the Addresses section of every new
account points its field at the supplied list. `CreateDefaultTemplate` reads that method and needs no
change of its own.

`AddressLabelEnum` supplies Home, Work, Holiday home, Other and Custom..., the last being the open label that takes a wording of its own, as the email and phone lists do.
`CountryEnum` supplies ten countries, seeded into every account under the list key `countries`.
