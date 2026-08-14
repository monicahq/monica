# 015. SectionVersion

**Status:** exists (`app/Models/SectionVersion.php`), built as far as the models it depends on allow
**Spec:** 6, 3.1, 5.4

## What it is

One frozen state of a section, and the thing that actually owns fields. Everything said in [TemplateVersion](013-template-version-done.md) about freezing applies here identically, one level down.

This is also where the cascade in section 6 is enforced. A published section version pins its fields, and a field that reads from a value list pins the state of that list. Renaming an option elsewhere in the product cannot change what a section published yesterday does, and that is only true if the version keeps enough of the list to stand on its own.

## Fields

```
id                          int                             primary key
account_id                  int                             account the version belongs to
section_id                  int                             section this is a version of
version_number              int                             incrementing number within the section, starting at 1
status                      enum (draft, published, retired)  draft is editable, published is frozen, retired is no longer offered
change_note                 text                            what changed since the previous version
resolved_value_lists        json                            snapshot of every value list the fields read from, as it stood at publication
published_at                datetime                        when it was frozen, null while it is a draft
retired_at                  datetime                        when it stopped being offered
previous_version_id         int                             version it was created from, null for the first one
usage_count                 int                             how many template versions contain it
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `belongsTo` [Section](014-section-in-progress.md)
- `belongsTo` self as the previous version
- `hasMany` [Field](017-field-done.md)
- `belongsToMany` [TemplateVersion](013-template-version-done.md) through [TemplateVersionSection](016-template-version-section-done.md), with each placement assigned to one [TemplateVersionPage](055-template-version-page-done.md)

## Rules

- A published version is immutable, enforced by the application, exactly as in [TemplateVersion](013-template-version-done.md).
- Fields belong to the section version, never to the section. Adding a field to a published section means publishing a new version of it.
- `resolved_value_lists` holds the items each list contained at publication, with their keys and labels. It is what makes the freeze real across the value list boundary (6). Reading a stored value still resolves through the live [ValueListItem](010-value-list-item-done.md) when it is still there, so a relabelled option reads with its new label. The snapshot is what answers when the item is gone, and it is what travels to another account on install.
- Retiring a version leaves every template version already containing it untouched.
- A published version is never deleted. A draft can be.

## Not done yet

- **Nothing reads `resolved_value_lists` back.** It is written at publication and it is correct, and [FieldValue](022-field-value-done.md) now exists and reads its choice straight off the live [ValueListItem](010-value-list-item-done.md). What is missing is the fallback: a value whose item has been deleted outright reads as nothing rather than as what the snapshot says it was. That is the whole point of the column and it is the half not yet built.
- **Nothing travels to another account.** The other half of what the snapshot is for is installation, which is [MarketplaceInstallation](047-marketplace-installation-done.md).
- **Drafting a section version is never asked for.** It happens underneath the builder: editing a field on a section that is published, or that another template carries, gives the section a new version and repoints only the template being edited. `MakeSectionVersionEditable` is that step, and the builder says on the section beforehand that it will happen.
- **No API.**

## Decisions taken while building it

- **The snapshot holds only the lists the fields actually reference.** This is the open question below, answered the smaller way: a section has nothing to do with the lists it does not use, and copying the whole account into every published version would grow without bound. The tidiness argument for full copies belongs to installation, and it can be revisited in [MarketplaceInstallation](047-marketplace-installation-done.md) without changing what is written here.
- **The snapshot stores labels as they sit in the database, translation key and all.** Resolving them through `__()` at publication would bake the interface language of whoever pressed the button into a structure meant to outlive them. Each item is stored with its raw `label`, its `label_translation_key`, its key, its position and whether it was active; each list with its raw `name`, its `name_translation_key` and its key.
- **The freeze is enforced on the model, exactly as on [TemplateVersion](013-template-version-done.md),** and `resolved_value_lists` is one of the columns publication freezes. A snapshot that could be rewritten afterwards would be worth nothing.
- **Drafting from a version copies every field, repeaters first.** Fields belong to the version and never to the section, so a new draft needs its own. Copying a child before its repeater would leave it pointing at the old parent and the two versions would silently share a field, so the copy walks the parents first and maps old identifiers to new ones.
- **A draft is not deletable while any template version points at it.** `usage_count` counts drafts as well as published assemblies, which is right here: the foreign key cascades, so deleting would quietly take the section off somebody's template rather than refusing.

## Open questions

- Whether `resolved_value_lists` should be a full copy or only the items the fields in this section actually reference. Only what is referenced is smaller and enough. Full copies make an install into another account simpler because the whole list arrives intact. This ties directly into the open question in [ValueList](009-value-list-in-progress.md) and should be answered with it.
