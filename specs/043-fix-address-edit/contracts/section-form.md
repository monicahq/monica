# Contract: the section form for an address

Applies to the block that edits a record, `contacts.sections.update`, validated by
`SaveFieldValuesRequest` through `FieldValueRules` and written by `SetFieldValues`.

## Field names

| Input | Name | Value |
| --- | --- | --- |
| Street | `fields[{id}][street]` | text, at most 255 characters |
| Extended street line | `fields[{id}][extended]` | text, at most 255 characters |
| Post office box | `fields[{id}][po_box]` | text, at most 255 characters |
| City | `fields[{id}][city]` | text, at most 255 characters |
| Region | `fields[{id}][region]` | text, at most 255 characters |
| Postcode | `fields[{id}][postal_code]` | text, at most 255 characters |
| Country | `fields[{id}][country]` | the id of an active item of the account's Countries list, `keep`, or empty |
| Label | `labels[{id}]` | the id of an active item of the list the field names, or empty |
| Typed wording | `label_wordings[{id}]` | text, read only when the label is the open one |

Inside a repetition, `fields[{id}]` becomes `fields[{repeater}][{n}][values][{child}]`, and `labels`
and `label_wordings` sit beside `values` under `fields[{repeater}][{n}]`, as they do for an email.

The boxes are offered in the order of the table. `keep` is offered as a selected option, worded as the
stored country, only when that country names no active item of the list. Every box is optional.

## Outcomes

| Submitted | Result |
| --- | --- |
| Every box empty, whatever the label | The stored address is cleared. A required field is refused, naming the field. |
| One or more boxes filled | All seven keys are written, an empty string for an empty part, and the label beside them. |
| Country id | Stored as that item's label. |
| Country `keep` | The stored country is written back unchanged. |
| Country empty | Stored as an empty string. |
| A part over 255 characters | Refused with the part named, such as "the street of address". |
| A country id not on the list, or inactive | Refused. |
| A label not on the field's list | Refused, as today. |

## Errors

An error on a part is keyed `fields.{id}.{part}` and shown under that box. An error on the label is
keyed `labels.{id}`. An error the action raises, a required field left empty, comes back under
`fields` as today.
