# Research: CardDAV accepts any incoming vCard

Measured on sabre/dav 4.7.1 and sabre/vobject 4.6.1 by running each case through the parser.

## R1. Where a card is refused today

| Card | Where | Answer |
| --- | --- | --- |
| A line with no colon, or a space in a property name | `Reader::read`, even forgiving | 415 |
| A property name holding `_` | strict `Reader::read` in Sabre's `validateVCard` | 415 (forgiving reads it) |
| vCard 2.1 | `validate(PROFILE_CARDDAV)`, level 3 | 415 |
| Two `FN` without a shared `ALTID` | `validate`, level 3 even with repair | 415 |
| No `FN` and nothing to build one from (a `TEL` alone) | `validate`, level 3 | 415 |
| No written name part after repair | `ImportVCard::writeTheName` | 400 |
| One `EMAIL` or `TEL` on the default template | `CreateFieldValueRow` given the child field, not the repeater | 500 |
| A value refused by `SetFieldValue`, or a repeater full (`TooManyRepetitions`) | `ImportVCard::writeSlot` | 500 |

Missing `UID`, missing `VERSION` and missing `FN` with an `ORG`, `NICKNAME` or `EMAIL` are already repaired. A 2.1 card, two `FN` and a `TEL` only card all parse and convert to 4.0 without error: only the level 3 verdict refuses them.

## R2. Validation

- **Decision**: `CardDavPlugin::validateVCard` keeps today's path (the alternative name probe, then Sabre's own check) when the client sends `Prefer: handling=strict`. Every other save takes a lenient path: set the unreadable lines aside (R3), read forgiving, refuse only a body that is not a `VCARD`, run `validate(PROFILE_CARDDAV | REPAIR)` for its repairs and ignore its verdict, then serialise and set `$modified`.
- **Rationale**: every level 3 verdict left is about a card this product can read. The import is the one place deciding what a card means.
- **Rejected**: listing the level 3 messages to tolerate (couples us to Sabre's wording).

## R3. Unreadable lines

- **Decision**: read forgiving. On a `ParseException`, take the line number Sabre reports ("Line starting at N"), cut that physical line and its continuation lines, and read again, at most 50 times. Each line cut is added to the card as `X-MONICA-UNREADABLE-LINE`, its value the raw text, so the import, the kept card and the merger all see a readable card. `CardDavPlugin::convertVCard`, which every read goes through (GET, `addressbook-multiget`, `addressbook-query`), runs the parent conversion and then puts each of those properties back as its raw text, for vCard 3.0 and 4.0. A jCard keeps the property, since JSON cannot carry a raw line.
- **Rationale**: clarification 2 of the spec; one carrier for the line from arrival to every read, with no column. Sabre decides what it cannot read, so no second grammar of ours can drift from it. Reading the line number out of its message is the one dependency on its wording, so a plugin test feeds it a line with no colon: an upgrade that changes the message fails the suite instead of refusing cards.
- **Rejected**: a new column (a schema change for text that belongs to the card); writing the raw line into the kept card (every later read of it would throw).

## R4. Borrowing a name

- **Decision**: in `ImportVCard::writeTheName`, when no written row is left, build one written row whose formatted name is the first of `ORG` (first component), `NICKNAME`, `EMAIL`, `TEL`; with none of them, keep the `BadRequest`. Sabre's repair has usually filled `FN` from the first three already, so this mostly serves `TEL`.
- **Rationale**: one rule in the action that already owns the name; the merger then writes `N:;;;;` and that `FN`, so no name part appears that the device did not send (FR-006).

## R5. Several values

- **Decision**: in `writeSlot`, give `CreateFieldValueRow` the repeater (`$field->parent`), and when a growable slot empties, remove the repetition with `DestroyFieldValueRow` rather than leaving an empty one. Catch `InvalidArgumentException` and `TooManyRepetitions` around one value's write: the value stays on the kept card, and the save goes on.
- **Rationale**: the defect is the wrong field handed to the action; nothing else in the slot logic is wrong.

## R6. Addresses in the default template

- **Decision**: add an `Addresses` repeater to `DefaultFieldEnum`, holding `Address`, ordered `NewestLast`, and put it in the `Addresses` section in place of the lone field. It reaches accounts created from then on; an account's published structure is frozen (principle VI), so existing accounts keep one address until they edit their template.
- **Rejected**: migrating existing structures (principle VI forbids it).

## R7. The kept card

- **Decision**: unchanged. The kept card is the device's card read forgiving, converted to 4.0 and stripped of its photo (`VCardMerger::forKeeping`), plus R3's properties. Keeping it byte for byte is open with the user and is not part of this plan.
