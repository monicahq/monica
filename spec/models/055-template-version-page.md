# 055. TemplateVersionPage

**Status:** to create
**Spec:** 5.2, 6, 3.1, 3.5

## What it is

One named and ordered page inside a template version. It is the missing layer between the complete template version and the reusable sections placed inside it.

A page is deliberately not a stable object beside [Template](012-template.md) or [Section](014-section.md). It cannot be reused or published on its own. Its identity, label and position describe one precise template version, and publication freezes all of them. A new draft copies the pages so the published version and the draft never share mutable rows.

This model keeps number 055 because it was added after the established sequence through model 054 had already been written and partly implemented. Renumbering those models would make existing documentation, code reviews and implementation history misleading. [TemplateVersionSection](016-template-version-section.md) therefore documents the one forward dependency this addition creates.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the API
account_id                  int                             account the page belongs to
template_version_id         int                             exact template version containing the page
key                         string (64)                     stable identifier within the template, used in record URLs and copied into later drafts
label                       string (255)                    label shown in the tab, null when it falls back to its translation key
label_translation_key       string (255)                    translation key of a supplied label
position                    int                             order the page appears in the template version
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md)
- `belongsTo` [TemplateVersion](013-template-version.md)
- `hasMany` [TemplateVersionSection](016-template-version-section.md)

## Rules

- Every template version has at least one page. Creating a template creates its first draft and a page labelled "General" in the same transaction. The supplied label uses a translation key and can be renamed like any other page.
- A page belongs to exactly one template version and is never reused across versions. Drafting from a version copies each page and preserves its key, label and position while assigning a new UUID.
- `key` is unique within a template version and stays stable across drafts copied from it. Renaming the label never changes the key. This is what makes a page URL readable and durable while a record remains on its precise template version.
- Every page has a visible label through either `label` or `label_translation_key`. Display labels are unique within a template version so two tabs never have the same accessible name.
- `position` is unique within a template version. Reordering pages normalises the positions into one contiguous sequence.
- A published template version freezes its pages. A page can be added, renamed, reordered or removed only while its template version is a draft.
- The last page cannot be removed. A page containing section placements cannot be removed until those sections have been moved or removed explicitly.
- A draft page may be empty while the template is being assembled. A version with several pages cannot be published while any of them is empty. A one page template may be published without a section because a record whose only custom content is its imposed name remains valid.
- There is no product limit above one page. The builder and record navigation must continue to work when the tab list is wider than the viewport.
- A page stores presentation and navigation only. Fields and their values continue to belong to section versions. Search, filtering, reminders, exports and highlighted fields work across all pages.
- A page is not a marketplace item. When a template is published or installed, its page structure travels inside the template payload. Publishing a section alone carries no page with it.
- The supplied contact template starts with the same translated "General" page. The record view hides the tab list while only that page exists, but the template builder shows it so another page can be added.

## Interface contract

- A record with two or more pages renders them as the tabs defined in section 5.2. Page order is tab order.
- Opening a record without a page key selects the first page. Opening it with a valid key selects that page. An unknown key falls back to the first page without producing an error page.
- Selecting a tab updates the URL and browser history without reloading the whole record.
- On a narrow screen the tab list scrolls horizontally, never wraps, and keeps the selected tab visible.
- When validation fails on a hidden page, the interface selects that page before focusing and announcing the first invalid field.

## Implementation order

1. Create this table and give every existing template version one "General" page. This migration preserves the rendered order of every existing section and does not change what any published record shows.
2. Add `template_version_page_id` to [TemplateVersionSection](016-template-version-section.md) as nullable, assign every existing placement to its version's new page, then make the foreign key required. The migration refuses completion if any placement and page belong to different template versions.
3. Update template creation, drafting, duplication, default seeding, publication and marketplace installation so they create or copy pages and their section placements atomically.
4. Expose page creation, rename, reorder and removal, plus moving a section between pages, through the web interface, API and MCP. These operations all use the same actions and enforce draft immutability in one place.
5. Update record rendering, editing, snapshots, private shares and marketplace payloads to read the page hierarchy and the navigation rules above.

## Open questions

- Whether a template builder should allow an icon alongside a page label. A label is sufficient for the navigation and accessibility contract, so no icon is stored until there is a demonstrated need.
