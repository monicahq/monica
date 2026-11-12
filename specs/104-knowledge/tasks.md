# Tasks: Knowledge

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/api.md](contracts/api.md), [quickstart.md](quickstart.md)
**Tests**: required by constitution principle III, written with each piece, per `.claude/rules/testing.md`. Data from The Office. Mirror the possession maintenance files of 103 for every kind of file.

## Phase 1: Setup

- [X] T001 Amend `.specify/memory/constitution.md` to 2.16.0 (research R13): principle VII lists Knowledge folder, Knowledge note, Knowledge list, Knowledge list column and Knowledge list item (twenty two), one sentence each, and list columns as the second exception beside Possession type, "text only, stored as rows, never as schema"; Sync Impact Report at the top
- [X] T002 [P] Create `spec/models/069-knowledge-folder-in-progress.md` to `073-knowledge-list-item-in-progress.md` (the cell inside 073) from data-model.md, in the shape of 068

## Phase 2: Foundational

- [X] T003 Create the six migrations `database/migrations/2026_10_27_000006` to `000011_create_knowledge_*_table.php` per data-model.md, `up()` only, `foreignIdFor()->constrained()->cascadeOnDelete()`: folders (`name` string(255), `position` unsigned integer), notes (`knowledge_folder_id`, `title` string(255), `body` longText nullable), lists (`knowledge_folder_id`, `title` string(255), `description` text nullable), columns (`knowledge_list_id`, `name` string(255), `position`), items (`knowledge_list_id`, `position`), cells (`knowledge_list_item_id`, `knowledge_list_column_id`, `value` text, unique on the pair, no uuid); every table `account_id`, `vault_id`, timestamps, `uuid` except cells
- [X] T004 Create `app/Models/Knowledge{Folder,Note,List,ListColumn,ListItem,ListCell}.php` (full docblocks, `ResolvesModelInVault` and `#[RouteKey('uuid')]` except the cell, relationships ordered by `position`), their factories in `database/factories/`, and `knowledgeFolders()` on `app/Models/Vault.php`; tests of the orderings in `tests/Unit/Models/`
- [X] T005 [P] Add the eighteen cases of data-model.md to `app/Enums/UserActionEnum.php`, in the voice of the possession cases, naming the title or name
- [X] T006 [P] Create `app/Helpers/KnowledgeMarkdown.php` (research R4, R5): `toHtml()`, `inline()`, `headings()` levels 1 to 3 with their anchors, numbered enabled task checkboxes labelled by their item, `taskLine(int $task)` from the parsed document; `tests/Unit/Helpers/KnowledgeMarkdownTest.php` per quickstart
- [X] T007 [P] Add a `folder` glyph to `resources/views/components/icon.blade.php`, the design's path

## Phase 3: User Story 1, start Knowledge and write a first note (P1) MVP

Independent test: in an empty vault, write a note with a task, save, tick the task, reload.

