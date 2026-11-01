# Data model: Groups

Two tables. Why they depart from notes 032 and 033 is in [research.md](research.md) R3.

## groups

| Column | Type | Note |
| --- | --- | --- |
| id | id | |
| uuid | uuid, unique | API, vCard `UID`; still answers in URLs |
| slug | string 128 | readable address built from the name, rebuilt on rename, unique in the vault with deleted groups |
| account_id | foreignIdFor(Account), cascade | |
| vault_id | foreignIdFor(Vault), cascade | |
| name | string 255 | required |
| description | text, nullable | |
| started_on | date, nullable | |
| started_precision | string 16, default `unknown` | `DatePrecisionEnum` |
| ended_on | date, nullable | null while the group exists |
| ended_precision | string 16, default `unknown` | `DatePrecisionEnum` |
| dav_uri | string 255, nullable | name a device filed the card under |
| deleted_with_vault | boolean, default false | |
| timestamps, softDeletes | | `updated_at` drives the CardDAV sync token |

Index: `(vault_id, deleted_at)`; unique `(vault_id, dav_uri)` and `(vault_id, slug)`.

## group_members

| Column | Type | Note |
| --- | --- | --- |
| id | id | |
| uuid | uuid, unique | API |
| account_id | foreignIdFor(Account), cascade | |
| group_id | foreignIdFor(Group), cascade | |
| contact_id | foreignIdFor(Contact), cascade | |
| role | string 255, nullable | free text |
| joined_on | date, nullable | |
| joined_precision | string 16, default `unknown` | |
| left_on | date, nullable | null while current |
| left_precision | string 16, default `unknown` | |
| timestamps, softDeletes | | soft delete only through a merge fold or with its group |

Index: `(group_id, contact_id, left_on)`; `(contact_id, deleted_at)`.

## Models

- `Group`: `HasUuids`, `SoftDeletes`, `ResolvesModelInVault`, `#[Fillable]`; `belongsTo` Account,
  Vault; `hasMany` GroupMember; `currentMembers()` filters `left_on` null and live contacts.
- `GroupMember`: `HasUuids`, `SoftDeletes`, `ResolvesModelInAccount` (it has no `vault_id`), `#[Fillable]`; `belongsTo` Group, Contact; `isCurrent()`.
- `Contact`: `hasMany` GroupMember.

## Rules and where they live

| Rule | Owner |
| --- | --- |
| Member and group in the same vault and account | `CreateGroupMembership` |
| One current membership per contact and group (FR-005) | `CreateGroupMembership` |
| `left_on` not before `joined_on` (US3) | `CreateGroupMembership`, `UpdateGroupMembership` |
| Membership outside the group's dates warns (edge case) | form view model, never refused |
| A deleted contact's memberships are hidden, not removed | queries join live contacts |
| Deleting a group soft deletes it alone; members untouched | `DestroyGroup`, `RestoreGroup` |
| Any membership change, and a member entering or leaving the address book, touches the group's `updated_at` (sync token, R6) | every membership action, `MergeContacts`, `UndoContactMerge`, `WriteGroupCard` |
