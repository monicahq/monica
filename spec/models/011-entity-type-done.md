# 011. EntityType

**Status:** exists (`app/Models/EntityType.php`), built as far as the models it depends on allow
**Spec:** 5.1, 5.3

## What it is

A kind of object the user invented: Animal, Company, Recipe, Musical instrument, Wine bottle. It is the answer to the second of the two needs in section 5.1, the one where no existing object fits.

Declaring the type and describing what it holds are two separate steps (5.3). The type is the noun. The structures come from [Template](012-template-in-progress.md), and a single type may have several, with a different default per vault ([VaultDefaultTemplate](018-vault-default-template-done.md)). The family vault and the professional breeding vault both handle Animals, at completely different levels of detail.

It comes before the template in the implementation order because a template that applies to entities needs to say which type it applies to.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the API, alongside the readable slug
account_id                  int                             account the type belongs to
name_singular               string (255)                    what one of them is called, used in headers and buttons
name_plural                 string (255)                    what several of them are called, used in list titles
slug                        string (64)                     stable identifier used in URLs and by the API, unique within the account
description                 text                            what the type is for
icon                        string (64)                     icon shown in navigation and next to each record
colour                      string (7)                      hex colour used the same way
position                    int                             order the types appear in the navigation
is_active                   boolean                         whether new records of this type can be created
entity_count                int                             running count across all vaults, shown on the settings screen
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `hasMany` [Entity](020-entity-done.md)
- `hasMany` [Template](012-template-in-progress.md)
- `hasMany` [VaultDefaultTemplate](018-vault-default-template-done.md)

## Rules

- Both a singular and a plural name are stored. Deriving one from the other does not survive translation, and section 3.7 makes plural handling explicit.
- An entity never changes type (5.3). It may change the structure it displays with at any time, which is a different thing.
- Deactivating a type stops new records being created and leaves existing ones readable and editable.
- Deleting a type is only offered when it holds no records. Otherwise the user deletes the records first, deliberately.
- A type belongs to the account and its records belong to vaults. The type is visible in every vault, and whether it is used in a given vault is decided by whether a default template is set there.

## Not done yet

- No API. The settings screens exist: the list that orders the types, and the form that declares one and changes it.
- `hasMany` [Entity](020-entity-done.md) is now declared and `entity_count` is kept in step by the actions that write a record down, delete one and bring one back, so the guard in `DestroyEntityType` is true rather than merely written, and `CreateEntity` refuses an inactive type. What is still missing is the marketplace side of the type, which belongs to [MarketplaceInstallation](047-marketplace-installation-done.md).

## Decisions taken while building it

- **The slug is built from the singular name and never rebuilt.** The spec calls it stable and says it travels in URLs and through the API, so a rename leaves it alone: "Animal" becoming "Pet" is the same type, and a link that already went out has to keep working. Uniqueness within the account is kept by counting up, and a deleted type does not release its slug, so a link cannot later land on something else.
- **The slug is the route key; the uuid stays for the API.** The spec gives the type both, describing the slug as readable and the uuid as unguessable. A settings URL wants the readable one. Because the slug is only unique within an account, the model resolves a route binding against the account of whoever is signed in: two accounts that both invented "animals" would otherwise race for the same row.
- **The icon is a name, not markup.** A type stores one of a fixed set of words and `<x-entity-icon>` draws it, so the glyph on the list, the glyph in the picker and the glyph in the navigation cannot drift apart. The colour is likewise one of six rather than free, so a navigation of a dozen types stays readable in both themes.

## Open questions

- Whether an entity type can be shared on the marketplace on its own, or only through a template that uses it. Section 7 describes publishing templates and sections. Publishing a template that applies to a type nobody has means the type must travel with it, so the installation flow has to create the type. That is settled in [MarketplaceInstallation](047-marketplace-installation-done.md), but publishing a bare type is not.
