# Implementation Plan: Contact list columns

**Branch**: `2026-09-22-contact-lists` | **Date**: 2026-09-22 | **Spec**: [spec.md](./spec.md)

## Summary

People becomes a table whose columns each vault chooses. The choice is a new `contact_list_columns`
table: a template field is named by section and key, so one column spans every template and version
that places the section ([research.md](./research.md) R3). A small `ContactListCells` object reads a
page's values in two queries. Everybody puts favourites first. The headings replace the sort selects,
and 041 still records them. A new screen edits the set in one Alpine component and saves it all at
once. The API reads and writes the set, and returns the column values in the contact list.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js, Alpine Ajax

**Storage**: one new table (see [data-model.md](./data-model.md))

**Testing**: PHPUnit (see [quickstart.md](./quickstart.md))

**Target Platform**: server rendered web application and JSON API

**Performance Goals**: People stays at two added queries per page of 50, whatever the column count

**Constraints**: SQLite, MySQL and PostgreSQL; PHPStan clean; WCAG 2.2 AA on both screens

**Scale/Scope**: two screens, one table, three API endpoints (two new, one extended)

## Constitution Check

| Principle | How this change satisfies it |
| --- | --- |
| I. Actions | `UpdateContactListColumns` holds the write and its refusals. The web and API controllers both call it. The view models only read. |
| II. Simplicity | One table and one enum. The cells reader is its own object only because `ContactsViewModel` is already 339 lines. |
| III. Tested | The test files and what each proves are in [quickstart.md](./quickstart.md). |
| IV. Laravel | A new create migration for a new table, with `up()` only and no `constrained()`. Inline validation. |
| V. Localized, safe | Every heading and label goes through `__()` or `trans_choice()`. A default heading is translated on display (R17). Nothing destructive: Remove and Reset change only the unsaved form. |
| VI. Nothing changes underfoot | The configuration displays values and changes none. A template change never alters a saved column. |
| VII. One personalisation mechanism | Columns read the fields templates already define. No user-defined data is attached another way. The columns are a product-defined preference, like 041. |
| VIII. Nothing disappears silently | A column nobody supplies is kept and marked (R9). |
| IX. Accessible | A native table with `aria-sort`, and reordering by keyboard announced in a live region ([contracts/web.md](./contracts/web.md)). |
| X. API | [contracts/api.md](./contracts/api.md). |
| XI. Short | Six artifacts. `research.md` holds every decision once. |

**Gate**: passes before Phase 0 and after Phase 1. Complexity Tracking stays empty.

## Project Structure

```text
app/
├── Actions/UpdateContactListColumns.php                       # new
├── Enums/ContactListColumnSourceEnum.php                      # new, holds defaults()
├── Http/Controllers/
│   ├── Api/ContactController.php                              # index adds columns
│   ├── Api/ContactListColumnController.php                    # new: show, update
│   └── App/Vaults/ContactListColumnController.php             # new: edit, update
├── Http/Resources/ContactListColumnResource.php               # new
├── Models/ContactListColumn.php                               # new
├── Models/Vault.php                                           # contactListColumns()
└── ViewModels/Vaults/
    ├── ContactsViewModel.php                                  # columns, favourites groups, heading links
    ├── ContactListCells.php                                   # new
    └── ContactListColumnsViewModel.php                        # new
database/
├── factories/ContactListColumnFactory.php                     # new
└── migrations/2026_09_22_000000_create_contact_list_columns_table.php
resources/
├── docs/api/06-contacts.php
├── js/app.js, js/contact-list-columns.js                      # register, new
└── views/app/vaults/
    ├── contacts/index.blade.php, contacts/_groups.blade.php   # the table
    └── contact-list-columns/edit.blade.php                    # new
routes/web.php, routes/api.php
tests/ (listed in quickstart.md)
```

**Structure Decision**: every file lands in a folder the constitution already names.

## Risks worth a reviewer's eye

- Removing the letter headings and the sort selects changes existing feature tests. They describe the
  old screen and are rewritten, not worked around.
- `FieldValueFormatter` gives one string per value. A multiselect or an address is shown as its
  formatted line, which may be long, and the cell truncates it.
- The favourites-first order adds `is_favourite` to the ordering only in Everybody. `sortedBy` stays
  as it is, and the view model prepends the column.
