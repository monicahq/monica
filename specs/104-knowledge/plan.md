# Implementation Plan: Knowledge

**Branch**: `2026-10-25-location-management` | **Date**: 2026-10-27 | **Spec**: [spec.md](spec.md)

## Summary

Six vault scoped tables (folders, notes, lists, columns, items, cells), eighteen actions, two policies,
one screen family beside the vault's sidebar, a Markdown helper and a search helper both screens and
API share, API endpoints, assistant tools and their documentation. Decisions in
[research.md](research.md), shapes in [data-model.md](data-model.md), the API in
[contracts/api.md](contracts/api.md), checks in [quickstart.md](quickstart.md).

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel (current major), Blade, Tailwind, Alpine.js, Alpine Ajax |
| Dependencies | `league/commonmark`, already installed; nothing added |
| Storage | Six new tables; nothing existing changes |
| Testing | PHPUnit: helpers, models, policies, actions, view models, web and API controllers, MCP tools |
| Scale | 1,000 notes and lists per vault, 500 items per list, 200,000 characters per note |
| Constraints | SQLite, MySQL, PostgreSQL; one new JavaScript file (R6); WCAG 2.2 AA; every wording translated |

No unknown is left: links and list columns were settled with the user on 2026-10-27.

## Constitution Check

| Principle | Status |
| --- | --- |
| I Actions | Pass. Eighteen actions; web, API and MCP share them. Search is a read, in a helper (R8) |
| II Simplicity | Pass. Two helpers each used by three surfaces; no flag |
| III Tested | Pass. Every layer in quickstart |
| IV Laravel | Pass. New migrations only, `up()` only, `foreignIdFor()->constrained()` |
| V Localized | Pass. Counts through `trans_choice()`; every deletion warns; user text never translated |
| VI Nothing changes | Pass. Nothing existing is rewritten; moving a file changes only its folder |
| VII Stable core | **Amendment**. Five fixed concepts and a second exception for list columns (R13) |
| VIII Nothing disappears | Pass. A folder's deletion says where its files go; a column's says its cells go |
| IX Accessible | Pass. Menus through `x-actions-menu`; refused controls stay with their reason (`policies.md`) |
| X API | Pass. Endpoints and tools in the contract; no webhook exists yet to emit |
| XI Short record | Pass. spec 120/120, the others within caps |
| Security | Pass. Markdown escaped, unsafe links refused (R4); every record found inside its vault |

Re-checked after design: unchanged.

## Project Structure

```text
.specify/memory/constitution.md; spec/models/069 to 073 (new, -in-progress)
database/migrations/2026_10_27_000006 to 000011_create_knowledge_*_table.php; database/factories/Knowledge*Factory.php
app/Models/Knowledge{Folder,Note,List,ListColumn,ListItem,ListCell}.php   new; Vault: knowledgeFolders()
app/Enums/UserActionEnum.php                                   eighteen cases (data-model.md)
app/Policies/KnowledgeFolderPolicy.php, KnowledgeListColumnPolicy.php   new
app/Helpers/KnowledgeMarkdown.php, KnowledgeSearch.php         new
app/Actions/{Create,Update,Destroy}KnowledgeFolder.php, UpdateKnowledgeFolderPosition.php
app/Actions/{Create,Update,Destroy}KnowledgeNote.php, ToggleKnowledgeNoteTask.php
app/Actions/{Create,Update,Destroy}KnowledgeList.php
app/Actions/{Create,Update,Destroy}KnowledgeListColumn.php, UpdateKnowledgeListColumnPosition.php
app/Actions/{Create,Update,Destroy}KnowledgeListItem.php
app/Http/Controllers/App/Vaults/Knowledge{,Folder,FolderPosition,Note,NoteTask,List,ListColumn,ListColumnPosition,ListItem}Controller.php
app/Http/Controllers/Api/Knowledge/Knowledge{Folder,Note,List,ListColumn,ListItem,Search}Controller.php
app/Http/Resources/Knowledge{Folder,Note,List}Resource.php
app/Mcp/Tools/*Knowledge*.php (nineteen, contract); app/Mcp/Servers/MonicaServer.php
app/ViewModels/Vaults/Knowledge{,Note,List,ListColumns}ViewModel.php, KnowledgeFolderTreeBlock.php
app/View/Components/Vault/Sidebar.php; resources/views/components/vault/sidebar.blade.php, icon.blade.php (folder)
resources/views/app/vaults/knowledge/{index,note,list,list-columns}.blade.php
resources/views/app/vaults/knowledge/{_folders,_note-form,_list-form,_item-row}.blade.php
resources/js/knowledge-note-editor.js; resources/js/app.js (registers it)
routes/web.php, routes/api.php; resources/docs/api/33 to 35-knowledge-*.php
docs/portal/en/11-knowledge/*.md; docs/FEATURES.md; lang/*.json
```

## Order of work

1. Constitution amendment, `spec/models` 069 to 073.
2. Migrations, models, factories, journal cases; model tests.
3. `KnowledgeMarkdown` and `KnowledgeSearch` with their tests.
4. Policies; folder actions, then note actions, then list, column and item actions; their tests.
5. Sidebar entry and `folder` glyph; view models and the folder tree block.
6. Web routes, controllers and views: empty screen and Contents, note read and edit with the editor
   script, list, columns, items, folder menus and their deletion card, search.
7. API controllers, resources, routes and reference; MCP tools; their tests.
8. Portal section, FEATURES.md, translations, PHPStan, Pint, whole suite; the screen by hand as
   quickstart says.

## Complexity Tracking

| Item | Why |
| --- | --- |
| `account_id` and `vault_id` on every table, cells included | `ResolvesModelInVault`, `RouteProtectionTest` and one-query search scoping (R1, R8) |
| A cells table rather than a JSON column | portable search and column removal across three databases (R1) |
| One new JavaScript file | inserting at the caret of a textarea cannot be done from markup (R6) |
| Nineteen MCP tools | one per action a person can take, as the possession tools do |
