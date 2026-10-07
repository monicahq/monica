# 057. ContactNameRepresentation

**Status:** exists (`app/Models/ContactNameRepresentation.php`)
**Spec:** 5.7, specs/070-multilingual-contact-names

## What it is

One way of writing or saying the current name of a [Contact](019-contact-done.md): the name in its own script, a transliteration into another, or how it is pronounced. 김민지, Kim Min-ji and 金敏智 are three representations of one name, not three names.

A contact holds as many as somebody records, and points at the one it is shown under. Former names, several legal names and nicknames are not representations: the nickname and the maiden name stay on the contact.

## Fields

```
id                          int                             primary key
uuid                        uuid                            identifier for the API, and the PROP-ID written on the vCard
contact_id                  int                             contact whose name this is
kind                        enum (written, transliteration, phonetic)
language_tag                string (35)                     BCP 47 tag, null when not given
script_code                 char (4)                        ISO 15924 code, null when not given
phonetic_system             string (32)                     vCard PHONETIC token, set only on a phonetic representation
formatted_name              string (255)                    the name exactly as written, the vCard FN, null to build it from the parts
prefix, suffix              string (50)                     as on the contact
given_name                  string (255)
additional_name             string (255)
family_name                 string (255)
name_order                  enum (given_first, family_first) null to follow the reader
sort_name                   string (511)                    family, given and additional name, else the formatted name, lowercased
search_key                  string (511)                    the text it shows, folded for the language of the account
match_key                   string (64)                     folded family name, else given name, else formatted name
vcard_alt_id                string (64)                     ALTID it arrived with from a device
vcard_property_id           string (255)                    PROP-ID a device gave it, when not our uuid
position                    int                             order on screen, from 1
created_at, updated_at      datetime
```

The contact gains `display_name_representation_id` and `formatted_name`.

## Rules

- A representation holds a formatted name or at least one part, and a phonetic one names its system.
- A contact with a name points at exactly one of its own representations; a contact with none points at nothing and reads Unnamed contact.
- The contact's prefix, first, middle and last name, suffix, name order and formatted name are a copy of the displayed representation. The contact's `saving` hook writes them when the pointer moves, the representation's hooks when the displayed one changes, and a direct write to them on an existing contact is refused. Everything that reads a name reads that copy, so a list of thousands needs no join.
- Only the displayed representation decides the readable address, the sort position and the initials. Changing another sends the contact and the records naming it to search again, and nothing else.
- Every representation is searched: the people list through its `search_key`, vault search through the contact's `search_name`, which holds them all, one per line.
- Duplicate detection compares the representations sharing a `match_key`. A shared pronunciation produces a suggestion like any other; nothing merges by itself.
- `UpdateContactNameRepresentations` replaces the whole list in one transaction and is the only writer: the screen, the API, MCP, `CreateContact`, `UpdateContactName` and CardDAV all go through it.
- Nothing is transliterated, detected or chosen automatically, and the language of the interface never changes which name is shown.

## vCard

- Export writes one `N` per representation and one `FN` per non phonetic one, under `ALTID=1`, with `PROP-ID`, `LANGUAGE`, `SCRIPT`, `PHONETIC` and `X-NAME-KIND=transliteration`, and `PREF=1` on the displayed `FN`. A contact with one plain written name exports as it always did.
- A card with several `N` reconciles the list by `PROP-ID`; a card with one `N` changes one representation and never removes another. A vCard 3 client is sent the displayed name alone.

## Decisions taken while building it

- **The copy on the contact rather than a join everywhere.** Thirty readers and every list already read the name columns. Keeping them as a copy written in one place was cheaper than rewriting each and loading names separately.
- **A formatted name that only repeats the parts is not kept on import**, and a family first reading sets the name order instead, so a device that only ever writes parts does not freeze a name the owner later edits as parts.

## Not done yet

- **Contact creation over the API takes parts only.** A second representation is added with the list endpoint afterwards.
