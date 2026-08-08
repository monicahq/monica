# 009. ValueList

**Status:** to create
**Spec:** 5.4, 3.3, 3.7, 9.3, 9.4

## What it is

A named set of choices defined once per account and reused by any number of fields: genders, countries, languages, currencies, phone labels, contact categories. Section 5.4 exists because retyping those lists inside every field is how a personalisation system becomes unusable.

The account gets a ready made set at creation. Every one of them is editable, and a supplied value can be disabled without being deleted so records already using it do not break (3.3).

## Fields

```
id                          int                             primary key
account_id                  int                             account the list belongs to
key                         string (64)                     stable identifier for a supplied list, such as countries, null for a user made one
name                        string (255)                    name the user gave the list, null when it falls back to its translation key
name_translation_key        string (255)                    translation key of a supplied name, so it reads in the user's language
description                 text                            what the list is for, shown in the field builder
is_system                   boolean                         whether the product supplied it, which only affects whether the key can be relied on
allows_user_values          boolean                         whether a user may add their own item to it
position                    int                             order the lists appear in on the settings screen
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md)
- `hasMany` [ValueListItem](010-value-list-item.md)
- referenced by [Field](017-field.md)

## Rules

- A supplied list carries both `name` and `name_translation_key`, and falls back to the translated key when `name` is null. This is the project convention for translatable defaults and it applies here so the seeded lists read correctly in every locale (3.7).
- A list is never deleted while a field points at it. It is disabled, and the field explains why (3.3).
- Renaming a list, or renaming an item inside it, must never change the behaviour of a published section (6). This is the reason a published section stores the resolved item and not just a pointer. See [SectionVersion](015-section-version.md).
- Country, language and currency lists are seeded from `database/data`, where the project already keeps that kind of reference data.

## Open questions

- **What happens on install.** Section 16 asks it directly: when a section that uses a shared list is installed into another account, is the list copied, or matched against the installer's equivalent list? Copying is predictable and produces duplicates ("Countries" twice). Matching is tidier and can silently change meaning, which section 3.1 forbids. The reading taken in [MarketplaceInstallation](047-marketplace-installation.md) is: copy, then offer the installer an explicit merge into an existing list. That keeps the guarantee and still gives a way out of duplication. It is a recommendation, not a decision.
