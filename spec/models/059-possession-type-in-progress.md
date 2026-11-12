# 059. PossessionType

**Status:** in progress (`app/Models/PossessionType.php`)
**Spec:** 3.2, specs/088-possession-types

## What it is

A kind of thing an [Account](001-account-in-progress.md) owns: office equipment, a vehicle, a musical instrument. It says which details a thing of that kind carries, through its [groups](060-possession-field-group-in-progress.md) and [fields](061-possession-field-in-progress.md). Types belong to the account, shared by all its vaults, and are managed from the account settings.

It is the one place user defined fields live outside the Template, Page, Section, Field mechanism (constitution principle VII).

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account the type belongs to
name                        string (255)                    what it is called, required
colour                      string (7)                      one of the vault colours
icon                        string (30), nullable           one of the glyphs of the section catalogue, or none
```

## Rules

- A new type is named "Untitled type" in the language of the person creating it, and takes the first vault colour. An empty name is refused afterwards.
- The default types each carry an icon of their kind. A type without an icon is drawn with the catalogue's stand-in glyph; a glyph outside the catalogue is refused.
- Deleting a type deletes its groups, its fields and its [possessions](062-possession-in-progress.md) with their values.
- A type is exported as a versioned JSON file holding its structure only (name, colour, icon, groups, fields), and imported as a new type; an unreadable file creates nothing and lists every problem.
- Types are managed in the account settings, through the API (`/api/v1/possession-types`) and through the MCP server. Creating, changing, deleting and importing one is written to the changelog.
