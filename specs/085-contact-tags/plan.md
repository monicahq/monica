# Implementation Plan: Tag contacts with categories

**Branch**: `085-contact-tags` | **Date**: 2026-10-29 | **Spec**: [spec.md](spec.md)

## Summary

Categories stay the values of every Multiselect field over the account's "Contact categories" list.
One new action matches a wording to an item, or creates it, and every path uses it: the contact's save,
CardDAV and the Monica 4 import ([R1](research.md)). The Multiselect field type gains an inline offer
that creates on save only ([R2](research.md)). The list of people gains a `categories[]` filter over
the JSON column ([R3](research.md)). `CATEGORIES` becomes a managed vCard property ([R4](research.md)).
Monica 4 tags become categories, recorded in the import's ledger for the rollback ([R5](research.md)).
The API gains a section save, the filter and the list of offered categories ([R6](research.md),
[contracts/api.md](contracts/api.md)). No migration and no flag.

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel 13, Blade, Alpine.js |
| Storage | Existing `field_values.value_list_item_ids` JSON and `account_import_items.payload`; no schema change |
| Testing | PHPUnit, by layer as `.claude/rules/testing.md` assigns; scenarios in [quickstart.md](quickstart.md) |
| Performance | SC-002 on 1,000 contacts, no index (R3) |
| Scale | 1 new action, 1 exception, 2 API controllers, about 12 files edited |

No open question remains. `data-model.md` is omitted: no entity, column or state is added, and the one
new ledger key is in R5.

## Constitution Check

| Principle | How it holds |
| --- | --- |
| I. Actions | Matching and creating live in `FindOrCreateValueListItem`; controllers only validate and catch |
| II. Simplicity | One shared action for three callers; no pivot, no new concept |
| III. Tested | Action, view model, controller and API tests per the table below |
| IV. Laravel | `whereJsonContains`, inline validation, one route per line, camelCase names |
| V. Localized, safe | New copy goes through `monica:localize`; the stopped-category refusal names the way back |
| VI. Frozen versions | No structure is touched; values point at list items as before |
| VII. One mechanism | Categories are the Category field's values; no tag concept (spec Clarifications) |
| VIII. Nothing silent | A stopped category is refused and explained, never reactivated; a rollback keeps an item still used |
| IX. Accessible | The offer is built once into the Multiselect type: labelled input, Enter adds, a live region announces the new chip |
| X. API | Section save, filter, offered categories and MCP `ListContacts` (R6) |
| XI. Short record | spec 120, plan, research under 80, contracts and quickstart under 60 |

A deliberate departure: the `labelOf()` docblock forbids a device writing to an account's list, and
R4 lets CardDAV create a category, as the spec's clarification decided. The docblock is amended.

## Project Structure

```text
specs/085-contact-tags/
├── spec.md, plan.md, research.md, quickstart.md
├── contracts/api.md
└── checklists/requirements.md

app/Actions/FindOrCreateValueListItem.php          # new (R1)
app/Exceptions/ValueListItemIsStopped.php          # new (R1)
app/Actions/SetFieldValues.php                     # newChoices (R2)
app/Helpers/FieldValueRules.php                    # new_choices rules (R2)
app/Http/Requests/SaveFieldValuesRequest.php, app/Http/Controllers/App/Vaults/ContactSectionController.php
resources/views/components/field-input.blade.php   # the offer (R2)
app/Models/Contact.php, app/Models/Vault.php      # the filter scope, the offered categories (R3)
app/ViewModels/Vaults/ContactsViewModel.php, app/Http/Controllers/App/Vaults/ContactController.php
resources/views/app/vaults/contacts/index.blade.php   # filter form (R3)
app/Http/Dav/OwnedFields.php, app/Http/Dav/VCardMerger.php, app/Actions/ImportVCard.php   # R4
app/Actions/WritesMonicaFourItems.php, app/Actions/RollBackAccountImport.php              # R5
app/Http/Controllers/Api/ContactSectionController.php      # new (R6)
app/Http/Controllers/Api/ContactCategoryController.php     # new (R6)
app/Http/Controllers/Api/ContactController.php, app/Http/Resources/ContactResource.php
app/Mcp/Tools/ListContacts.php, routes/api.php, lang/*.json
```

**Structure Decision**: the existing Laravel tree. Nothing outside `app`, `resources/views`, `routes`,
`lang` and `tests`.

## Tests by layer

| Layer | Proves |
| --- | --- |
| `FindOrCreateValueListItemTest` | Case and spaces, stopped refused unless held, `addItem` refused, journal, other account |
| `SetFieldValuesTest` | New wordings join the choice; a refusal writes nothing |
| `ContactTest` (scope), `ContactsViewModelTest` | Any-of matching, grouping combined, offered filters and counts |
| `ImportVCardTest`, `VCardMergerTest` | `CATEGORIES` both ways, first field, skip refused, no Category field |
| `WriteAccountImportItemTest`, `RollBackAccountImportTest` | Tags as categories, note fallback, rollback keeps used items |
| Web and API controller tests | Validation of `new_choices` and `categories[]`, one refusal as error and 422, wrong vault |

## Order

1. R1 and its test, then R2 with the field type (US1 shippable).
2. R3: scope, view model, screen (US2).
3. R4 (US3), then R5 (US4).
4. R6 and the API docs (US5), then `monica:localize`, the suite, PHPStan, Pint and the quickstart.
