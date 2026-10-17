# Implementation Plan: Paginate long lists

**Branch**: `2026-10-14-paginate-long-lists` | **Date**: 2026-10-14 | **Spec**: [spec.md](spec.md)

## Summary

Twelve lists get numbered pages through one new component, `x-pagination`, over `paginate()`, with a page size field read by one helper (research R1, R2). Four lists read from the top get Load more through `x-list-panel`, which learns to move focus and announce what arrived (R5). The contact page reads repeated values a page at a time through a route of their own while its edit form keeps every row (R6).

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js, Alpine Ajax (`x-target`, `x-merge`, `x-autofocus`). No new package.

**Storage**: none. No migration: the page and the size live in the address.

**Testing**: PHPUnit. View model tests for page size, order and the next page; controller tests for the query parameters and the new route; a component test for `x-pagination`.

**Target Platform**: the web application, desktop to phone, LTR and RTL, light and dark.

**Project Type**: web application (Laravel monolith).

**Performance Goals**: opening a list at its default size reads one page and one count; All reads everything, by choice.

**Constraints**: WCAG 2.2 AA; every configured locale; SQLite, MySQL and PostgreSQL; the API is untouched.

**Scale/Scope**: one component, one helper, one new route and controller, two new view models, about fourteen view models and twelve views touched, about eight strings.

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Nothing is written: no action. View models read and page; the helper only resolves a size and pages a query. |
| II. Simplicity | One helper and one component instead of twelve copies. `ReminderHistory` grows a page argument, not a second class. |
| III. Tested | Every touched view model test proves its page size and its page URL the way `ContactsViewModelTest::it_shows_everybody_exactly_once_across_the_pages` does. New: `PageSizeTest`, `PaginationTest`, `ContactFieldRowsViewModelTest`, `ContactFieldRowControllerTest`. |
| IV. Laravel conventions | `paginate()`, `withQueryString()`, `simplePaginate()`; one named route per line; `page` and `size` clamped, never refused. |
| V. Localized | Every string through `__()` and `monica:localize`; totals through `NumberFormatEnum` (R10). |
| VI. Nothing changes under anybody's feet | Notes change order on the contact page, decided in the spec (FR-007). Nothing stored changes. |
| VII, VIII | Not touched. No data is hidden: every row stays reachable through a page or Load more. |
| IX. Accessible | `nav` with a label, `aria-current="page"`, disabled ends kept on screen, a labelled select, focus after Load more, a live region (R5). |
| X. API | No capability is added: every list in scope is already paginated by the API. |
| XI. Short record | No `data-model.md` (no entity changes) and no `contracts/` (no interface changes). |
| Self hosted equals hosted | Same screens on both. |

Gate: passes. After design it passes again; R11 records the one deviation from an earlier spec.

## Project Structure

```text
app/Helpers/PageSize.php                                             new: CHOICES, ALL, from(), paginate()
resources/views/components/pagination.blade.php                     new: x-pagination
resources/views/components/list-panel.blade.php                     paginator prop, x-autofocus on Load more, live region, end of list line
app/Http/Controllers/App/Vaults/ContactFieldRowController.php       new: index() for one repeater's rows
app/ViewModels/Vaults/ContactFieldRowsViewModel.php                  new: one page of a repeater's rows
routes/web.php                                                       contacts.fieldRows.index
app/ViewModels/Vaults/{Contacts,ContactMerge,Entities,ContactShareLinks,ShareLink,ContactDuplicates}ViewModel.php   paginate() through PageSize
app/ViewModels/Settings/Account/Billing/InvoicesViewModel.php        paginate() through PageSize
app/ViewModels/Admin/{Accounts,AdministratorLogs,ModerationQueue,Administrators}ViewModel.php                      paginate() through PageSize
app/ViewModels/Vaults/ContactViewModel.php                           read side pages repeaters, edit side keeps every row
app/ViewModels/Vaults/{ContactReminders,ContactDeadlinesBlock,ReminderHistory,Reminder}ViewModel.php               history and delivery log paged
app/ViewModels/NotificationBarViewModel.php                          simplePaginate(25)
app/Http/Controllers/App/Vaults/{Contact,ContactMerge,Entity,ContactShareLink,ContactDuplicate,ContactReminder}Controller.php   read page and size
app/Http/Controllers/App/Admin/{Account,AdministratorLog,Moderation,Administrator}Controller.php                        read page and size
app/Http/Controllers/App/Settings/Account/Billing/InvoiceController.php, App/NotificationController.php                read page and size
resources/views/app/vaults/contacts/{index,_section,_notes,reminders/index}.blade.php, merge/new, share-links/{index,show}, entities/index, duplicates/index, reminders/{show,_history}
resources/views/app/admin/{accounts,logs,moderation,administrators}/index.blade.php, settings/account/billing/invoices.blade.php, notifications/_list.blade.php
resources/views/components/field-value.blade.php                     id on the rows wrapper so Load more can append into it
lang/*.json                                                          through monica:localize
tests/Unit/Helpers/PageSizeTest.php, tests/Feature/Views/Components/PaginationTest.php                           new
tests/Unit/ViewModels/Vaults/ContactFieldRowsViewModelTest.php, tests/Feature/Controllers/App/Vaults/ContactFieldRowControllerTest.php   new
tests for every view model and controller above                      updated
```

## Component outline: `x-pagination`

Props: `paginator` (`LengthAwarePaginator`), `size` (`int|string`), `default` (`int`), `target` (`?string`), `id` (`?string`). Renders nothing when `total()` is 0.

1. `<nav aria-label="{{ __('Pagination') }}">`.
2. A summary `<p>`: `__(':first to :last of :total', ...)`, numbers through `NumberFormatEnum`.
3. When `hasPages()`: a `<ul>` of Previous (`rel="prev"`), the window (`aria-current="page"` on the current number, `…` as `aria-hidden`, each link labelled `__('Page :page')`), Next (`rel="next"`). At either end the link is a `<span aria-disabled="true">` that stays.
4. The size form (R3): `x-select` id `page-size`, label `__('Rows per page')`, options `PageSize::CHOICES` then `__('All')`, the current one selected.

Read `x-list-panel`, `x-panel`, `x-select` and `x-link` before writing it. The ends and the window use `focus-ring`.

## Complexity Tracking

| Deviation | Why | Simpler alternative rejected |
| --- | --- | --- |
| `paginate()` on lists spec 001 kept on `simplePaginate()` | Page numbers need the total | Previous and Next only, which the decision of 2026-10-14 ruled out |
| A route and a view model for one field's rows | `contacts.show` already uses `page` and `timelinePage` | Paging inside `contacts.show`, which needs a page parameter per field |
| `request()` read inside `x-pagination` | The size form has to carry the filters it does not own | A `keep` prop filled by every view model, twelve times |
