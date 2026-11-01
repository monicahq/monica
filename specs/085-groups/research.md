# Research: Groups

## R1. Which existing capability to copy

Life events for the vault record (actions, restoration, vault list, API, MCP tools, docs page);
relationships for the contact rules (`CreateRelationship`: account and vault checks).
Set aside: a generic "collection" abstraction shared with relationships.

## R2. The flag

`groups` (`app/Features/Groups.php`) ships off, joins the list in `tests/TestCase.php`, and guards
the web routes (`flag:groups`), the API, the MCP tools, the contact block, the share link toggle and
the group cards. It is deleted in the pull request that releases groups.

## R3. Schema departs from models 032 and 033

Dropped from note 032: `group_type_id` (clarified), `avatar_file_id` (the spec says no photo),
`is_active` (it is `ended_on` being null) and `member_count` (`withCount` is enough; nothing sorts on
it). Added: `dav_uri`, the name a device filed the card under, as on contacts, and
`deleted_with_vault`, as on contacts and entities. Precision columns use `DatePrecisionEnum`, as
`life_events.date_precision` does. Notes 032 and 033 are updated in the same change.

## R4. One current membership per contact and group

Enforced in the action: a unique index over a nullable `left_on` differs across the three drivers.

## R5. The timeline

`ContactTimelineBlock` paginates life events only. Join and leave entries join the years inside the
date span the page covers, the last page taking everything older; nothing becomes a life event.
Set aside: a union query across two tables, which paginates correctly but differs per driver.

## R6. Group cards served

`AddressBookBackend` and `CollectionChanges` serve groups beside contacts. A group card is built by a
new `GroupVCard` helper: `VERSION:4.0`, `UID` (group uuid), `KIND:group`, `FN`, one
`MEMBER:urn:uuid:<contact uuid>` per current member visible in the address book (not partial, not
archived, not deleted), and the Apple pair. A member outside the address book is left out so a device
never meets a reference it cannot resolve. When the group's `updated_at` is touched, so the sync
token moves, is a rule in [data-model.md](data-model.md).

## R7. Group cards received

`createCard` and `updateCard` read `KIND` (or `X-ADDRESSBOOKSERVER-KIND`) before `ImportVCard`. A
group card goes to a new `WriteGroupCard` action, which renames from `FN`, finds members by `UID`
inside the vault, and diffs: a new member starts a membership today (day precision), a missing one
ends today. `deleteCard` on a group's address calls `DestroyGroup`.
Risk to prove first in a test: whether a contact a device created answers to the `UID` the device
gave it. `contactNamedBy` matches `uuid` against `UID`; if the uuid is not the device's UID, matching
falls back to the card address the device used.
Answered by the T029 test: a device's contact keeps its own uuid; the device's UID lives only in the
stored card. Group cards therefore name each member by the UID on that member's own card.

## R8. Merging contacts

`PreviewContactMerge` reports each membership that will fold. `MergeContacts` moves the absorbed
contact's memberships and writes two ledger kinds, `membership_moved` and `membership_folded` (the
dropped row is soft deleted, the kept row's old `joined_on` and `role` kept in the entry).
`UndoContactMerge` reverses both. Merge moves no relationship or life event today; groups do not wait.

## R9. Share links

`share_links.includes_groups` already exists with its action parameters; only the toggle and the
public block (group names, never other members) are new.

## R10. Terminology

The thirteen strings that call a set of fields a "group" become "block" through `monica:localize`:
eleven in the life event type editor and the two category descriptions (life event types,
relationship types). The glossary row in `.claude/rules/translations.md` moves with them.

## R11. Changelog

`UserActionEnum` gains `GroupCreation`, `GroupUpdate`, `GroupDeletion`, `GroupRestoration`,
`GroupMembershipCreation`, `GroupMembershipUpdate`, `GroupMembershipEnd` and
`GroupMembershipDeletion`, following the existing `…Deletion` naming, sent via `LogUserAction`.
