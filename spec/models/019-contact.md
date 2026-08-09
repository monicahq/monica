# 019. Contact

**Status:** exists (`app/Models/Contact.php`), built as far as the models it depends on allow
**Spec:** 3.2, 4, 4.3, 5.7, 8.2, 9.5, 13

## What it is

A person being documented. It is in the immutable core (3.2), and it is the object the whole product orbits.

The nuance in section 3.2 matters here more than anywhere: the concept is frozen, its content is not. A contact will always be a person with a name. Everything else it holds comes from the template applied to it.

Only the name is imposed (5.7), and the reason is stated plainly: the product uses it constantly to sort, to search, to render headers and to generate initials, so it cannot depend on a field the user might not have created. Things that feel just as fundamental, gender, birthdate, description, are ordinary custom fields supplied by default, and can be removed, renamed or replaced.

Two special states live here. `Moi` is the contact representing the user (4.3), and a partial contact is somebody who was mentioned in a relationship and reduced to a first name until they are filled in (8.2).

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used in URLs, share links and by the API
account_id                  int                             account the contact belongs to
vault_id                    int                             vault the contact lives in
template_version_id         int                             exact structure version the page renders from
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

## Not done yet

- **Nothing is written on the page.** [FieldValue](022-field-value.md) and [FieldValueRow](021-field-value-row.md) are ahead, so a contact points at a structure and holds nothing in it. The `hasMany` side arrives with them, and so does the promise that every field is searchable with no configuration (13).
- **The contact cannot be moved to another template version.** That move writes a [RecordSnapshot](023-record-snapshot.md) first, always, and there is no snapshot model. Until there is, a contact stays on the version it was created on, which is the safe half of the rule to leave unfinished.
- **The version is given to `CreateContact` rather than looked up.** [VaultDefaultTemplate](018-vault-default-template.md) is what says which structure a new contact starts on in a vault, and it does not exist. The caller names the version, and nothing here picks the latest one.
- **`avatar_file_id` is a column with no foreign key.** [File](008-file.md) has no table yet. The column is there so the avatar arrives without a migration that rewrites the contact table, and the constraint arrives with the files table.
- **`last_activity_at`, `activity_count` and `last_consulted_at` stay null and zero.** They are maintained on write by the models that produce them, and neither [Activity](036-activity.md) nor a screen to consult a contact on exists.
- **Deleting a contact cancels nothing.** [Reminder](040-reminder.md) is ahead, so the cancelling of future occurrences and their restoration wait for it.
- **A partial contact is created by passing a flag.** Nothing produces one on its own, since a mention comes from [Relationship](026-relationship.md). What exists is the state and the fact that the main lists ignore it.
- **Merging is not here.** [ContactMerge](034-contact-merge.md) is its own model, and with it the rule that a partial contact stops being partial when it is merged.
- **No screen and no API.** Model and actions only.

## Decisions taken while building it

- **`sort_name` is written by the model, not by the actions.** One `saving` hook builds it, so no path that writes a name can forget to keep it in step. It reads family name first whatever order the contact itself reads in, since a list sorted one way for some rows and another way for others is sorted no way at all, and it is lowercased so the order does not depend on the collation of the database underneath.
- **The rule that a contact needs a name is enforced in the same place.** Building the sort name is exactly the moment the question "is there a name at all" gets asked, and a contact left with none of the parts is refused there rather than in each action.
- **One `Moi` per vault.** Section 4.3 says the contact is unique in the account and section 4.2 says a contact belongs to one vault; those cannot both be literally true. Vault isolation is the stronger guarantee, so each vault holds its own contact flagged `is_me`, and `CreateContact` refuses a second one in the same vault. See [Vault](007-vault.md).
- **`contact_count` counts every contact the vault holds**, including the archived and the partial ones. It is the size of what the vault carries, which is what somebody about to delete one needs to be told; the count of what a list shows is the list's business.
- **A contact is put on a published version only.** A draft is something somebody is still editing, and a contact rendering from it would change under them; a retired version is no longer offered for anything new.
- **Archiving, favouriting and deleting are each their own action**, as they are for every other model here, rather than one action taking a flag.

## Open questions

- Whether an archived contact still triggers reminders. Probably not, and the specification does not say.
