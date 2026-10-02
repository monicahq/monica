# 022. FieldValue

**Status:** exists (`app/Models/FieldValue.php`), built as far as the models it depends on allow
**Spec:** 5.5, 3.3, 13, 10.2

## What it is

What somebody actually typed. One row per field per record, or per field per repeater row.

This is the largest table in the product by a wide margin, and the one every screen reads from. Two decisions shape it.

The first is typed columns instead of a single JSON blob. A blob is smaller to write and cannot be filtered on. Section 13 requires structured filtering on any custom field with no configuration ("every contact whose city is Paris and whose diet is vegetarian"), and requires it on fields invented yesterday. Typed columns with indexes are what makes that a query rather than a scan.

The second is the detached state. Section 3.3 forbids silent deletion: when a field is removed from a structure, its values are not destroyed, they are set aside, explained, and restorable.

## Fields

```
id                          int                             primary key
account_id                  int                             account the value belongs to
vault_id                    int                             vault the value belongs to, so filtering never needs a join
valuable_type               string (255)                    class of the record, a contact, an entity or a life event
valuable_id                 int                             id of that record
field_id                    int                             field the value was entered for
field_key                   string (64)                     key of that field copied at write time, so the value survives the field
field_value_row_id          int                             repeater row it belongs to, null when it is not inside a repeater
value_string                string (1024)                   value for the text, url, email, phone and colour types
value_text                  text                            value for the long text and rich text types
value_number                decimal (20,6)                  value for the number, rating and duration types
value_boolean               boolean                         value for the yes or no type
value_date                  date                            value for the date and recurring date types
value_datetime              datetime                        value for a type that carries a time as well
value_json                  json                            value for the composite types, such as an address or a location. An address holds po_box, extended, street, city, region, postal_code and country, all optional strings, in the order a card writes them
value_list_item_id          int                             chosen item for the single choice types, and the label of an email or a phone
label_wording               string                          wording somebody typed for a label the list does not name, kept only when the chosen item is the open one
value_list_item_ids         json                            chosen items for the multiple choice types
value_file_id               int                             attached file for the file, image and avatar types
related_contact_id          int                             target of a relation field pointing at a contact
related_entity_id           int                             target of a relation field pointing at an entity
year_is_unknown             boolean                         whether the year of a recurring date is not known, so it is never rendered
month_is_unknown            boolean                         whether the month is not known either, for a date reduced to a year
is_detached                 boolean                         whether the field left the structure, hiding the value without destroying it
detached_at                 datetime                        when that happened, so the page can explain why the value is set aside
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md)
- `morphTo` valuable
- `belongsTo` [Field](017-field-done.md), [FieldValueRow](021-field-value-row-done.md)
- `belongsTo` [ValueListItem](010-value-list-item-done.md), [File](008-file-in-progress.md), [Contact](019-contact-done.md), [Entity](020-entity-done.md)
- `hasOne` [Reminder](040-reminder-done.md), the rule reading this exact value

## Rules

- Exactly one of the `value_*` columns is populated, decided by the field type. Which one is a property of the type, not a per row choice.
- `field_key` is copied at write time for the same reason `user_email` is copied on a log: the value has to remain interpretable after the field it came from is gone from the structure.
- A recurring date with an unknown year is stored with a placeholder year and `year_is_unknown` set (5.5). The year is never rendered and never used to compute an age. A reminder built on it still fires every year (10.2), reading the day and the month alone. Only a `recurring_date` may be missing a part: a complete `date` drives a reminder too, and it is still complete.
- A value carries at most one live reminder rule ([Reminder](040-reminder-done.md)). Clearing the value destroys this row, and the rule survives it: the rule lets go of the pointer, goes quiet and says the date it read is gone, because a rule cascaded away with the row would be the silent deletion section 3.3 forbids.
- Removing a field from a new structure version sets `is_detached` and `detached_at` on its values. They stop appearing on the page, they are listed on a dedicated screen with an explanation, and re-adding the field brings them back (3.3).
- A value pointing at a file increments that file's `reference_count`. Detaching decrements it and starts the grace period ([File](008-file-in-progress.md)).
- A value pointing at a deleted contact or entity is kept and rendered as unavailable. It is not nulled.
- Layout fields (`divider`, `heading`, `help`) never produce a row.
- Writing a value sends its record to the search engine again, on a queue, through the record it touches (specs/067-full-text-search). Saving stays instantaneous and the index catches up behind it (13).

## Not done yet

- **Free search reads the values, filtering does not yet.** A value reaches the search engine as the text the product writes for it. The typed columns and their indexes are there for structured filtering, the half of section 13 still ahead.
- **`value_datetime` is written by nothing.** No type in the first version carries a time as well as a date. The column is there because the file lists it, and the first type that needs one will be what fills it.
- **Nothing removes a field from a structure on its own.** `MoveRecordToStructureVersion` is the only thing that sets `is_detached`, and it does so inline for the one record it moves. The two actions that set aside every value of a field and put them back had no caller and have been removed. A section version that drops a field and is then republished still does not walk its values, because deciding what a field of the same key on a new section version is to a value of the old one is the builder's question, not this model's.
- **Two types have no control yet.** `relation` and `avatar` are stored and read perfectly well and cannot be typed in: a relation is a search across the vault, and an avatar is the one file type nothing asks for, since a contact carries its own. `FieldTypeEnum::hasAnInput()` is what says so, and the page draws such a field as what it holds with a line saying it cannot be edited there.
- **No API.** The screens exist: a block of the record opens as a form, and what a field holds is read back through `FieldValueFormatter`.

## Decisions taken while building it

- **A value moves the record it belongs to.** `$touches = ['valuable']` on this model and on the repetition, honoured on save and on delete, because a card's version is read from the record's `updated_at` and a value that moved without moving its record would hand a device a card it already believes it has.
- **The stored shape of an address was settled by its first writer.** A device sending a card was the first thing able to write one, so the seven parts a card carries are the seven keys `value_json` holds. `FieldValueFormatter::addressLine()` joins the non empty ones with a comma. The control on the page reads and writes the same shape: seven boxes, all seven keys written on every save with an empty string for a part left empty, and the country picked from the account's Countries list and stored as the wording of the item picked. A country a device wrote that names no item of the list is offered back as it arrived and kept on save until somebody picks one in its place, so a card keeps its country through an edit on the screen.

- **The column is asked of the type, once.** `FieldTypeEnum::valueColumn()` answers it, the way the enum already answers whether a type stores anything or reads from a list. It reads null for three different reasons, each written on the method: layout types store nothing, a repeater holds repetitions rather than a value, and a relation is decided by what the field points at rather than by its type.
- **`SetFieldValue` is the only place a value is written,** and it takes typed parameters rather than one `mixed`: a chosen item, several chosen items, a file, a contact, an entity. A single loose value would mean every caller deciding what shape to hand over, and the action guessing.
- **Writing replaces rather than adds,** on the triple of record, field and repetition, and every column the value is not written into is cleared in the same write. Leaving what was there before beside the new value is how a row ends up saying two things.
- **The unknown year is imposed rather than trusted.** A recurring date whose year nobody knows is stored under `FieldValue::PLACEHOLDER_YEAR`, rewritten by the action, so nothing downstream can read a year out of the column and believe it. The two missing parts are independent: a birthday with no year and a date somebody remembers only the year of are both normal.
- **A part missing on a type that cannot have one is dropped, not stored,** following the decision already taken on [Field](017-field-done.md) that a setting a type cannot honour is a setting that lies.
- **`is_unique` is checked against the vault,** since a vault is a compartment and two of them know nothing about each other. A field holding several values at once is skipped: uniqueness across sets is a different question and not one the specification asks.
- **A relation may not cross a vault.** The specification does not say it in as many words, and the alternative is a value that reads what is on the other side of a compartment.
- **The counts on files and on value list items are moved by the action that writes,** through the existing `AttachFile` and `DetachFile` and by hand for the items. Both are counted on write for the same reason: counting on read means aggregating the largest table in the product.
- **Clearing a value deletes the row; a field leaving the structure does not.** They look alike and are not the same event. Somebody who empties a field has nothing to be explained or restored later; a field taken out of a structure leaves values that are set aside, listed and restorable.
- **A section is what gets saved, not a value and not a page.** `SetFieldValues` takes one block of the record and writes it in one transaction. It is the unit somebody actually fills in: the record reads until somebody opens a block, and what is then in front of them is that block's questions and nothing else, so that is what a save may write and what it may refuse. The refusal is narrower than it looks for the same reason: a required field in another section was never on the screen, nobody declined to answer it, and holding this block hostage to it would be refusing an edit because of a question somebody has not been asked. Nothing here can say a record is complete, and nothing here pretends to.
- **Which block is open is the address, not a flag held on the client.** Cancelling means asking for the record again, which is what makes it genuinely throw away what was typed. The page is not in the address alongside it: the section already knows which page it sits on, and naming it twice is a way for the two halves to disagree.
- **An empty file box means unchanged, not cleared.** It is the one exception to the rule that an empty field is cleared, and it has to be: a browser cannot put a file back into an input it once left, so the ordinary rule would delete somebody's document as soon as they fixed a typo in its description. Replacing a document means choosing another one, and removing it means removing the repetition it sits on.
- **A repeater is saved whole, with the block it sits in.** What arrives carrying the number of a repetition is that repetition, what carries none is a new one, and a number that does not arrive at all is one somebody took away. Adding and removing repetitions is therefore part of saving the block rather than a set of writes of its own, which is what lets the block go on promising that nothing is written until it is saved.
- **A field left empty is cleared rather than skipped.** Somebody who deletes what they typed and saves has said something, and a save that quietly kept the old answer would be losing their edit rather than keeping their data. A field that was already empty is left alone, so a save that changed nothing writes nothing and says nothing in the journal.
- **`SetFieldValues` writes nothing itself and logs nothing itself.** Each value goes through `SetFieldValue`, each clearing through `DestroyFieldValue`, each repetition through `CreateFieldValueRow` and `DestroyFieldValueRow`, and each upload through `CreateFile`, which is where the journal entries come from. A line saying a block was saved, above a list repeating what each field now holds, would be the same story told twice.
- **The placeholder year never reaches a screen, including the date control.** A date input has to be given a year, and handing back the stored placeholder would put 1900 in front of somebody as though they had typed it. The control is filled with the current year instead: the day and the month are what was entered and what comes back, and the save writes the placeholder again while the box stays ticked.
- **Reading a value is its own class.** `FieldValueFormatter` turns a stored value into what a page shows, because the page and the form that edits it have to agree about what a value says, and because none of it belongs to the value: a choice is read as its item's label, a date in the order the reader reads dates, a number with the separators they use.

## Open questions

- Whether `value_string` should be indexed wholesale or only where a field is marked filterable. Indexing everything is the promise section 13 makes and it costs write throughput. Measuring is the only honest way to settle it. The databases have already forced half an answer: MySQL caps an index at 3072 bytes, so the filter reads the first 255 characters of the value there, and PostgreSQL will refuse a row whose indexed value runs past about 2700 bytes, which a 1024 character column can do in a multibyte language. Whatever settles the question has to settle the length of the column with it.
- Whether a value should keep its own edit history, or whether [Log](004-log-in-progress.md) is enough. The log already carries before and after values, which suggests it is enough.
