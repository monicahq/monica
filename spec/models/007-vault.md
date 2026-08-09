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

- The vault holds contacts and the templates its records start from, and nothing else yet. Entities, activities, groups, journal days, files and share links are each their own model, and the `hasMany` sides arrive with them. So does the filter on `vault_id` for everything but contacts.
- `contact_count` is kept in step by the actions that write a contact down, delete one and bring one back. It counts every contact the vault holds, archived and partial ones included.
- `last_visited_at` is a column that stays null. Nothing opens a vault yet, since there is no screen to open one on.
- The screen that opens a vault exists, and the list of them is a placeholder waiting for its own design. The screen for one vault is still to come, so nothing warns before a deletion or says what the vault holds. `DestroyVault` refuses to take the last vault and hands the default over to the first one left; `RestoreVault` brings a vault back. The grace period is not enforced: a deleted vault stays deleted and nothing purges it, which is the safe half of the rule to leave unfinished.
- Moving a contact from one vault to another. The action does not exist, and neither does the resolution of what happens to what the contact took part in.
- Nothing seeds the `Moi` contact of a vault (4.3). [Contact](019-contact.md) knows how to create one and refuses a second in the same vault, and the vault now carries the template a new contact starts from ([VaultDefaultTemplate](018-vault-default-template.md)), so nothing stands in the way of it any more.

## Decisions taken while building it

- **`Moi` lives once per vault.** Section 4.3 says the contact is unique in the account and section 4.2 says a contact belongs to one vault, and those cannot both be literally true. Each vault holds its own contact flagged `is_me`, all representing the same person, because vault isolation is the stronger guarantee: breaking it for one row would leak across compartments in search, activities and share links.

## Open questions

- Moving an activity between vaults when its participants do not all move. Undecided.
