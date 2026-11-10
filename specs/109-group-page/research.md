# Research: Group page

## R1 An activity is a life event with a group

**Decision**: `life_events.group_id`, nullable. `CreateLifeEvent` takes `?Group $group` and a new
`LifeEventSourceEnum::Group`. The first ticked attendee is the action's `contact`, the others are
`participants`, so counts, `last_life_event_at` and timelines work unchanged.
**Rejected**: the group as a polymorphic participant (participation means "somebody was there" and
feeds counts and timelines); a separate activity table (a second "something that happened").

## R2 The writing box asks for a type and a date

**Decision**: the Activity mode is a short form: type `<select>` (active types in the order the type
picker shows them, the first one chosen), date (today), title, narrative, attendee checkboxes. No
custom field inputs. A type whose structure has a required field is refused with the existing
`FieldIsRequired` message; no default type has one.
**Rejected**: embedding `_life-event-panel` (324 lines built around one contact's page).

## R3 Editing an activity happens where it is already edited

**Decision**: an activity in the group history links Edit to `contacts.lifeEvents.edit` on the
page of its first participant still in the vault. Deletion is in that editor. One editor, one record.
**Rejected**: group routes wrapping `UpdateLifeEvent` and `DestroyLifeEvent`, built around a `Contact`.

## R4 A group note is a table of its own

**Decision**: `group_notes`, soft deleted, with `CreateGroupNote`, `UpdateGroupNote`,
`DestroyGroupNote`. Its date is `created_at`. Constitution VII reading is in the spec's context.
**Rejected**: a repeater field (groups have no template, by the clarification).

## R5 One history, two sources

**Decision**: `GroupHistoryBlock` reads `page × 20` of each source, merges by date (a life event's
`happened_on`, a note's `created_at`), keeps the first `page × 20`, and offers "Show more" through
`x-list-panel`. Two queries per page; a group's history is small.

## R6 Reminders take a group as their target

**Decision**: `Reminder::remindable` accepts `Group`. `CreateContactReminder` becomes
`CreateManualReminder(record: Contact|Group)`; only the archive check is contact only. Its callers
(web, API, MCP, imports) change name only. `DeactivateReminders` and `ReactivateReminders` accept
`Group`; `DestroyGroup` and `RestoreGroup` call them with `RecordDeleted` (constitution VIII).
Readers that assume a contact are fixed: `remindable.avatar` eager loads become `morphWith`
(a group has no avatar), and `ReminderRow`, `ReminderViewModel`, `ReminderController` and
`ReminderOccurrence::isStillWarranted` handle a group as they handle an entity.
**Rejected**: `CreateGroupReminder` copying eight validation rules.

## R7 The API and MCP mirror the page

**Decision**: group notes, activities, reminders and files each get endpoints under
`vaults/{vault}/groups/{group}/...` mirroring the contact ones, and one MCP tool per endpoint. Life
event and reminder resources gain a `group` attribute. MCP gets no file upload or download, as the
contact photo set the precedent. Each area has its own reference page (28 to 31).

## R8 Files on a group are a pivot

**Decision**: `group_files` (no soft delete). `AddGroupFile` runs `CreateFile` then `AttachFile`;
`RemoveGroupFile` deletes the row and runs `DetachFile`, which starts the grace period. Deleting the
group keeps the rows, so restoring it keeps the files. Download is the existing `files.show`. Both
the panel and the tab are absent when `FlagState::isOn('file-upload', $account)` is false.

## R9 Tabs are links

**Decision**: one route per tab, `x-tabs` with `href`; the member list moves to Members unchanged.
**Rejected**: Alpine tabs on one page (three paginated lists in one request).

## R10 Attendance counts

**Decision**: one grouped query over the participants of this group's life events, keyed by contact.

## R11 Export and import

**Decision**: the account export writes group notes, group files and `group_id` on life events;
the importer reads them back. The export already writes a reminder's target by type and uuid
(`WriteAccountExport::recordUuid`); the importer, which today expects a contact, learns a group.

## R12 Journal

**Decision**: `UserActionEnum` gains `GroupNoteCreation`, `GroupNoteUpdate`, `GroupNoteDeletion`,
`GroupFileAddition`, `GroupFileRemoval`. Activities and reminders log what they log today.
