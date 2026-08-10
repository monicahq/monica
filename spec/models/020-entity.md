# 020. Entity

**Status:** to create
**Spec:** 5.1, 5.3, 8.1, 9.2

## What it is

One record of a type the user invented: a specific dog, a specific company, a specific bottle. It sits beside contacts rather than inside them.

An entity is thinner than a contact because almost nothing about it is imposed. It has a name so it can be listed and searched, and everything else comes from its template. It has no structured name parts, because it is not necessarily a person.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used in URLs and by the API
account_id                  int                             account the entity belongs to
vault_id                    int                             vault the entity lives in
entity_type_id              int                             type of object it is, which never changes
template_version_id         int                             exact structure version the page renders from
name                        string (255)                    what the record is called, used in lists, headers and search
sort_name                   string (255)                    precomputed string the list sorts on
avatar_file_id              int                             image standing in for it, null when a generated one is used
avatar_colour               string (7)                      background colour of the generated avatar
is_archived                 boolean                         whether it is hidden from the main lists without being deleted
last_consulted_at           datetime                        when the user last opened the page
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md), [Vault](007-vault.md), [EntityType](011-entity-type.md), [TemplateVersion](013-template-version.md)
- `belongsTo` [File](008-file.md) as avatar
- `hasMany` [FieldValue](022-field-value.md)
- `belongsToMany` [Activity](036-activity.md) through [ActivityParticipant](037-activity-participant.md)
- `hasMany` [RecordSnapshot](023-record-snapshot.md), [Reminder](040-reminder.md)

## Rules

- An entity never changes type (5.3). It changes its display structure freely, which is a different operation.
- An entity does not participate in the human relationship system (8.1). That system is deliberately restricted to contacts, because it carries direction, periods, uncertainty, qualifiers and deduction, none of which make sense for a bottle of wine. The link between a contact and their dog is an ordinary relation field ([Field](017-field.md), type `relation`).
- An entity can take part in an activity (9.2), can be the target of a relation field from a contact or from another entity, and can carry reminders driven by its own date fields (10.2). A contract renewal date on a Company entity is a first class reminder source.
- An archived entity triggers no reminder, exactly as an archived [Contact](019-contact.md) does not. Its rules are deactivated with `record_archived` and come back when it is unarchived. A contract renewal on a company nobody deals with any more is the reason somebody archives it.
- Changing the applied template version writes a snapshot first, exactly as for a contact.
- Soft deleted, for the same reasons.

## Open questions

- What the relation interface for entities looks like in detail. Section 16 lists it as unresolved. The storage is settled here, the screens are not.
