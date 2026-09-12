# Contract: the DAV endpoint and the key scope

Base `/dav`. Every request needs `Authorization: Basic base64(email:key)`. Locale is the user's, rate limit is the API's, 60 a minute per key.

## Tree

| Path | Kind | Notes |
| --- | --- | --- |
| `/dav/` | root | `PROPFIND` answers 207. `current-user-principal` points at the principal below. |
| `/dav/principals/{user id}` | principal | `displayname`, `email-address`, `addressbook-home-set`, `calendar-home-set`. |
| `/dav/addressbooks/{user id}/` | address book home | one child per vault of the account |
| `/dav/addressbooks/{user id}/{vault uuid}/` | address book | `displayname` = vault name, `getctag`, `sync-token`, `supported-address-data` 3.0 and 4.0 |
| `/dav/addressbooks/{user id}/{vault uuid}/{card}.vcf` | card | `GET`, `PUT`, `DELETE`, `getetag` |
| `/dav/calendars/{user id}/` | calendar home | two children per vault |
| `/dav/calendars/{user id}/{vault uuid}-dates/` | calendar, `VEVENT` only | `displayname` = vault name and the word for dates, read only ACL |
| `/dav/calendars/{user id}/{vault uuid}-reminders/` | calendar, `VTODO` only | `displayname` = vault name and the word for reminders, read only ACL |

Reports honoured: `addressbook-multiget`, `addressbook-query`, `calendar-multiget`, `calendar-query`, `sync-collection`.

## What a card carries

A card read back is the card the device last saved, with the properties below rewritten from the record. Anything else on it comes back exactly as sent.

| Property | From | Rule |
| --- | --- | --- |
| `UID` | the card's own, else `dav_uri ?? uuid` | stable across devices |
| `N`, `FN` | the name parts, the assembled name | `N` is family;given;additional;prefix;suffix, maiden name and name order stay home |
| `NICKNAME` | nickname | |
| `PHOTO` | avatar | JPEG data URI, absent when the contact is drawn as initials |
| `EMAIL`, `TEL`, `URL`, `ADR` | fields of that type, structure order | the first F of a kind in card order are the record's, F the fields of that type or all when repeatable; their parameters are kept, a new one gets `item{n}.X-ABLabel` = field label |
| `BDAY` | the recurring date whose field key is `birthday` | `--MMDD` when the year is unknown |
| `X-ABDATE` | every other recurring date | same ownership rule as the four above |
| `REV` | `updated_at` | |

A field marked sensitive appears only when the key carries `sensitive-fields`; otherwise its property is left as the card had it. Nothing else from the record appears.

## Answers

| Situation | Answer |
| --- | --- |
| No credential, wrong key, revoked key, the account password, username not the key's owner | 401, `WWW-Authenticate: Basic` with the application's name as the realm |
| A vault of another account, or a card, event or task not in the named vault | 404 |
| `PUT` of a body that is not a card, or of one without the `FN` the standard requires | 415, from the standard's own validation |
| `PUT` of a card that validates but carries no usable name part | 400 |
| `PUT` with a stale `If-Match` | 412 |
| `DELETE` of the Me contact's card | 403 |
| Any write to a calendar, or `MKCOL`, `MKCALENDAR`, `ACL`, `PROPPATCH`, `DELETE` on a collection | 403 |
| `PUT` creating a card | 201 and no `ETag`; the card lives at the address the client chose |
| `PUT` updating, `DELETE` of a card | 204 and no `ETag` |
| `MOVE` of a card into another address book | the standard's fallback: created in the target vault, the source contact removed, both journalled |

The endpoint accepts the seventeen methods the package routes, so a method it once refused at the router, `MKCALENDAR` and `ACL` among them, is now refused by the node that owns the address. No write answers with an `ETag`, because the card kept is not the card sent: a device told otherwise would sit on a version that differs from ours, so it reads the card again instead.

## The key scope, on the API and the screen

| Surface | Change |
| --- | --- |
| `POST /api/v1/api-keys` | optional `sensitive_fields` boolean, default false |
| `GET /api/v1/api-keys`, `/{apiKey}` | attribute `sensitive_fields` boolean |
| Settings, Security, API keys | checkbox on the mint form, badge on each key that has it, the DAV address with a copy button |
| `resources/docs/api/04-api-keys.php` | the field and the parameter; `12-devices.php` is a guide page and joins no route |
