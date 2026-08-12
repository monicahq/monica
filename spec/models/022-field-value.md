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
valuable_type               string (255)                    class of the record, a contact, an entity or an activity
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
value_json                  json                            value for the composite types, such as an address or a location
value_list_item_id          int                             chosen item for the single choice types
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

- `belongsTo` [Account](001-account.md), [Vault](007-vault.md)
- `morphTo` valuable
- `belongsTo` [Field](017-field.md), [FieldValueRow](021-field-value-row.md)
- `belongsTo` [ValueListItem](010-value-list-item.md), [File](008-file.md), [Contact](019-contact.md), [Entity](020-entity.md)

## Rules

- Exactly one of the `value_*` columns is populated, decided by the field type. Which one is a property of the type, not a per row choice.
- `field_key` is copied at write time for the same reason `user_email` is copied on a log: the value has to remain interpretable after the field it came from is gone from the structure.
- A recurring date with an unknown year is stored with a placeholder year and `year_is_unknown` set (5.5). The year is never rendered and never used to compute an age. A reminder built on it still fires every year (10.2).
- Removing a field from a new structure version sets `is_detached` and `detached_at` on its values. They stop appearing on the page, they are listed on a dedicated screen with an explanation, and re-adding the field brings them back (3.3).
- A value pointing at a file increments that file's `reference_count`. Detaching decrements it and starts the grace period ([File](008-file.md)).
- A value pointing at a deleted contact or entity is kept and rendered as unavailable. It is not nulled.
- Layout fields (`divider`, `heading`, `help`) never produce a row.
- Writing a value queues a reindex for the record ([SearchIndexEntry](054-search-index-entry.md)). Saving stays instantaneous and the index catches up behind it (13).

## Not done yet

- **Writing a value queues no reindex.** [SearchIndexEntry](054-search-index-entry.md) is ahead, so the promise that saving stays instantaneous while the index catches up behind it has nothing to catch up. The typed columns and their indexes are there, which is the half of section 13 that had to be decided now.
- **`value_datetime` is written by nothing.** No type in the first version carries a time as well as a date. The column is there because the file lists it, and the first type that needs one will be what fills it.
- **Nothing removes a field from a structure on its own.** `DetachFieldValues` and `ReattachFieldValues` do the work and are called by hand or by `MoveRecordToTemplateVersion`; a section version that drops a field and is then republished does not walk its values yet, because deciding what a field of the same key on a new section version is to a value of the old one is the builder's question, not this model's.
- **`is_sensitive` masks nothing and `default_value` is handed to nobody.** Both are rendering, and there is no screen.
- **`is_required` is enforced nowhere.** Required is a statement about a whole record being submitted, and there is no action that submits one; writing one value at a time cannot honestly refuse an empty field somebody has not reached yet.
- **No screen and no API.** Model and actions only.

## Decisions taken while building it

- **The column is asked of the type, once.** `FieldTypeEnum::valueColumn()` answers it, the way the enum already answers whether a type stores anything or reads from a list. It reads null for three different reasons, each written on the method: layout types store nothing, a repeater holds repetitions rather than a value, and a relation is decided by what the field points at rather than by its type.
- **`SetFieldValue` is the only place a value is written,** and it takes typed parameters rather than one `mixed`: a chosen item, several chosen items, a file, a contact, an entity. A single loose value would mean every caller deciding what shape to hand over, and the action guessing.
- **Writing replaces rather than adds,** on the triple of record, field and repetition, and every column the value is not written into is cleared in the same write. Leaving what was there before beside the new value is how a row ends up saying two things.
- **The unknown year is imposed rather than trusted.** A recurring date whose year nobody knows is stored under `FieldValue::PLACEHOLDER_YEAR`, rewritten by the action, so nothing downstream can read a year out of the column and believe it. The two missing parts are independent: a birthday with no year and a date somebody remembers only the year of are both normal.
- **A part missing on a type that cannot have one is dropped, not stored,** following the decision already taken on [Field](017-field.md) that a setting a type cannot honour is a setting that lies.
- **`is_unique` is checked against the vault,** since a vault is a compartment and two of them know nothing about each other. A field holding several values at once is skipped: uniqueness across sets is a different question and not one the specification asks.
- **A relation may not cross a vault.** The specification does not say it in as many words, and the alternative is a value that reads what is on the other side of a compartment.
- **The counts on files and on value list items are moved by the action that writes,** through the existing `AttachFile` and `DetachFile` and by hand for the items. Both are counted on write for the same reason: counting on read means aggregating the largest table in the product.
- **Clearing a value deletes the row; a field leaving the structure does not.** They look alike and are not the same event. Somebody who empties a field has nothing to be explained or restored later; a field taken out of a structure leaves values that are set aside, listed and restorable.

## Open questions

- Whether `value_string` should be indexed wholesale or only where a field is marked filterable. Indexing everything is the promise section 13 makes and it costs write throughput. Measuring is the only honest way to settle it. The databases have already forced half an answer: MySQL caps an index at 3072 bytes, so the filter reads the first 255 characters of the value there, and PostgreSQL will refuse a row whose indexed value runs past about 2700 bytes, which a 1024 character column can do in a multibyte language. Whatever settles the question has to settle the length of the column with it.
- Whether a value should keep its own edit history, or whether [Log](004-log.md) is enough. The log already carries before and after values, which suggests it is enough.
