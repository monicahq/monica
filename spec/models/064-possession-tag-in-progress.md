# 064. PossessionTag

**Status:** in progress (`app/Models/PossessionTag.php`)
**Spec:** 3.2, specs/092-possession-tags, specs/094-add-possession-screen, specs/101-possession-detail-tags

## What it is

A word an [Account](001-account-in-progress.md) puts on its [possessions](062-possession-in-progress.md) to sort them across types and locations: insured, to sell, fragile. Tags belong to the account, shared by all its vaults, and are managed from the account settings.

A possession carries any number of tags, each once.

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account the tag belongs to
name                        string (50)                     what it reads, required
colour                      string (7)                      one of the vault colours
```

## Rules

- A name is 1 to 50 characters once markup and surrounding blank space are stripped, and unique in the account ignoring case. Changing only the case of a tag's own name is allowed.
- A tag added without a colour takes the first vault colour. A tag created from a possession's page takes the vault colour after the colour of the account's latest tag, wrapping after the last.
- The list is sorted by name in the reader's language.
- Deleting a tag is permanent, after a confirmation naming it and saying how many possessions carry it; they lose it and nothing else.
- Tags are managed in the account settings, through the API (`/api/v1/possession-tags`) and through the MCP server. Adding, changing and deleting one is written to the changelog.
