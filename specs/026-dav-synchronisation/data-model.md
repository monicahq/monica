# Data model: Address book and calendar synchronisation

Schema changes are two columns and one behaviour. Everything else here is derived and stored nowhere.

## Changed

| Where | Change | Why |
| --- | --- | --- |
| `contacts.dav_uri` | `string(255)` nullable, unique with `vault_id` | The `.vcf` name a client chose when it created the card keeps resolving to the contact. Null for every contact made in Erica, whose card is `{uuid}.vcf`. |
| `contacts.vcard` | `longText` nullable | The card as a device last sent it, vCard 4.0, without `PHOTO`. Null until a device writes the contact. Never read by anything but the merge. |
| `FieldValue::$touches`, `FieldValueRow::$touches` | `['valuable']` | A value saved, moved or deleted moves the record's `updated_at`, which the sync token and ETag are built from. |
| `personal_access_tokens.abilities` | Written as the chosen scopes, `[]` or `['sensitive-fields']` | First enforced scope. Existing `['*']` keys do not reach sensitive fields, checked by membership. |
| `field_values.value_json` for `address` | `{po_box, extended, street, city, region, postal_code, country}`, all optional strings | The stored shape of an address, settled by its first writer, since no screen writes one. `FieldValueFormatter` joins the non empty ones with a comma, and the input control is still owed by [022](../../spec/models/022-field-value-done.md). |
| `logs.source` | New enum case `dav` | A write from a device is told apart from the browser and the API. `source_detail` carries the key's name. |

## New enum

`ApiKeyScopeEnum { SensitiveFields = 'sensitive-fields' }`, implementing `Translatable` for its label on the screen.

## Derived, per vault

| Collection | Members | URI of a member | Writable |
| --- | --- | --- | --- |
| Address book | contacts `inGrouping(Everyone)` | `{dav_uri ?? uuid}.vcf` | yes |
| Dates calendar | recurring date values of those contacts, attached, month and day known | `{contact uuid}-{value id}.ics` | no |
| Reminders calendar | `ReminderOccurrence::upcomingInVault(vault, now + window)` | `{occurrence uuid}.ics` | no |

| Derived value | Formula |
| --- | --- |
| Sync token, getctag | greatest of `updated_at` and `deleted_at` over the vault's contacts, including trashed, as unix seconds. Reminders: greatest `updated_at` over the vault's occurrences. |
| Changes since token T | added: `created_at >= T` and visible. modified: visible, `updated_at >= T`, `created_at < T`. deleted: `deleted_at >= T`, or archived with `updated_at >= T`. |
| Card ETag | `"` md5 of `updated_at` timestamp, locale, scope `"` |
| Card `lastmodified` | `updated_at` timestamp |
| Principal | `principals/{user id}`, registered as a string through `LaravelSabre::principal()` because the package's own mapping is the email address and refuses an integer. Displayname the user's name, email the user's email |

## Ownership of a card's properties

| Property | Owned | On read | On save |
| --- | --- | --- | --- |
| `N`, `FN`, `NICKNAME`, `PHOTO`, `BDAY` | always | rewritten from the record | `UpdateContactName` or `CreateContact`; `UpdateContactAvatar`, `DestroyContactAvatar`; `SetFieldValue` on the `birthday` field |
| `EMAIL`, `TEL`, `URL`, `ADR`, `X-ABDATE` | the first F in card order, F the fields of that type, all when repeatable | value replaced, parameters kept, removed when the field is empty, appended when the record holds more | `SetFieldValue`, `CreateFieldValueRow`, an absent owned property clears its field |
| everything else | never | passes through | kept on the card, written nowhere |

A sensitive field is neither written nor cleared unless the key carries the scope; its property is left as the card had it.
