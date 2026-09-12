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
sort_key                    binary (2048)                   collation key the list orders and pages on, built for the language of the account and compared byte by byte, so the order reads the same on every database
search_key                  string (511)                    sort_name folded for the language of the account, so a search folds what that language calls a minor difference and nothing else
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
deleted_with_vault          bool                            whether the contact was taken out by the deletion of its vault, so restoring the vault knows who to bring back
dav_uri                     string (255)                    the .vcf name a device chose when it created the card, unique within the vault, null for a contact made here, whose card is its uuid
vcard                       longtext                        the card as a device last sent it, kept whole so nothing a device wrote is lost, read by nothing but the merge that hands a card back
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md), [TemplateVersion](013-template-version-done.md)
- `belongsTo` [File](008-file-in-progress.md) as avatar
- `hasMany` [FieldValue](022-field-value-done.md)
- `hasMany` [Relationship](026-relationship-not-yet.md), from both sides
- `belongsToMany` [Group](032-group-not-yet.md) through [GroupMember](033-group-member-not-yet.md)
- `belongsToMany` [Activity](036-activity-not-yet.md) through [ActivityParticipant](037-activity-participant-not-yet.md)
- `hasMany` [RecordSnapshot](023-record-snapshot-done.md), [Reminder](040-reminder-done.md), [ShareLink](048-share-link-done.md)

## Rules

- The name is structured by the product and never by a template (5.7). Every part is optional individually, and at least one has to be present.
- `sort_name` is written on save. Sorting a list of thousands of contacts cannot depend on concatenating at read time, and the order has to be stable regardless of who is looking.
- `is_me` marks the contact representing the user (4.3). It cannot be deleted, cannot be merged into another contact, and is identifiable stably by the API, MCP, imports and modules. Its name and its page are editable like any other contact's. Relations read from it in the first person.
- A partial contact is a real row in the graph, not free text (8.2). It is hidden from the main lists while `is_partial` is true, and it stops being partial as soon as it is enriched or merged. There is never a parallel string describing a human who does not exist as a contact.
- `last_activity_at` counts only activities whose nature is marked as a real interaction (9.3). It is what the inactivity reminder reads (10.2), and it is maintained on write because computing it on read would scan the activity table for every row in a list.
- A contact is soft deleted. Deleting it cancels future reminder occurrences and restores them if the contact comes back (10.3). Its relationships, activities and files are not destroyed.
- Deleting a [Vault](007-vault-in-progress.md) takes its contacts with it, marked as having gone with it. Restoring the vault brings back exactly those, and leaves somebody deleted on their own beforehand deleted.
- **An archived contact triggers no reminder.** Archiving deactivates the rules about them and cancels their future occurrences, the way deleting does, and unarchiving reactivates the rules and puts what is ahead back (10.3). Past occurrences are kept, because they are history, and the rules say why they are quiet instead of vanishing (3.3). This covers the inactivity rule too: a contact nobody is meant to hear about cannot fire for silence.
- Changing the applied template version writes a [RecordSnapshot](023-record-snapshot-done.md) first, always (6).
- Every field on the page, including ones invented yesterday, is searchable and filterable with no configuration (13).
- The avatar is a [File](008-file-in-progress.md) like any other upload, and choosing one moves `reference_count` through `AttachFile` and `DetachFile` (11). A contact carrying none is drawn as its initials, and taking one off never deletes: the file goes orphan and the sweep comes for it once the grace period has run out.
- The page hierarchy comes from the precise [TemplateVersion](013-template-version-done.md) assigned to the contact. The identity header and highlighted fields remain above it. One template page renders without tabs, while two or more use the accessible tab navigation defined in section 5.2.

## Not done yet

- **Search and filtering are still ahead.** A contact holds what somebody typed, a block of the record is the form that writes it, and the record reads it back. What is missing is the promise that every field is searchable with no configuration (13), which starts at [SearchIndexEntry](054-search-index-entry-not-yet.md). The list searches on the name and the nickname, and on nothing anybody wrote in a field.
- **`last_activity_at`, `activity_count` and `last_consulted_at` stay null and zero.** They are maintained on write by the models that produce them, and neither [Activity](036-activity-not-yet.md) nor a screen to consult a contact on exists.
- **Silencing is built and reversing it is not.** Archiving and deleting a contact deactivate the rules about them and cancel the [ReminderOccurrence](041-reminder-occurrence-done.md) rows still ahead with the matching reason, and nothing already past is ever touched. Nothing puts either back. The actions that unarchived a contact and restored a deleted one were reached from nowhere and have been removed, so a rule silenced by archiving or by deletion stays silent, and the rule stated above is the specification rather than what the code does.
- **A partial contact is created by passing a flag.** Nothing produces one on its own, since a mention comes from [Relationship](026-relationship-not-yet.md). What exists is the state and the fact that the main lists ignore it.
- **Merging is not here.** [ContactMerge](034-contact-merge-done.md) is its own model, and with it the rule that a partial contact stops being partial when it is merged.
- **`name_order` does not fall back to the user preference.** The column says null means "follow the user", and a contact carrying none reads given name first whatever that user chose. The preference is stored on [User](002-user-done.md) and asked for on no screen, so nothing here has anything to follow yet.
- **Archiving has an action nobody can reach, and unarchiving and restoring have none at all.** `ArchiveContact` is written and no screen and no endpoint calls it; `UnarchiveContact` and `RestoreContact` had no caller either and have been removed. The list already offers the archived as one of its three places. It is therefore a list nobody can put anybody on, and the soft deletion is a deletion as far as anybody using the product can tell. Favouriting is the one of the five that has come out: `api/v1/vaults/{vault}/contacts/{contact}/favourite` sets it, and no screen does.
- **The API reads and writes a contact, and not what is written on one.** `api/v1/vaults/{vault}/contacts` lists, searches, sorts, groups and creates, and one contact is read, deleted, renamed, favourited, given or stripped of a picture, and moved to another published template version, each at its own address. What no endpoint touches is the record itself: no field value is read or written over the API, so everything a template asks about somebody is reachable in the interface and nowhere else. Archiving and restoring have no endpoint either.

