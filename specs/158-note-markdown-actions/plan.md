# Implementation Plan: Markdown actions on a note

**Branch**: `158-note-markdown-actions` | **Date**: 2026-11-25 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/158-note-markdown-actions/spec.md`

## Summary

The reading view of a Knowledge note gains a "Markdown" menu beside Edit and Delete (design option 1a)
with Copy, View as MD and Download as MD. The document is built once in `KnowledgeMarkdown`; Copy and
View as MD work in the page, Download is a route like the contact screen's Markdown export. The
Markdown mark becomes the component `x-markdown-mark`. API and MCP are unchanged (spec FR-011).
Decisions and their alternatives are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: Blade, Alpine.js, Tailwind CSS; no new dependency

**Storage**: N/A, nothing stored or migrated

**Testing**: PHPUnit (`tests/Unit/Helpers`, `tests/Feature/Controllers/App/Vaults`, `tests/Feature/Views/Components`)

**Target Platform**: web application, the logged in vault screens

**Project Type**: web application (Laravel monolith)

**Performance Goals**: no extra query on the note screen; the document is built from the loaded note

**Constraints**: WCAG 2.2 AA; works with the keyboard alone; download works without JavaScript

**Scale/Scope**: one screen, one route, two components touched, one created

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. No business rule: reading only. The document is a helper function (R1) |
| II. Simplicity | Pass. No new JS module, reuse of `x-actions-menu`, `window.copyText()`, the contact download pattern |
| III. Tested before merged | Pass. Tests listed below |
| IV. Laravel conventions | Pass. `streamDownload`, `Str::slug`, named route, camelCase segment |
| V. Localized and safe | Pass. Every new wording through `__()`, translated in every locale |
| VI. Nothing changes under anybody's feet | Pass. Additive; existing controls stay where they are |
| VIII. Nothing disappears silently | Pass. Nothing is removed |
| IX. Accessible | Pass. Menu contract from `x-actions-menu`, `role="status"` for the copy result, decorative mark |
| X. Everything through the API | Pass. The API and `get-knowledge-note` already return title and Markdown body |
| XI. Short record | Pass. No data-model, contracts or quickstart: nothing stored, the route is below |

## Project Structure

### Source Code (repository root)

```text
app/Helpers/KnowledgeMarkdown.php                         # + document()
app/ViewModels/Vaults/KnowledgeNoteViewModel.php          # + markdown(), markdownUrl()
app/Http/Controllers/App/Vaults/KnowledgeNoteMarkdownController.php   # new, show()
routes/web.php                                            # + knowledge.noteMarkdown.show
resources/views/app/vaults/knowledge/note.blade.php      # menu, status line, source view
resources/views/components/markdown-mark.blade.php       # new
resources/views/components/icon.blade.php                # + copy, download
lang/*.json                                               # new wordings, every locale
tests/Unit/Helpers/KnowledgeMarkdownTest.php              # + document()
tests/Feature/Controllers/App/Vaults/KnowledgeNoteMarkdownControllerTest.php   # new
tests/Feature/Controllers/App/Vaults/KnowledgeNoteControllerTest.php           # + menu on the screen
tests/Feature/Views/Components/MarkdownMarkTest.php       # new
```

**Structure Decision**: existing Laravel layout; the download mirrors `ContactMarkdownController`.

### The new route

`GET vaults/{vault}/knowledge/notes/{knowledgeNote}/markdown` → `KnowledgeNoteMarkdownController@show`,
`whereUuid('knowledgeNote')`, name `knowledge.noteMarkdown.show`. Answers 200 with
`Content-Type: text/markdown; charset=utf-8` and `Content-Disposition: attachment; filename=<slug>.md`.
A note of another vault or account is not found through `ResolvesModelInVault`.

### New wordings

"Markdown", "View as MD", "View formatted", "Download as MD". Reused: "Copy", "Copied",
"Could not copy. Download the file instead."

### Tests, one layer each

- Helper: the title becomes a level one heading, a blank line, the body verbatim; a blank body.
- Controller: the file, its name and content type; the `note.md` fallback; another vault is not found.
- Note screen: the reading view offers the three lines and the download link; an empty note offers none.
- `x-markdown-mark`: decorative without a label, an image named by its label with one.
- `x-icon` glyphs and the Alpine toggles are the look and the browser: checked by viewing the screen.

### Delivery

Changelog entry (changelog skill), translations by Sonnet subagents, and the `definition-of-done`
skill before the commit, which also decides whether the Knowledge help page mentions the menu.

## Complexity Tracking

No violation to justify.