- [X] T008 [US1] Create `app/Actions/CreateKnowledgeNote.php` (folder optional; creates "General" when the vault has none; "Untitled note" without a title), `UpdateKnowledgeNote.php` (title "string(255) required", folder of the same vault, body "at most 200,000 characters"; blank title keeps the previous), `DestroyKnowledgeNote.php`, `ToggleKnowledgeNoteTask.php` (R5, journals a note update); their tests
- [X] T009 [US1] Add the "Knowledge" entry (icon `journal`, count of the vault's notes and lists) under the existing heading in `resources/views/components/vault/sidebar.blade.php` and `app/View/Components/Vault/Sidebar.php`; `tests/Feature/Views/Components/SidebarTest.php`
- [X] T010 [US1] Create `app/ViewModels/Vaults/KnowledgeFolderTreeBlock.php` (folders by position, files by title in the account's collation, counts, current file) and `KnowledgeViewModel.php` (the empty screen) and `KnowledgeNoteViewModel.php` (title, folder, kind, rendered body or the empty wording, "Edited" timestamp, folder options); their tests
- [X] T011 [US1] Routes in `routes/web.php` after the possession routes, `knowledge.*`: index, notes show, edit, create, update, destroy, tasks update; controllers `app/Http/Controllers/App/Vaults/KnowledgeController.php`, `KnowledgeNoteController.php`, `KnowledgeNoteTaskController.php`, inline validation, `abort_unless` on the vault
- [X] T012 [US1] Views `resources/views/app/vaults/knowledge/index.blade.php` (empty screen of the design, three buttons), `note.blade.php`, `_note-form.blade.php`, `_folders.blade.php` (tree, folding, the plus menu through `x-actions-menu` saying where the file goes) (research R9)
- [X] T013 [US1] `resources/js/knowledge-note-editor.js` registered in `resources/js/app.js`: "Add a link" and Ctrl K insert `[text](address)` at the caret (R6); inline title form, Enter saves, Escape cancels
- [X] T014 [US1] Controller tests `tests/Feature/Controllers/App/Vaults/KnowledgeControllerTest.php`, `KnowledgeNoteControllerTest.php`, `KnowledgeNoteTaskControllerTest.php`: each route answers, a validation error, wrong vault 404

## Phase 4: User Story 2, organise in folders (P1)

Independent test: create Home and Travel, rename one, move it up, delete it moving its files, fail on the last.

- [X] T015 [US2] Create `app/Policies/KnowledgeFolderPolicy.php` (`delete`, refused at the last folder with the screen's wording); `tests/Unit/Policies/KnowledgeFolderPolicyTest.php`, including `nobody_is_signed_in`
- [X] T016 [US2] Create `app/Actions/CreateKnowledgeFolder.php` (name "string(255) required", appended), `UpdateKnowledgeFolder.php`, `UpdateKnowledgeFolderPosition.php` (no gap), `DestroyKnowledgeFolder.php` (asks the policy; moves files to a folder of the same vault or deletes them, journalling how many); their tests
- [X] T017 [US2] Routes, `app/Http/Controllers/App/Vaults/KnowledgeFolderController.php` and `KnowledgeFolderPositionController.php`; controller tests
- [X] T018 [US2] In `_folders.blade.php`: "New folder" name field, folder menu (Rename, double click, Move up, Move down, Delete folder, or the reason it cannot), the deletion card with its destination `<select>`, consequence and "Keep it"; the tree block gives each folder its menu state and consequence wording through `trans_choice()`

## Phase 5: User Story 3, keep a list (P1)

Independent test: start a list, add three items, add a column, remove one with its warning, delete an item.

- [X] T019 [US3] Create `app/Policies/KnowledgeListColumnPolicy.php` (`delete`, refused at the last column); its test
- [X] T020 [US3] Create `app/Actions/CreateKnowledgeList.php` (columns "Name" and "Note", "Untitled list", "General" when no folder), `UpdateKnowledgeList.php` (description "at most 5,000 characters"), `DestroyKnowledgeList.php`; their tests
- [X] T021 [US3] Create `app/Actions/{Create,Update,Destroy}KnowledgeListColumn.php` and `UpdateKnowledgeListColumnPosition.php`, `app/Actions/{Create,Update,Destroy}KnowledgeListItem.php` (cells keyed by a column of the same list, "at most 5,000 characters", blank cells stored as no row, a row with every cell blank refused); their tests
- [X] T022 [US3] Create `app/ViewModels/Vaults/KnowledgeListViewModel.php` (description, columns, items with rendered cells or a dash, item count) and `KnowledgeListColumnsViewModel.php`; their tests
- [X] T023 [US3] Routes and controllers `KnowledgeListController.php`, `KnowledgeListColumnController.php`, `KnowledgeListColumnPositionController.php`, `KnowledgeListItemController.php`; views `list.blade.php`, `_list-form.blade.php`, `_item-row.blade.php`, `list-columns.blade.php` (R7), deletions through `x-delete-action`; controller tests
- [X] T024 [US3] Deleting a note or a list redirects to the index with a flash; both warn that it is final

## Phase 6: User Story 4, find and browse (P2)

Independent test: search "scranon" finds "Scranton" in a cell; Contents opens a note at a heading.

- [X] T025 [US4] Create `app/Helpers/KnowledgeSearch.php` (research R8) and `tests/Unit/Helpers/KnowledgeSearchTest.php` per quickstart
- [X] T026 [US4] `KnowledgeViewModel` Contents (folders, files with kind, item count and last edit, note headings linking to their anchors, the sums through `trans_choice()`) and the search state of the tree block ("Nothing matches" and the words); `index.blade.php` and `_folders.blade.php` render both; tests

## Phase 7: User Story 5, reach Knowledge from outside (P2)

Independent test: create, read, update, list, search and delete each kind through the API and through each tool.

- [X] T027 [US5] `app/Http/Resources/Knowledge{Folder,Note,List}Resource.php` per contracts/api.md
- [X] T028 [US5] `app/Http/Controllers/Api/Knowledge/Knowledge{Folder,Note,List,ListColumn,ListItem,Search}Controller.php` and routes in `routes/api.php`; tests in `tests/Feature/Controllers/Api/Knowledge/`
- [X] T029 [P] [US5] The nineteen MCP tools of contracts/api.md in `app/Mcp/Tools/`, registered on `app/Mcp/Servers/MonicaServer.php`; tests in `tests/Unit/Mcp/Tools/`
- [X] T030 [US5] API reference `resources/docs/api/33-knowledge-folders.php`, `34-knowledge-notes.php`, `35-knowledge-lists.php`, through the `api-docs-writer` skill
- [X] T031 [P] [US5] Portal section `docs/portal/en/11-knowledge/` through the `writer` skill

## Phase 8: Polish

- [X] T032 `php artisan monica:localize`, translate every reported string in every locale, `--check` passes
- [X] T033 `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, `php artisan test --compact`
- [ ] T034 The quickstart's checks by hand at erica.test
- [X] T035 Record the feature in `docs/FEATURES.md` with the `feature-documentator` skill

## Dependencies

Setup, then Foundational, then US1. US2 and US3 need US1's screen and tree. US4 needs US1 and US3. US5 needs the actions of US1 to US3 and the search helper. Polish last.
Parallel: T002 beside T003; T005, T006, T007 together; T029 and T031 beside the API controllers.

## Strategy

MVP is US1 plus US2: notes in folders. Then US3 (lists), US4, US5, Polish. Each phase is a commit.