## Decisions taken while building it

- **A device reads and writes a contact over CardDAV, and the card it sends is kept whole.** `dav_uri` and `vcard` are what that costs the model: the name a client chose for the card, so it keeps resolving, and the card itself, so a property the structure has no field for survives the next refresh instead of being stripped. The product never reads, searches or shows the kept card; the only reader is the merge that writes the record's values over it. One card is kept per contact, the one the last device sent, so removing a property on a device removes it everywhere.

- **`sort_name` is written by the model, not by the actions.** One `saving` hook builds it, so no path that writes a name can forget to keep it in step. It reads family name first whatever order the contact itself reads in, since a list sorted one way for some rows and another way for others is sorted no way at all, and it is lowercased so the order does not depend on the collation of the database underneath.
- **The rule that a contact needs a name is enforced in the same place.** Building the sort name is exactly the moment the question "is there a name at all" gets asked, and a contact left with none of the parts is refused there rather than in each action.
- **One `Moi` per vault.** Section 4.3 says the contact is unique in the account and section 4.2 says a contact belongs to one vault; those cannot both be literally true. Vault isolation is the stronger guarantee, so each vault holds its own contact flagged `is_me`, and `CreateContact` refuses a second one in the same vault. See [Vault](007-vault-in-progress.md).
- **`contact_count` counts every contact the vault holds**, including the archived and the partial ones. It is the size of what the vault carries, which is what somebody about to delete one needs to be told; the count of what a list shows is the list's business.
- **A new contact starts on the vault's contact default**, and the caller may name another published version instead. The default is a version and never a template ([VaultDefaultTemplate](018-vault-default-template-done.md)), so nothing here picks the latest one.
- **A contact is put on a published version only.** A draft is something somebody is still editing, and a contact rendering from it would change under them; a retired version is no longer offered for anything new.
- **Archiving, favouriting and deleting are each their own action**, as they are for every other model here, rather than one action taking a flag.
- **The name and the deletion share one screen.** Neither is a field: every other question on a record comes from a structure somebody chose, the name is there whatever they choose, and deletion is not a question about the person at all. They are the same errand, reached from the same button on the record, and a product that hides deletion behind a third place is a product people cannot leave.
- **A record is moved between versions one at a time, from two places.** The banner offers the version the template publishes now, and the version history offers the way back to an earlier published one. Both are the same act and the same route, and naming no version means the one published now. What can be named is narrow on purpose: a published version of the template this record already renders from, so the address cannot put somebody on another account's structure, on a draft nobody has finished, or on a version that has been retired.
- **The version history is read from consecutive snapshots, not from any one of them.** A snapshot is taken before a record moves and records the version it is leaving, so the version that follows a snapshot is the next snapshot's, or the one the record is on now for the last of them. A snapshot whose two versions are the same moved nothing, and is left out: it was taken by hand, or by an import, or by a restore that put the record back where it already was, and it is not a line in a history of structures.
- **The avatar is one square crop and the original is not kept.** An avatar is drawn in a circle at four sizes between 34 and 104 pixels and nowhere else, so what is kept is a 512 pixel square WebP written on upload by `ResizeImage`, and the file the account is charged for is the one the product actually serves. Keeping the original as well would put a five megabyte photograph behind a forty pixel row and count bytes nothing draws. It follows that the general variant pipeline [File](008-file-in-progress.md) still owes is not what this leans on.
- **The name, the picture and the deletion share one screen.** The picture is not a field either: no template decides whether somebody has a face, and the product draws one wherever the person appears. It is therefore edited beside the name, on the screen reached from the record's one way in.
- **Taking the picture off detaches and does not delete.** `DestroyContactAvatar` nulls the column and lets the file go, exactly as replacing one does with the file it replaced. The sweep is the only thing that removes bytes, which is what lets a restored state of a record find the picture it had.
- **Archiving silences reminders.** The specification does not settle it, and both readings hold together: archiving is about the lists, or archiving is about attention. Attention wins, because being told about somebody is the loudest thing the product does, and a colleague from two jobs ago whose birthday still arrives every year is exactly what somebody archives to stop. Archiving therefore reuses the machinery deletion uses, with its own reason ([Reminder](040-reminder-done.md), [ReminderOccurrence](041-reminder-occurrence-done.md)), so a rule that went quiet can say it is the archiving and not somebody having disabled it. Half of that is built: `ArchiveContact` silences with `record_archived`, and the action that revived exactly what that reason silenced has been removed, so `ReactivateReminders` is now reached only by the pause screen and only for `paused_by_user`.

## Open questions

None. This model is settled.
