# 016. TemplateVersionSection

**Status:** exists (`app/Models/TemplateVersionSection.php`)
**Spec:** 5.2, 6

## What it is

The assembly. It says which section versions a template version contains, and in which order. Section 5.2 describes a template as an assembly of sections "dans l'ordre qu'on veut", and this is that ordering.

It is a pivot with attributes rather than a plain join, because the same section can appear in several templates at different positions, under a different title, collapsed in one and open in another.

## Fields

```
id                          int                             primary key
account_id                  int                             account the assembly belongs to
template_version_id         int                             template version the section is placed in
section_version_id          int                             exact section version placed, never the section itself
position                    int                             order the section appears on the page
title_override              string (255)                    title to show instead of the section's own name, null to use its own
is_collapsed_by_default     boolean                         whether the block starts folded, so a long page stays readable
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md)
- `belongsTo` [TemplateVersion](013-template-version.md)
- `belongsTo` [SectionVersion](015-section-version.md)

## Rules

- The foreign key points at a section version, never at a section. This is what makes the freeze in section 6 cascade: a frozen template version contains frozen section versions, all the way down to the fields.
- A section version appears at most once in a given template version. Placing the same block twice on one page would give two fields the same identity, and stored values would have nowhere to go.
- Rows are immutable once the template version is published, along with the version itself.
- `title_override` exists so a shared section can be reused without being duplicated just to rename its header. It is display only and changes no identity.

## Not done yet

- **Nothing renders an assembly.** The order, the title override and the folded state are all stored and all correct, and no screen reads any of them yet.

## Decisions taken while building it

- **The rule that a section version appears once per template version is enforced twice.** A unique index on the pair makes it impossible, and `AddSectionToTemplateVersion` checks first so that somebody gets an explanation rather than a database error.
- **Immutability lives on the model rather than in the four actions that touch a row.** Creating, moving, retitling and removing all go through one guard that refuses as soon as the template version it belongs to is not a draft. All four are the same change from the point of view of a published template version: what it renders would move.
- **Adding refuses a retired section version.** Retiring is what stops a section version being offered to anything new, and an assembly is the only thing that could offer it. A draft section version is accepted, since that is how somebody assembles a template before publishing anything; the refusal comes later, when the template version is published.
- **`usage_count` on [SectionVersion](015-section-version.md) moves when a row is added or removed,** not when a template version is published. The column is described as how many template versions contain the section version, and a draft contains it just as truly as a published one. This is also what makes the count usable as the guard against deleting a section version out from under a template.

## Open questions

- Whether a section can be marked as shown only when it holds a value, which would keep a long page from filling with empty blocks. It is a rendering rule and could live here as a boolean. Not asked for, and cheap to add later.
