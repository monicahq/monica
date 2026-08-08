# 007. Vault

**Status:** exists (`app/Models/Vault.php`), built as far as the models it depends on allow
**Spec:** 3.2, 4, 4.2, 11, 13, 14.3

## What it is

A watertight compartment of data. "Famille", "Pro", "Amis". Contacts, entities and activities live in one, and nothing reaches across.

The word is inherited from Monica v2, where a vault was also a unit of collaboration: people were invited into one and given a role. That half is gone (4.1). What remains is the useful half: separating contexts, so a work search does not return a family contact, so a webhook feeding a work automation never sees anything personal, and so a private link can be scoped to one part of a life without exposing the rest.

The vault is in the immutable core (3.2). Its nature is never personalisable, though section 15 leaves room for personalising its fields later.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used in URLs and by the API
account_id                  int                             account the vault belongs to
name                        string (255)                    name the user gave the vault
description                 text                            what the vault is for, shown on the vault list
colour                      string (7)                      hex colour used to tell vaults apart at a glance
position                    int                             order the vault appears in, so the user decides which comes first
is_default                  boolean                         whether new records land here when no vault is chosen
contact_count               int                             running count of contacts, so the list does not aggregate on every render
last_visited_at             datetime                        when the user last opened it, used to order the switcher
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md)
- `hasMany` [Contact](019-contact.md), [Entity](020-entity.md), [Activity](036-activity.md), [Group](032-group.md), [JournalDay](039-journal-day.md), [File](008-file.md)
- `hasMany` [VaultDefaultTemplate](018-vault-default-template.md)
- `hasMany` [ShareLink](048-share-link.md)

## Rules

- An account always has at least one vault. The last one cannot be deleted.
- Exactly one vault per account is the default.
- Every query touching contacts, entities, activities, groups, files or journal days filters on `vault_id`. This is not a convenience, it is the compartment. It applies equally to search (13), exports, webhooks (14.3), modules and share links (4.4).
- Moving a record from one vault to another is an explicit action, logged, and it must carry its dependants with it or refuse. Moving a contact that participates in an activity in the old vault is the interesting case, and the resolution belongs in the action, not here.
- Deleting a vault is soft, and it is the most destructive thing a user can do short of deleting the account. It must warn, state what it contains, and be reversible for a grace period.
- Files are stored under a path derived from account then vault (11), and no predictable name may allow crossing that boundary.

## Not done yet

- The vault holds nothing yet. Contacts, entities, activities, groups, journal days, files, default templates and share links are each their own model, and the `hasMany` sides arrive with them. So does the filter on `vault_id`, which cannot be written before there is something to filter.
- `contact_count` is a column that stays at zero. The contact model keeps it in step, and there is no contact model.
- `last_visited_at` is a column that stays null. Nothing opens a vault yet, since there is no screen to open one on.
- Nothing warns before a deletion or says what the vault holds, because there is no screen. `DestroyVault` refuses to take the last vault and hands the default over to the first one left; `RestoreVault` brings a vault back. The grace period is not enforced: a deleted vault stays deleted and nothing purges it, which is the safe half of the rule to leave unfinished.
- Moving a record from one vault to another. There is no record to move.
- The `Moi` contact seeded in the first vault (4.3), which waits on [Contact](019-contact.md), and the contact default template of [VaultDefaultTemplate](018-vault-default-template.md).

## Open questions

- **Where `Moi` lives.** Section 4.3 says the `Moi` contact is unique in the account. Section 4.2 says a contact belongs to one vault. Those two cannot both be literally true. The reading taken in [Contact](019-contact.md) is one `Moi` contact per vault, all representing the same person, because vault isolation is the stronger guarantee and breaking it for one row would leak across compartments in search, activities and share links. If the other reading is chosen instead, this file and [Contact](019-contact.md) both change.
- Moving an activity between vaults when its participants do not all move. Undecided.
