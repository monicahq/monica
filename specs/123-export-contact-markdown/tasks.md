# Tasks: Export a contact as Markdown

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/contact-markdown.md](contracts/contact-markdown.md), [quickstart.md](quickstart.md)

**Tests**: required by Constitution III. **Format**: `[ID] [P?] [Story] Description`.

## Phase 1: Setup

- [X] T001 Stay on `2026-11-12-knowledge-dummy-data` (standing instruction); confirm the tree is clean

## Phase 2: Foundational

- [X] T002 Add `ContactExportedAsMarkdown` and its sentence to `app/Enums/UserActionEnum.php`, beside `ContactExported`, and to every map in that enum that lists `ContactExported`
- [X] T003 [P] Write `tests/Unit/ViewModels/Vaults/ContactMarkdownViewModelTest.php`: name heading and names, fields by page and section, repeater rows, relationships, life events, groups, reminders, loans, empty parts left out, sensitive values kept and left out, block markers neutralised, file name
- [X] T004 Create `app/ViewModels/Vaults/ContactMarkdownViewModel.php` with `document()` and `fileName()`, per the contract and research R1 to R3
- [X] T005 [P] Write `tests/Unit/Actions/ExportContactMarkdownTest.php`: returns the document, honours the sensitive flag, journals on `low`, refuses another account
- [X] T006 Create `app/Actions/ExportContactMarkdown.php`

## Phase 3: User Story 1 (P1) MVP: download and copy from the contact screen

- [X] T007 [P] [US1] Write `tests/Feature/Controllers/App/Vaults/ContactMarkdownControllerTest.php`: download with type and file name, sensitive value present, wrong vault 404
- [X] T008 [US1] Declare `contacts.markdown.show` in `routes/web.php` beside `contacts.vcard.show`, above the catch-all
- [X] T009 [US1] Create `app/Http/Controllers/App/Vaults/ContactMarkdownController.php`
- [X] T010 [US1] Add `markdownUrl()` to `app/ViewModels/Vaults/ContactViewModel.php`
- [X] T011 [US1] Create `resources/js/contact-markdown-copy.js` (research R4) and register it in `resources/js/app.js`
- [X] T012 [US1] Add **Export as Markdown** and **Copy as Markdown** to the actions menu in `resources/views/app/vaults/contacts/show.blade.php`, with the live region
- [X] T013 [US1] Extend `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php`: the screen offers the export route and the copy hook

## Phase 4: User Story 2 (P2): API and MCP

- [X] T014 [P] [US2] Write `tests/Feature/Controllers/Api/ContactMarkdownControllerTest.php`: body and headers, scope with and without, other account 404, body equals the web download
- [X] T015 [US2] Declare `contacts.markdown.show` in `routes/api.php` beside the vCard route
- [X] T016 [US2] Create `app/Http/Controllers/Api/ContactMarkdownController.php`
- [X] T017 [P] [US2] Write `tests/Unit/Mcp/Tools/GetContactMarkdownTest.php`: answers the document, sensitive left out for a key without the scope, unknown contact refused
- [X] T018 [US2] Create `app/Mcp/Tools/GetContactMarkdown.php` and register it in `app/Mcp/Servers/MonicaServer.php`

## Phase 5: User Story 3 (P3): documentation

- [X] T019 [P] [US3] Document the endpoint in `resources/docs/api/06-contacts.php` after `contacts-vcard`, with `responseMediaType` `text/markdown`
- [X] T020 [P] [US3] Add an `Added` entry to `resources/docs/api-changelog/2026-11-13.php`
- [X] T021 [P] [US3] List `get-contact-markdown` in `app/ViewModels/Docs/McpDocsViewModel.php` under "Contacts, reading"
- [X] T022 [P] [US3] Write the help centre page `export.markdown` in `docs/portal/en/8-sharing-export-and-sync/` and mention it in `1-introduction.md`
- [X] T023 [P] [US3] Record the feature in `docs/FEATURES.md`

## Phase 6: Polish

- [X] T024 Run `php artisan monica:localize`, translate the new strings in every locale with Sonnet subagents, then `--check`
- [X] T025 Run the quickstart tests, the MCP and docs suites, then the whole suite
- [X] T026 Run `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`

## Dependencies

T002 → T004 → T006 → US1, US2. US3 needs US2's route. T024 after every string is written. Inside a phase, `[P]` tasks run in parallel.

**MVP**: Phases 2 and 3.
