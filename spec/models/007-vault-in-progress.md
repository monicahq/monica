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

- `belongsTo` [Account](001-account-in-progress.md)
- `hasMany` [Contact](019-contact-done.md), [Entity](020-entity-done.md), [Activity](036-activity-not-yet.md), [Group](032-group-not-yet.md), [JournalDay](039-journal-day-not-yet.md), [File](008-file-in-progress.md)
- `hasMany` [VaultDefaultTemplate](018-vault-default-template-done.md)
- `hasMany` [ShareLink](048-share-link-not-yet.md)

## Rules

- An account may hold no vault at all. The last one goes like any other, and the account is taken back to the state it started in, which is the screen that explains what to do first.
- Exactly one vault per account is the default, whenever the account holds a vault. The first vault created is the default, and so is the next one created by an account left with none.
- Every query touching contacts, entities, activities, groups, files or journal days filters on `vault_id`. This is not a convenience, it is the compartment. It applies equally to search (13), exports, webhooks (14.3), modules and share links (4.4).
- Moving a record from one vault to another is an explicit action, logged, and it must carry its dependants with it or refuse. Moving a contact that participates in an activity in the old vault is the interesting case, and the resolution belongs in the action, not here.
- Deleting a vault is soft, and it is the most destructive thing a user can do short of deleting the account. It must warn, state what it contains, and be reversible for a grace period.
- Files are stored under a path derived from account then vault (11), and no predictable name may allow crossing that boundary.

## Not done yet

- The vault holds contacts, entities and the templates its records start from. Files carry a `vault_id` and are served through it, and the `hasMany` side of that is read through the values that point at one rather than declared here. Activities, groups, journal days and share links are each their own model, and their `hasMany` sides arrive with them. So does the filter on `vault_id` for everything but records.
- `contact_count` is kept in step by the actions that write a contact down, delete one and bring one back. It counts every contact the vault holds, archived and partial ones included.
- The screen that opens a vault exists and stamps `last_visited_at` as it does (`RecordVaultVisit`), which is what puts the last one opened at the head of the list. The list of them is a placeholder waiting for its own design, and the screen of one vault shows its name, what it is for and how many contacts it holds. Everything else on that screen is invented, since reminders, tasks, the journal and the record of what was opened recently are all later models.
- The settings screen renames the vault, changes what it is for and its colour, says what a new contact starts from, and deletes the vault; the deletion asks first and says how many contacts go with it. `DestroyVault` takes the contacts with it and hands the default over to the first vault left, or to nobody when there is none; `RestoreVault` brings the vault back with the contacts that left with it, and takes the title back when nobody holds it. The grace period is not enforced: a deleted vault stays deleted and nothing purges it, which is the safe half of the rule to leave unfinished, and nothing in the interface calls `RestoreVault` yet.
- Moving a contact from one vault to another. The action does not exist, and neither does the resolution of what happens to what the contact took part in.
- Nothing seeds the `Moi` contact of a vault (4.3). [Contact](019-contact-done.md) knows how to create one and refuses a second in the same vault, and the vault now carries the template a new contact starts from ([VaultDefaultTemplate](018-vault-default-template-done.md)), so nothing stands in the way of it any more.

## Decisions taken while building it

- **`Moi` lives once per vault.** Section 4.3 says the contact is unique in the account and section 4.2 says a contact belongs to one vault, and those cannot both be literally true. Each vault holds its own contact flagged `is_me`, all representing the same person, because vault isolation is the stronger guarantee: breaking it for one row would leak across compartments in search, activities and share links.

## Open questions

- Moving an activity between vaults when its participants do not all move. Undecided.
