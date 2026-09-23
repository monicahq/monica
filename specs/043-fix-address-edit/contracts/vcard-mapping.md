# Contract: how an address reaches and leaves a card

Applies to `VCardMerger` (CardDAV and the vCard endpoint) and to `ImportVCard`. The label mechanism,
which wins between a wording and a type, the item group, and the round trip rule, is the one in
[040's contract](../../040-composite-email-phone/contracts/vcard-mapping.md). This file adds the
`ADR` column and says what changes for an address.

## The parts

`ADR` carries seven positional parts, and `value_json` holds the seven keys in the same order:
post office box, extended, street, city, region, postal code, country. Written and read one to one,
an empty string where a part is empty. Unchanged by this feature, and now written by the screen too.

The country travels as the text stored, which is the label of the picked item or what a device wrote.
Nothing translates it on the way out or matches it on the way in.

## Standard types

| Key | `ADR` |
| --- | --- |
| `home` | `home` |
| `work` | `work` |
| `holiday_home`, `other`, `custom...`, and anything an account invented | `X-ABLabel` |

Matched on the item's key, so an account reading in French still exports `TYPE=work`.

## Reading

An incoming `ADR` is matched to a label exactly as an `EMAIL` is, through `ImportVCard::labelOf()`,
which reads the enum and needs no change: the `X-ABLabel` wording first, then `TYPE`, then the open
label when the list holds one, then no label.

## Round trip

Exporting then importing a labelled address returns the same seven parts and the same label. That is
the test worth writing.
