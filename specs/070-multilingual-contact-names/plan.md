# Implementation Plan: Multilingual contact names

**Branch**: `2026-10-06-multilingual-contact-names` | **Date**: 2026-10-06 | **Spec**: [spec.md](spec.md)

## Summary

A new `contact_name_representations` table holds every way to write the current name; the contact
points at the displayed one. The six name columns on `contacts` stay, plus `formatted_name`, as a
projection only the model writes, so every list and view model keeps reading them without a join.
One action replaces a contact's whole set and serves the screen, the API, MCP and CardDAV. Decisions
are in [research.md](research.md), the schema in [data-model.md](data-model.md), the interfaces in
[contracts/api.md](contracts/api.md) and [contracts/vcard.md](contracts/vcard.md).

## Technical Context

**Language/Version**: PHP 8.4, current Laravel. **Primary Dependencies**: none added; sabre/vobject 4.6, sabre/dav 4.7, ICU through `intl`.
**Storage**: three new migrations (R12); no shipped migration edited, the product being in production.
**Testing**: PHPUnit. **Project Type**: web application with JSON API, CardDAV and MCP.
**Constraints**: SQLite, MySQL and PostgreSQL alike; no query per contact on a list (SC-005); a single `N` never deletes (SC-004).
**Scale/Scope**: 1 table, 1 model, 1 enum, 1 new action, 8 actions changed, 1 job, 3 Dav classes, 5 controllers, 3 view models, 1 component, 2 MCP tools.

## Constitution Check

| Principle | Result |
| --- | --- |
| I. Actions | Pass. The set, its display invariant and the reconciliation live in `UpdateContactNameRepresentations`; controllers, MCP and `ImportVCard` call it. |
| II. Simplicity | Pass. Projection instead of rewriting 30 readers (R1); one set action instead of three (R2). |
| III. Tested | Pass, see Tests. |
| IV. Laravel | Pass. New migrations only; `foreignIdFor()->constrained()`; enum named `ContactNameKindEnum`. |
| V. Localised | New copy through `monica:localize`; language and script names from ICU in the reader's locale; name order already per contact. |
| VI. Nothing changes under feet | Pass. The backfill changes no visible name; the display choice moves only on an explicit act or the import rule. |
| VII. Stable core | Pass. Representations are part of the fixed structured name, never a template field. |
| VIII. Nothing disappears silently | Pass. Single `N` cards update only; removal warns; merge keeps duplicates on the absorbed contact for undo. |
| IX. Accessible | Native radios for the display choice, `aria-expanded` on Name parts, errors tied by `aria-describedby`, keyboard only path through add, edit, remove and switch. |
| X. API | Pass. Two endpoints, two MCP tools, the contact object extended. |
| XI. Short record | Spec 120, plan, research 80, data model 60, contracts 59 and 37. `quickstart.md` omitted: the Tests table is the validation guide. |
| Scoping | Every write names its contact through route binding (`ResolvesModelInVault`) and its rows by uuid within that contact; a foreign uuid is a 422. |

Gate passes. Re-checked after design: unchanged.

## Changes

