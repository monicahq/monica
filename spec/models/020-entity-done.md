# 020. Entity

**Status:** exists (`app/Models/Entity.php`), built as far as the models it depends on allow
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
sort_key                    binary (2048)                   collation key the list orders and pages on, built for the language of the account and compared byte by byte, so the order reads the same on every database
search_key                  string (511)                    sort_name folded for the language of the account, so a search folds what that language calls a minor difference and nothing else
avatar_file_id              int                             image standing in for it, null when a generated one is used
avatar_colour               string (7)                      background colour of the generated avatar
is_archived                 boolean                         whether it is hidden from the main lists without being deleted
last_consulted_at           datetime                        when the user last opened the page
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
deleted_with_vault          bool                            whether the record was taken out by the deletion of its vault, so restoring the vault knows what to bring back
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md), [EntityType](011-entity-type-done.md), [TemplateVersion](013-template-version-done.md)
- `belongsTo` [File](008-file-in-progress.md) as avatar
- `hasMany` [FieldValue](022-field-value-done.md)
- `belongsToMany` [Activity](036-activity-not-yet.md) through [ActivityParticipant](037-activity-participant-not-yet.md)
- `hasMany` [RecordSnapshot](023-record-snapshot-done.md), [Reminder](040-reminder-done.md)

## Rules

- An entity never changes type (5.3). It changes its display structure freely, which is a different operation.
- An entity does not participate in the human relationship system (8.1). That system is deliberately restricted to contacts because it carries person-to-person roles, temporal state and the protected semantics used by genealogy and romantic history. The link between a contact and their dog is an ordinary relation field ([Field](017-field-done.md), type `relation`).
- An entity can take part in an activity (9.2), can be the target of a relation field from a contact or from another entity, and can carry reminders driven by its own date fields (10.2). A contract renewal date on a Company entity is a first class reminder source.
- An archived entity triggers no reminder, exactly as an archived [Contact](019-contact-done.md) does not. Its rules are deactivated with `record_archived` and come back when it is unarchived. A contract renewal on a company nobody deals with any more is the reason somebody archives it.
- Changing the applied template version writes a snapshot first, exactly as for a contact.
- The page hierarchy comes from the precise [TemplateVersion](013-template-version-done.md) assigned to the entity. One template page renders without tabs, while two or more use the same accessible tab navigation as a contact (5.2).
- Soft deleted, for the same reasons.

## Not done yet

- **Nothing sets the avatar.** The column points at [File](008-file-in-progress.md) and the relationship reads it, but no action and no screen picks a file for a record, exactly as for a [Contact](019-contact-done.md). Every record is drawn as its initials until something offers that choice.
- **`last_consulted_at` stays null.** It is written by the screen that consults a record, and there is none.
- **Nothing archives a record, so nothing silences a reminder about one.** The actions that archived a record and put it back silenced and revived with `record_archived`, had no caller and have been removed, so a contract renewal on an archived company is a case no code now reaches. No screen creates a rule about an entity either, because entities have no screens at all.
- **Nothing writes a record down from an activity or a relation field.** [Activity](036-activity-not-yet.md) is ahead, and the relation field points at an entity that already exists.
- **No screen, no API and no action.** The model, its factory and its relationships are all that exist. Every action that wrote a record down, renamed one, archived one, unarchived one, deleted one or brought one back had no caller and has been removed, so no code writes a record of an invented kind or takes one away. What reads them is the model, its relationships and the counts on [EntityType](011-entity-type-done.md).

## Decisions taken while building it

- **The name is enforced where the sort name is built,** as it is on a contact. One `saving` hook writes `sort_name` and refuses a record left with nothing, so no path that writes a name can forget either half. It is lowercased for the same reason: the order must not depend on the collation of the database underneath.
- **`deleted_with_vault` is carried here too**, although this file did not list it. Deleting a vault takes its contacts with it and marks them; leaving the records of the invented kinds behind would orphan every animal and every company in the vault. `DestroyVault` carries both and gives the counts back on the versions and on the types. The action that restored a vault with the records that left with it has been removed, so a deleted vault takes them with it for good.
- **A record is created only for an active type,** which is the check [EntityType](011-entity-type-done.md) said would arrive with this model. Deactivating a type stops new records and leaves the existing ones readable, and that is the whole difference between deactivating it and deleting it.
- **A new record starts on the vault's default for its own type,** and the caller may name another published version instead. A vault with no default for the type refuses rather than guesses: a type is visible in every vault, and whether it is used in one is decided there.
- **`entity_count` on the type is no longer kept in step.** The actions that wrote a record down, deleted one and brought one back maintained it and have been removed, so the column is set to zero by `CreateEntityType`, given back by `DestroyVault`, and moved by nothing else. The guard in `DestroyEntityType` therefore reads a count that cannot rise above zero.
- **An entity has no `is_favourite`.** The specification gives pinning to contacts and this file does not list it. Adding it because contacts have it would be inventing a feature out of symmetry.

## Open questions

- What the relation interface for entities looks like in detail. Section 16 lists it as unresolved. The storage is settled here, the screens are not.
