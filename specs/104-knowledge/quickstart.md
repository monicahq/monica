# Quickstart: Knowledge

Each layer proves its own rules (`.claude/rules/testing.md`). Run the narrowest file, then the suite.

| Test | Proves |
| --- | --- |
| `Unit/Helpers/KnowledgeMarkdownTest` | escapes HTML and unsafe links; autolinks open a new tab; headings with ids; task numbering |
| `Unit/Helpers/KnowledgeSearchTest` | every word must match; title, text, description, cell; accents and one typo; another vault left out |
| `Unit/Models/Knowledge*Test` | folder, column and item ordering; files by title |
| `Unit/Policies/KnowledgeFolderPolicyTest`, `KnowledgeListColumnPolicyTest` | permitted, refused at the last one, nobody signed in |
| `Unit/Actions/*KnowledgeFolder*Test` | create, rename, move, delete moving or deleting files, refuse the last; journal; another account |
| `Unit/Actions/*KnowledgeNote*Test` | create (with "General" when none), update, move, delete; task toggle skips code blocks |
| `Unit/Actions/*KnowledgeList*Test` | default columns; column add, rename, move, remove with cells, refuse the last; items, blank row refused |
| `Unit/ViewModels/Vaults/Knowledge*ViewModelTest` | empty screen, folder tree and counts, Contents and its sums, list table and dashes, wording |
| `Feature/Views/Components/SidebarTest` | Knowledge entry and its count |
| `Feature/Controllers/App/Vaults/Knowledge*ControllerTest` | each route answers, inline validation, redirects, wrong vault 404 |
| `Feature/Controllers/Api/Knowledge/*ControllerTest` | JSON shape, 201/204, 422, list leaves out other vaults |
| `Unit/Mcp/Tools/*Knowledge*Test` | each tool reaches its action |

```bash
php artisan test --compact tests/Unit/Helpers/KnowledgeMarkdownTest.php
php artisan monica:localize && php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

## By hand, at erica.test

1. A new vault: Knowledge in the sidebar, the empty screen matching the design.
2. "Write a first note": "General" appears, the editor opens; write headings, tasks, a quote, a bare
   address and a link through Ctrl K; save, tick a task, reload: it stays ticked.
3. "Start a list": add Kevin's restaurants, add a "Cuisine" column, remove "Note" (warned), delete an item.
4. Create Home and Travel, move a note there, rename by double click, move Travel up, delete Home
   moving its files, then try the last folder.
5. Search "cooper" and "scranon": results and their folders; Contents lists headings that open the note.
6. Paste `<script>alert(1)</script>` in a note and a cell: shown as text.
7. Keyboard alone, light and dark, `fr_FR`, 375 px and 200%.
