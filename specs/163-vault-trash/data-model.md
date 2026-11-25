# Data model: Vault trash

## Schema changes (new migrations only, `up()` only)

| Table | Change |
| --- | --- |
| `relationships`, `life_events`, `reminders`, `group_members`, `group_notes` | `deleted_with_type` and `deleted_with_id`, nullable, after `deleted_at`, indexed together; one migration per table |
| `group_files` | `softDeletes()`, then the same two columns |

A data migration then:

1. sets `deleted_with` to the `ContactMerge` for each `relationship_dropped` and `membership_folded` entry of a merge whose `undone_at` is null (R7);
2. moves `deleted_at` to now on every trashed row of the nine tables that is not `deleted_with_vault` and not a merge leftover (R8).

## Kinds

`App\Enums\TrashItemKindEnum`, one case per kind. Its value is the kebab name used in URLs, the API and MCP.

| Case | Value | Model | Flag | Owner of |
| --- | --- | --- | --- | --- |
| Contact | `contact` | Contact | | relationships, memberships, reminders, sole life events |
| Entity | `entity` | Entity | | |
| Group | `group` | Group | `groups` | memberships, notes, reminders, group files |
| Relationship | `relationship` | Relationship | | |
| LifeEvent | `life-event` | LifeEvent | | |
| Reminder | `reminder` | Reminder | | |
| GroupMembership | `group-membership` | GroupMember | `groups` | |
| GroupNote | `group-note` | GroupNote | `groups` | |
| GroupFile | `group-file` | GroupFile | `groups`, `file-upload` | |

## Trash item (read model, not stored)

`App\Helpers\TrashItem`, built by `App\Helpers\VaultTrash` from one union row plus its loaded record.

| Field | Source |
| --- | --- |
| kind, uuid, title | the record (a contact's name as the vault shows names, an entity's `name`, a note's title, a file's original name...) |
| context | kind specific line: whom a life event or reminder is about, both ends of a relationship, the group of a membership, note or file |
| deleted_at | `deleted_at` |
| expires_at | `deleted_at` + retention |
| owner | `deleted_with`, when that owner is itself in the trash |
| obstacle | owner in the trash, other end in the trash, or entity type deleted; null when restorable |

## State transitions

```text
live --delete--> trashed (deleted_at set, dependents trashed with deleted_with = it)
trashed --restore--> live (deleted_with cleared on it and its dependents, reminders reactivated)
trashed --delete for good | empty | purge after retention--> gone (dependents first)
```

A dependent deleted on its own before its owner keeps `deleted_with` null and stays in the trash when the owner is restored.
