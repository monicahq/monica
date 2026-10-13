# Contract: saving a card into an address book

`PUT /dav/addressbooks/{user id}/{vault uuid}/{card}.vcf`, body `text/vcard` or `application/vcard+json`.
This replaces the two `PUT` refusal rows of [026's contract](../../026-dav-synchronisation/contracts/dav.md);
everything else there is unchanged.

## Answers

| Body | Today | After |
| --- | --- | --- |
| A valid vCard 3.0 or 4.0 | 201 or 204 | 201 or 204 |
| A card with `EMAIL` or `TEL`, default template | 500 | 201 or 204, every value written |
| Several `ADR`, default template of a new account | 201 or 204, first written | 201 or 204, every value written |
| vCard 2.1 | 415 | 201 or 204 |
| A line with no colon, or a space in a property name | 415 | 201 or 204, the line kept |
| A property name holding `_` | 415 | 201 or 204 |
| Two `FN` without a shared `ALTID` | 415 | 201 or 204 |
| No name, but `ORG`, `NICKNAME`, `EMAIL` or `TEL` | 415 or 400 | 201 or 204, named after the first of them |
| No name, `ORG`, `NICKNAME`, `EMAIL` or `TEL` | 415 or 400 | 400 |
| A value its field refuses, or more values than its repeater allows | 500 | 201 or 204, the value left on the card |
| Not a vCard at all | 415 | 415 |
| Anything needing repair, with `Prefer: handling=strict` | 415 | 415 |

No ETag is returned on a write, as today: the server holds a different card from the one sent.

## Reading a card that held an unreadable line

| Asked for | The line comes back |
| --- | --- |
| vCard 4.0 or 3.0 (GET, `addressbook-multiget`, `addressbook-query`) | as the raw text the device sent, in place of `X-MONICA-UNREADABLE-LINE` |
| jCard | as an `x-monica-unreadable-line` property holding that text |
