# 009. ValueList

**Status:** exists (`app/Models/ValueList.php`), built as far as the models it depends on allow
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

## Not done yet

- Three of the six lists section 5.4 names. Genders, phone labels and contact categories are seeded; countries, languages and currencies are not. They are reference data of a different size, six hundred rows an account and six hundred labels a locale, and they wait on `database/data` and on the field types that need them.
- Nothing stops a list being deleted while a field points at it, because there is no [Field](017-field.md) to point. `DestroyValueList` deletes softly and takes the items with it. The rule arrives with the field.
- Nothing reads a list yet. The fields that pick from one are model 017, and until they exist a list is a set of choices nothing offers.
- The supplied names and labels are held as cases of an enum, one per list, so `monica:localize` finds them the way it finds any other translated string. A list seeded from a data file could not be found that way, which is one more thing countries and currencies need before they arrive.

## Open questions

- **What happens on install.** Section 16 asks it directly: when a section that uses a shared list is installed into another account, is the list copied, or matched against the installer's equivalent list? Copying is predictable and produces duplicates ("Countries" twice). Matching is tidier and can silently change meaning, which section 3.1 forbids. The reading taken in [MarketplaceInstallation](047-marketplace-installation.md) is: copy, then offer the installer an explicit merge into an existing list. That keeps the guarantee and still gives a way out of duplication. It is a recommendation, not a decision.
