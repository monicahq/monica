# 015. SectionVersion

**Status:** to create
**Spec:** 6, 3.1, 5.4

## What it is

One frozen state of a section, and the thing that actually owns fields. Everything said in [TemplateVersion](013-template-version.md) about freezing applies here identically, one level down.

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

- `belongsTo` [Account](001-account.md)
- `belongsTo` [Section](014-section.md)
- `belongsTo` self as the previous version
- `hasMany` [Field](017-field.md)
- `belongsToMany` [TemplateVersion](013-template-version.md) through [TemplateVersionSection](016-template-version-section.md)

## Rules

- A published version is immutable, enforced by the application, exactly as in [TemplateVersion](013-template-version.md).
- Fields belong to the section version, never to the section. Adding a field to a published section means publishing a new version of it.
- `resolved_value_lists` holds the items each list contained at publication, with their keys and labels. It is what makes the freeze real across the value list boundary (6). Reading a stored value still resolves through the live [ValueListItem](010-value-list-item.md) when it is still there, so a relabelled option reads with its new label. The snapshot is what answers when the item is gone, and it is what travels to another account on install.
- Retiring a version leaves every template version already containing it untouched.
- A published version is never deleted. A draft can be.

## Open questions

- Whether `resolved_value_lists` should be a full copy or only the items the fields in this section actually reference. Only what is referenced is smaller and enough. Full copies make an install into another account simpler because the whole list arrives intact. This ties directly into the open question in [ValueList](009-value-list.md) and should be answered with it.