| Area | Files |
| --- | --- |
| Schema | `database/migrations/2026_10_06_000000_create_contact_name_representations_table.php`, `…000001_add_display_name_to_contacts_table.php`, `…000002_backfill_contact_name_representations.php` |
| Model | `app/Models/ContactNameRepresentation.php` and factory; `app/Enums/ContactNameKindEnum.php`; `app/Models/Contact.php`: relations, `nameFor()`, projection in `saving`, name columns out of `#[Fillable]`, `formatted_name` fallbacks (R9), `matchingSearch` (R3), `searchableName()` and `searchableLinkedNames()` |
| Factories | `ContactFactory` writes a display representation from its parts in `afterCreating`, so existing tests keep their names |
| Actions | New `UpdateContactNameRepresentations`; `UpdateContactName`, `CreateContact` (R2); `PreviewContactMerge`, `MergeContacts`, `UndoContactMerge` (R10); `CreateRecordSnapshot` (R11); `ImportVCard` (R6) |
| Job | `DetectDuplicateContacts` blocks and compares per representation (R4) |
| Dav | `VCardReader`, `VCardMerger` (R5, R6); new `app/Http/Dav/CardDavPlugin.php` overriding `convertVCard()` (R7), registered in `DavServiceProvider` |
| Web | `App/Vaults/ContactNameController@update`, `ContactController@create`, `ContactMergeController`; `ContactEditViewModel`, `ContactFormViewModel`, `ContactMergeViewModel`; `contacts/edit.blade.php`, `contacts/form.blade.php`, `contacts/merge/new.blade.php` |
| Component | `resources/views/components/contact-name-editor.blade.php` and `resources/js/contact-name-editor.js`: used by edit and create, extracted at the second file for its keyboard and ARIA contract (components rule) |
| API and MCP | `Api/ContactNameRepresentationController` (index, update), two routes in `routes/api.php`; `ContactResource`, `ContactMergePreviewResource`, `Api/ContactMergeController`; `app/Mcp/Tools/ListContactNameRepresentations.php`, `UpdateContactNameRepresentations.php`, registered in `EricaServer`; `resources/docs/api/06-contacts.php` |
| Product spec | New `spec/models/057-contact-name-representation-done.md`; `019-contact-done.md` and `034-contact-merge-done.md` point at it |
| Copy | `lang/*.json` through `monica:localize` only |

## Tests

| Test file | Proves |
| --- | --- |
| `tests/Unit/Models/ContactNameRepresentationTest.php` | keys built on save; `displayText()` in each order |
| `tests/Unit/Models/ContactTest.php` | projection follows the display row only; formatted name fallbacks for name, initials, sort and slug; secondary change keeps slug and sort; `matchingSearch` finds every representation; `nameFor()` |
| `tests/Unit/Actions/UpdateContactNameRepresentationsTest.php` | create, update, delete in one set; first row displayed; display must be in the set; empty row and phonetic without system refused; foreign uuid refused; another account not found; logged; duplicates detection dispatched |
| `tests/Unit/Actions/UpdateContactNameTest.php`, `CreateContactTest.php` | parts land on the display representation; none creates one |
| `tests/Unit/Actions/MergeContactsTest.php`, `UndoContactMergeTest.php`, `PreviewContactMergeTest.php` | display conflict offered and applied; duplicates left behind; undo restores rows and pointer |
| `tests/Unit/Actions/CreateRecordSnapshotTest.php` | payload holds every representation and the display uuid |
| `tests/Unit/Actions/ImportVCardTest.php`, `tests/Unit/Http/Dav/VCardReaderTest.php`, `VCardMergerTest.php` | contract table rows; round trip of the fixture is identical (SC-001); single `N` deletes nothing (SC-004) |
| `tests/Unit/Http/Dav/CardDavPluginTest.php` | 3.0 output carries one pair without `ALTID` or `PROP-ID` |
| `tests/Unit/Jobs/DetectDuplicateContactsTest.php` | a shared transliteration is found; a shared pronunciation is suggested |
| `tests/Unit/Migrations/BackfillContactNameRepresentationsTest.php` | every named contact gets one displayed written row and an unchanged name; nameless contacts get none |
| `tests/Feature/Controllers/Api/ContactNameRepresentationControllerTest.php` | shape, validation, wrong vault, 422 on foreign uuid |
| `tests/Feature/Controllers/App/Vaults/ContactNameControllerTest.php`, `ContactControllerTest.php` | form posts the set; error lands on `names.{i}.{key}`; wrong vault |
| `tests/Unit/Mcp/Tools/*NameRepresentations*Test.php` | both tools mirror the API |
| `tests/Feature/Views/Components/ContactNameEditorTest.php` | one name shows the parts and the add button; several show a radio group named for the display choice |
| `tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php` | a page of contacts resolves names with a constant number of queries (SC-005) |

The whole suite runs: the factory and the projection touch every test that names a contact.

## Complexity Tracking

None.
