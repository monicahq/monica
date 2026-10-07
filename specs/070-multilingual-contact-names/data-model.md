# Data model: Multilingual contact names

## `contact_name_representations` (new migration)

| Column | Type | Rule |
| --- | --- | --- |
| `id` | bigint | |
| `uuid` | uuid, unique | API identifier and vCard `PROP-ID` |
| `contact_id` | foreignIdFor(Contact), cascade on delete | owner |
| `kind` | string, `ContactNameKindEnum` | `written`, `transliteration`, `phonetic` |
| `language_tag` | string(35), nullable | BCP 47 |
| `script_code` | char(4), nullable | ISO 15924 |
| `phonetic_system` | string(32), nullable | vCard token; required when `kind` is phonetic, null otherwise |
| `formatted_name` | string(255), nullable | exact text, vCard `FN` |
| `given_name`, `additional_name`, `family_name` | string(255), nullable | components, vCard `N` |
| `prefix`, `suffix` | string(50), nullable | as on `contacts`, so the copy fits |
| `name_order` | string, `NameOrderEnum`, nullable | null follows the user, as on contacts today |
| `sort_name` | string(511) | family, given, additional, else formatted name, lowercased |
| `search_key` | string(511), index | `foldForSearch` of the text this representation shows |
| `match_key` | string(64), index | `matchKey` of family, else given, else formatted name |
| `vcard_alt_id` | string(64), nullable | `ALTID` received |
| `vcard_property_id` | string(255), nullable | `PROP-ID` received from a device, when not our uuid |
| `position` | unsigned int | order on screen, 1 based |
| timestamps | | |

## `contacts` (new migration, `Schema::table`)

| Column | Type | Rule |
| --- | --- | --- |
| `display_name_representation_id` | foreignIdFor(ContactNameRepresentation), nullable, null on delete | must belong to this contact |
| `formatted_name` | string(255), nullable | projection of the display representation's formatted name |

`prefix`, `first_name`, `middle_name`, `last_name`, `suffix`, `name_order` stay as the projection of
`prefix`, `given_name`, `additional_name`, `family_name`, `suffix`, `name_order` (research R1). They
leave `#[Fillable]`. `nickname` and `maiden_name` are unchanged.

## Invariants

- A contact with at least one representation points at exactly one of its own, never a phonetic
  one; with none it points at nothing. A phonetic representation holds parts and no formatted name. Only `UpdateContactNameRepresentations`, `UpdateContactName`, `CreateContact`,
  `MergeContacts` and `UndoContactMerge` move the pointer, each in a transaction.
- The contact's `saving` hook copies the display representation into the projection whenever the
  pointer changes; a representation's `saved` and `deleted` hooks re-save the contact when it is the
  displayed one, then reindex search either way.
- Slug, `sort_name`, `sort_key` and `contacts.search_key` read the projection only, so a secondary
  change leaves them alone.

## Model `ContactNameRepresentation`

`#[Fillable]` the columns above except the three keys; `belongsTo(Contact)`; never bound from a
route, since every write names its contact and its rows by uuid within that contact;
`displayText()` returns the formatted name or the parts in order.
`Contact` gains `nameRepresentations()` (ordered by `position`), `displayNameRepresentation()` and
`nameFor(?string $script = null, ?ContactNameKindEnum $kind = null)`.

## Enum

`ContactNameKindEnum`: `Written = 'written'`, `Transliteration = 'transliteration'`,
`Phonetic = 'phonetic'`, with a translated `label()`: Written form, Transliteration, Pronunciation.
