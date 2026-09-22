# Implementation Plan: Contact list view preference

**Branch**: `2026-09-22-contact-list-view-preference` | **Date**: 2026-09-22 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/041-contact-list-view-preference/spec.md`

## Summary

The People list keeps the grouping, sort field and sort direction somebody last looked at, in three
nullable columns on `users`, and opens on them next time. Every grouping gains an address of its own,
including Everybody, whose value is already `all`; the bare address becomes a redirect to the
remembered one, so the address always states what is on screen. Recording is an action called from the
controller, guarded against prefetch requests. The preference is read on `GET me` and written with
`PUT me/contact-list-view`, following `MeTimezoneController`.

## Technical Context

**Language/Version**: PHP 8.4.23

**Primary Dependencies**: Laravel 13.32, Blade, Tailwind CSS, Alpine.js, Alpine Ajax, Turbo,
instant.page 5.2

**Storage**: relational, through Eloquent. Three columns added to the existing `users` table. No new
table.

**Testing**: PHPUnit. `Tests\Unit` mirrors the application namespace, `Tests\Feature\Controllers`
holds the two controllers.

**Target Platform**: server rendered web application, desktop, tablet and phone.

**Project Type**: Laravel web application with a JSON API alongside it.

**Performance Goals**: no measurable change to the People list. The recording write is skipped when
nothing differs, so paging through a long list writes once and not once per page.

**Constraints**: the suite passes on SQLite, MySQL and PostgreSQL, so no driver specific SQL. PHPStan
level 5 over `app/`, `routes/` and `database/`. WCAG 2.2 AA on the screen touched.

**Scale/Scope**: one screen, one JSON endpoint, one new enum, one new action, one new controller.
Twelve application files and six test files.

## Constitution Check

*GATE: passed before Phase 0, re-checked after Phase 1.*

| Principle | How this change satisfies it |
| --- | --- |
| I. Actions hold the business logic | `UpdateContactListView` holds the write. `ContactsViewModel` keeps reading only, and the recording is called from the controller rather than from the view model, which may not write. |
| II. Simplicity over abstraction | Three columns, one action, no new table and no preference framework. `SortDirectionEnum` is the one new abstraction, and it replaces a literal that already stood in five places. |
| III. Tested before merged | Seven test files listed in [quickstart.md](./quickstart.md), covering the enum, the action, the view model, both controllers, the reserved words and the model casts. |
| IV. Laravel conventions | `Request::prefetch()` is the framework's own method. Route model binding, `whereIn` and `Rule::enum` are used as documented. The three columns are not enum cast, and [data-model.md](./data-model.md) says why. Only `up()` is written, and the existing users migration is edited rather than a new one added, which is what the migrations skill requires before production. |
| V. Localized and safe | No new user-facing string: the four chip labels and both select labels already exist. `php artisan monica:localize` is still run, and is expected to report nothing. Nothing destructive is added. |
| VI. Nothing changes under anybody's feet | The preference records what somebody looked at and changes nothing already fixed. A prefetch does not record, so hovering a link cannot change what the next visit shows. |
| VII. Stable core, unlimited personalisation | This is a product defined preference in the same family as `locale`, `time_format` and `name_order`, not user invented data, so columns are the right shape and principle VII's ban on schema changes for personalisation does not bite. |
| VIII. Nothing disappears silently | A recorded value the product no longer recognises falls back per choice rather than failing, and the other two choices survive. This is why the columns are read through the enum helpers rather than cast. |
| IX. Accessible to everybody | The chips already carry `aria-current`. The set grows by one working chip (Names only, which is on the screen today and leads nowhere), and no control is added or hidden. |
| X. Everything goes through the API | `GET me` carries the three attributes, `PUT me/contact-list-view` writes them, both documented in the portal. |
| XI. The written record is short | Five artifacts, each deciding one thing and linking rather than restating. |

**Gate result before Phase 0**: no violations.

**Re-checked after Phase 1**: still none, with two design decisions worth a reviewer's eye because they
depart from what the code beside them does, and both are argued in the artifacts rather than assumed.

- The three columns are **not** enum cast, unlike the six preferences beside them. An enum cast throws
  on a value that is no longer a case, which is the case FR-012 says must fall back. Argued in
  [data-model.md](./data-model.md).
- The controller resolves the three choices itself, in three lines, rather than through a class. This
  is input resolution of the same kind the controller already does with `$request->string('sort', ...)`,
  and principle II says not to hide a few short lines behind a name. The write it leads to is in the
  action, which is what principle I governs.

The Complexity Tracking table stays empty.

## Project Structure

### Documentation (this feature)

```text
specs/041-contact-list-view-preference/
├── plan.md              # This file
├── spec.md              # What the feature is
├── research.md          # Phase 0: the decisions and what was rejected
├── data-model.md        # Phase 1: the three columns and the enums
├── quickstart.md        # Phase 1: how to prove it works
├── contracts/
│   ├── web-addresses.md # The People list addresses and the redirect
│   └── api.md           # GET me and PUT me/contact-list-view
└── tasks.md             # Written by /speckit-tasks, not by this command
```

### Source Code (repository root)

```text
app/
├── Actions/
│   └── UpdateContactListView.php              # new
├── Enums/
│   ├── ContactGroupingEnum.php                # unchanged
│   ├── ContactSortEnum.php                    # unchanged
│   └── SortDirectionEnum.php                  # new
├── Helpers/
│   └── ReservedAddressWords.php               # `all` joins the contact words
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   └── MeContactListViewController.php    # new
│   │   └── App/Vaults/
│   │       └── ContactController.php              # index redirects and records
│   └── Resources/
│       └── UserResource.php                   # three attributes added
├── Mcp/Tools/
│   └── ListContacts.php                       # follows the direction enum
├── Models/
│   ├── Contact.php                            # scopeSortedBy takes the enum
│   └── User.php                               # three docblock properties, nothing else
└── ViewModels/Vaults/
    ├── ContactsViewModel.php                  # takes enums, always states the grouping
    └── RelationshipsBlock.php                 # unchanged, its link now resolves

database/migrations/
└── 0001_01_01_000000_create_users_table.php   # three columns added in place

resources/docs/api/
└── 03-profile.php                             # the attributes and the endpoint

routes/
├── api.php                                    # PUT me/contact-list-view
└── web.php                                    # whereIn over all four groupings

tests/
├── Feature/Controllers/
│   ├── Api/MeContactListViewControllerTest.php        # new
│   └── App/Vaults/ContactControllerTest.php           # extended
└── Unit/
    ├── Actions/UpdateContactListViewTest.php          # new
    ├── Enums/SortDirectionEnumTest.php                # new
    ├── Helpers/ReservedAddressWordsTest.php           # extended
    └── ViewModels/Vaults/ContactsViewModelTest.php    # extended
```

**Structure Decision**: no new folder and no new top-level namespace, so no amendment is owed. Every
file lands in a directory the constitution already enumerates.

## Complexity Tracking

No constitution violation to justify.
