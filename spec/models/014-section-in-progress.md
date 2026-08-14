# 014. Section

**Status:** exists (`app/Models/Section.php`), built as far as the models it depends on allow
**Spec:** 5.2, 7

## What it is

A reusable thematic block: "Address", "Social networks", "Medical follow up". A template version contains pages, and each page assembles sections in the order the user chose.

Sections exist as their own object rather than as a grouping owned by a page for one specific reason given in section 5.2: so a single block can be shared without dragging the whole page around it. Somebody who built a good medical follow up block can publish that block alone. Section 7 calls this out as what lowers the barrier to contributing. Pages organise a template, but they do not change the independent identity or reuse of a section.

Like a template, this model is the stable identity. The content lives in [SectionVersion](015-section-version-in-progress.md).

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the API, alongside the readable slug
account_id                  int                             account the section belongs to
name                        string (255)                    name the user gave it, null when it falls back to its translation key
name_translation_key        string (255)                    translation key of a supplied name
description                 text                            what the block is for, shown in the picker and on the marketplace
slug                        string (64)                     stable identifier used by the API, unique within the account
icon                        string (64)                     icon shown in the section header
is_system                   boolean                         whether the product supplied it at account creation
is_active                   boolean                         whether it can still be added to a template
published_version_id        int                             version offered by default when adding it, null while only drafts exist
locale                      string (5)                      language the labels inside it are written in
installed_from_listing_version_id  int                       marketplace version it was copied from, null when it was built here
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `hasMany` [SectionVersion](015-section-version-in-progress.md)
- `belongsTo` [SectionVersion](015-section-version-in-progress.md) as the published one
- `belongsToMany` [TemplateVersion](013-template-version-done.md) through [TemplateVersionSection](016-template-version-section-done.md)
- `hasOne` [MarketplaceListing](045-marketplace-listing-done.md) when it is published

## Rules

- A section is not owned by a template or a page. It can appear in several templates and in several versions of the same template, on different pages and at different positions.
- A section carries no page or position of its own. Where it sits is a property of the assembly, and it lives on [TemplateVersionSection](016-template-version-section-done.md).
- A section can be published to the marketplace on its own, without a template around it (7).
- Deleting a section is refused while any published template version contains one of its versions.
- The account is seeded with the sections the default contact template needs, so the product works out of the box.

## Not done yet

- **Everything the marketplace needs.** `installed_from_listing_version_id` and the `hasOne` [MarketplaceListing](045-marketplace-listing-done.md) are not in the schema, for the same reason they are absent from [Template](012-template-in-progress.md): the tables they point at do not exist. Publishing a section on its own is what section 7 says lowers the barrier to contributing, and it is the first thing the marketplace models should make possible.
- **Nothing shares a section between templates.** An account is seeded with six of them, all marked as supplied and all carried by the one template that was seeded with them. Reuse is what the model is for and what makes drafting a section version worth the machinery, and no screen offers to put an existing section on another template.
- **A section has no screen of its own.** It is created, retitled and taken off from inside the builder of the template carrying it, since a section with no template around it is not yet something anybody has a reason to open. The screen that lists sections for their own sake arrives with the marketplace, which is what makes a bare section worth having.
- **No API.**

## Decisions taken while building it

- **A section is created with its first draft version.** A section with no version is a name that asks for nothing, so `CreateSection` writes both in one transaction, exactly as a template does.
- **A section carries no position, and the schema has no column for one.** Where a section sits is a property of the assembly and lives on [TemplateVersionSection](016-template-version-section-done.md). Giving the section one as well would let the two disagree.
- **Deleting is refused only while a published template version carries one of its versions.** A section sitting in somebody's abandoned draft goes freely: nothing has been promised about a draft, and refusing there would leave sections nobody can get rid of. The check joins through the assembly to the status of the template version rather than reading `usage_count`, which counts drafts too.
- **Deactivating is what a shared section gets instead of deletion.** It stops the section turning up when somebody assembles a template and leaves every template version already carrying it untouched.

## Open questions

- Whether a section may contain another section. Nesting would let a "Contact details" block reuse "Address" instead of copying its fields. It also makes the builder harder to render accessibly, which section 16 already flags as the riskiest screen in the product. Not in the first version.
