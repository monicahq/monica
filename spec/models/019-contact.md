# 019. Contact

**Status:** to create
**Spec:** 3.2, 4, 4.3, 5.7, 8.2, 9.5, 13

## What it is

A person being documented. It is in the immutable core (3.2), and it is the object the whole product orbits.

The nuance in section 3.2 matters here more than anywhere: the concept is frozen, its content is not. A contact will always be a person with a name. Everything else it holds comes from the template applied to it.

Only the name is imposed (5.7), and the reason is stated plainly: the product uses it constantly to sort, to search, to render headers and to generate initials, so it cannot depend on a field the user might not have created. Things that feel just as fundamental, gender, birthdate, description, are ordinary custom fields supplied by default, and can be removed, renamed or replaced.

Two special states live here. `Moi` is the contact representing the user (4.3), and a partial contact is somebody who was mentioned in a relationship and reduced to a first name until they are filled in (8.2).

## Fields

```
id                          int                             primary key
account_id                  int                             account the contact belongs to
vault_id                    int                             vault the contact lives in
template_version_id         int                             exact structure version the page renders from
uuid                        string (36)                     unguessable identifier used in URLs, share links and by the API
prefix                      string (50)                     civility, such as Dr or Mrs
first_name                  string (255)                    first name
middle_name                 string (255)                    middle name
last_name                   string (255)                    last name
suffix                      string (50)                     suffix, such as Jr or PhD
maiden_name                 string (255)                    name before marriage, kept because searching on it is common
nickname                    string (255)                    what they are actually called
name_order                  enum (given_first, family_first)  order this particular name reads in, null to follow the user preference
sort_name                   string (511)                    precomputed string the list sorts on, so sorting never depends on rendering
is_me                       boolean                         whether this contact represents the user themselves
is_partial                  boolean                         whether the contact came from a mention and is not filled in yet
is_archived                 boolean                         whether the contact is hidden from the main lists without being deleted
is_favourite                boolean                         whether the contact is pinned to the top of the list
avatar_file_id              int                             uploaded avatar, null when initials are used instead
avatar_colour               string (7)                      background colour of the generated initials avatar
last_activity_at            datetime                        when the last activity counting as a real interaction happened, null when none has
activity_count              int                             running count of activities the contact took part in
last_consulted_at           datetime                        when the user last opened the page, used to order recently viewed
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md), [Vault](007-vault.md), [TemplateVersion](013-template-version.md)
- `belongsTo` [File](008-file.md) as avatar
- `hasMany` [FieldValue](022-field-value.md)
- `hasMany` [Relationship](026-relationship.md), from both sides
- `belongsToMany` [Group](032-group.md) through [GroupMember](033-group-member.md)
- `belongsToMany` [Activity](036-activity.md) through [ActivityParticipant](037-activity-participant.md)
- `hasMany` [RecordSnapshot](023-record-snapshot.md), [Reminder](040-reminder.md), [ShareLink](048-share-link.md)

## Rules

- The name is structured by the product and never by a template (5.7). Every part is optional individually, and at least one has to be present.
- `sort_name` is written on save. Sorting a list of thousands of contacts cannot depend on concatenating at read time, and the order has to be stable regardless of who is looking.
- `is_me` marks the contact representing the user (4.3). It cannot be deleted, cannot be merged into another contact, and is identifiable stably by the API, MCP, imports and modules. Its name and its page are editable like any other contact's. Relations read from it in the first person.
- A partial contact is a real row in the graph, not free text (8.2). It is hidden from the main lists while `is_partial` is true, and it stops being partial as soon as it is enriched or merged. There is never a parallel string describing a human who does not exist as a contact.
- `last_activity_at` counts only activities whose nature is marked as a real interaction (9.3). It is what the inactivity reminder reads (10.2), and it is maintained on write because computing it on read would scan the activity table for every row in a list.
- A contact is soft deleted. Deleting it cancels future reminder occurrences and restores them if the contact comes back (10.3). Its relationships, activities and files are not destroyed.
- Changing the applied template version writes a [RecordSnapshot](023-record-snapshot.md) first, always (6).
- Every field on the page, including ones invented yesterday, is searchable and filterable with no configuration (13).

## Open questions

- **One `Moi` per account or one per vault.** See [Vault](007-vault.md). This file assumes one per vault, each flagged `is_me`, unique within its vault. If the account wide reading wins instead, `is_me` becomes account unique and every query that assumes a contact is confined to a vault has to be revisited, which is why the decision cannot be postponed past this model.
- Whether an archived contact still triggers reminders. Probably not, and the specification does not say.
