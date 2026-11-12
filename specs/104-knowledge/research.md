# Research: Knowledge

## R1 Storage
**Decision**: six tables carrying `account_id` and `vault_id`, all but cells with a uuid route key and
`ResolvesModelInVault`: `knowledge_folders`, `knowledge_notes`, `knowledge_lists`,
`knowledge_list_columns`, `knowledge_list_items`, `knowledge_list_cells`. Shapes in [data-model.md](data-model.md).
**Rationale**: as 103 R1, the account and the vault scope every address. A cell is a row, so removing a
column deletes its cells by cascade, and search reads text the same way on the three databases.
**Rejected**: one `knowledge_files` table with a type (half its columns empty); cells as JSON on the
item (column removal and search differ per database).

## R2 Folders
**Decision**: flat, with a `position`; a new folder goes last, and the folder menu offers "Move up" and
"Move down". Starting a note or a list in a vault without folders creates "General" in the same action.
Deleting takes a destination folder or deletes the files with it.
**Rejected**: dragging (needs `x-sortable-row`'s list markup, which a folding tree is not).
Notes and lists of a folder read together by title in the account's collation; items by
`position`, appended, reordering them left for later.

## R4 Markdown
**Decision**: `App\Helpers\KnowledgeMarkdown` on `league/commonmark` (already used by the portal):
`html_input: escape`, `allow_unsafe_links: false`, CommonMark core, GFM (autolinks, task lists),
external links in a new tab with `noopener noreferrer`, an id on each heading. It also answers
`headings()` (levels 1 to 3, from the parsed document) and `inline()` for cells and descriptions.
**Rejected**: rendering in the browser (a parser shipped to every page, and two renderers for the API).

## R5 Ticking a task
**Decision**: a renderer for the task marker draws an enabled checkbox labelled by its item's text and
numbered in reading order. Ticking it sends that number with Alpine Ajax to
`ToggleKnowledgeNoteTask`, which finds the marker's source line in the parsed document and flips it,
so a task inside a code block is never touched. Through the API, a task is ticked by updating the text.

## R6 Note editor
**Decision**: title, folder and a textarea. "Add a link" and Ctrl K open a small form inserting
`[text](address)` at the caret: `resources/js/knowledge-note-editor.js`, the one new JavaScript file,
since a caret cannot be reached from markup. A title clicked while reading is a one field form.

## R7 Lists
**Decision**: a new list is "Untitled list" with columns "Name" and "Note" and the add row open. Its
Edit form takes title, folder and description; "Edit columns" is its own screen (add, rename, move up
or down, remove with `x-delete-action` warning that the cells go). An item is edited in place of its
row and deleted with `x-delete-action`. A row whose cells are all blank is not saved.

## R8 Search
**Decision**: `App\Helpers\KnowledgeSearch` keeps a file when every typed word is found by
`SearchWords::matches` in its title, text, description or cells; the screen (`?search=`, as the vault
search reads a query) and the API share it. Titles alone load until somebody searches.
**Rejected**: a Scout index (asynchronous, a second index for one screen); `LIKE` (no accent or typing
tolerance, unlike the vault search). The vault wide search stays as it is (spec Assumptions).

## R9 Screens
**Decision**: `x-shell` with `x-vault.sidebar current="knowledge"`, the folder column as
`_folders.blade.php` and the reading pane beside it. The plus menu and each folder menu are
`x-actions-menu`. Folding is Alpine state, open by default. "New folder" opens a name field; a blank
name is refused. `knowledge` alone shows the empty screen, else Contents ("Sommaire" in the design).
Deleting a note or a list redirects to Contents. "Edited" uses `x-timestamp`.

## R10 Sidebar
**Decision**: the existing "Knowledge" heading gets one entry, "Knowledge", icon `journal`, its badge
the vault's notes and lists counted in `App\View\Components\Vault\Sidebar`. A `folder` glyph joins `x-icon`.

## R11 Policies
**Decision**: "the last folder cannot be deleted" and "a list keeps one column" are each read by a
screen and refused by an action, so each becomes a policy (`policies.md`):
`KnowledgeFolderPolicy::delete` and `KnowledgeListColumnPolicy::delete`, with the screen's wording.

## R12 Limits
**Decision**: titles and names 255 characters; note text `longText`, at most 200,000 characters;
descriptions and cells 5,000. Each action validates its own input.

## R13 Constitution
**Decision**: MINOR amendment 2.16.0: principle VII names Knowledge folder, Knowledge note, Knowledge
list, Knowledge list column and Knowledge list item (twenty two concepts), one sentence each, and list
columns as the second exception beside Possession type: text only, stored as rows, never as schema.
`spec/models/069` to `073` describe them, the cell inside 073.

## R14 Documentation
**Decision**: `resources/docs/api/33-knowledge-folders.php`, `34-knowledge-notes.php`,
`35-knowledge-lists.php` through `api-docs-writer`; a portal section `docs/portal/en/11-knowledge/`
through `writer`; an entry in `docs/FEATURES.md`.
