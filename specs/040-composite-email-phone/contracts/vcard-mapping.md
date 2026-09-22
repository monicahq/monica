# Contract: how a label reaches and leaves a card

Applies to `VCardMerger` (CardDAV and the vCard endpoint) and to `ImportVCard`.

## Writing

| The value's label | What is written |
| --- | --- |
| A wording somebody typed | `X-ABLabel` carrying that wording, whatever the label beside it says |
| Matches a standard type | `TEL;TYPE=cell:...`, `EMAIL;TYPE=work:...` |
| Matches nothing standard | `X-ABLabel` in the property's item group, carrying the label's own wording |
| Absent | `X-ABLabel` carrying the field's name, which is what is written today |

Standard types, matched on the list item's key rather than its translated wording, so an account
reading in French still exports `TYPE=work`.

| Key | `TEL` | `EMAIL` |
| --- | --- | --- |
| `mobile` | `cell` | not applicable |
| `home` | `home` | `home` |
| `work` | `work` | `work` |
| `fax` | `fax` | not applicable |
| `personal` | not applicable | `personal` |
| `other` | `other` | `other` |
| `custom...`, and anything an account invented | `X-ABLabel` | `X-ABLabel` |

The keys are read off the two enums rather than spelled out again, so a case added to either one
reaches the card without this table being the thing that has to remember.

A sensitive field is still written on no card at all, which `VCardMerger::writeSlot()` already
enforces before any of this is reached.

## Reading

An incoming property is matched to a label in this order, and the first hit wins.

1. `X-ABLabel` in the property's group, compared against the wording of the field's list items.
2. `TYPE`, compared against the keys in the table above, with `cell` reading as `mobile`.
3. An `X-ABLabel` matching nothing is kept as a typed wording against the open label, the item keyed `custom...`, when the list holds one.
4. Nothing matches and the list has no open label, so the value is recorded with no label rather than with an invented one.

A label arriving for a list that does not hold it is not added to the list. The account owns its
lists, and a synchronising device may not write to them.

## Round trip

Exporting then importing a labelled value MUST return the same label. That is the test worth
writing, since it is the only one that catches the two sides disagreeing.
