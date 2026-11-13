# Implementation Plan: Export a contact as Markdown

**Branch**: `2026-11-12-knowledge-dummy-data` (kept, by standing instruction) | **Date**: 2026-11-13 | **Spec**: [spec.md](spec.md)

## Summary

One view model writes the whole contact as Markdown, reusing the blocks the contact screen already draws from. One action refuses other accounts and journals the export. Three thin surfaces hand the same text over: a web download plus a clipboard copy, an API endpoint answering raw `text/markdown`, and an MCP tool. Four documentation surfaces describe it. [research.md](research.md) records the decisions; [contracts/contact-markdown.md](contracts/contact-markdown.md) fixes the document and the endpoint.

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13, Alpine.js

**Primary Dependencies**: `laravel/mcp` for the tool, Sanctum for the key scopes. Nothing new

**Storage**: none. No migration, no column, no stored file

**Testing**: PHPUnit: `tests/Unit/ViewModels/Vaults`, `tests/Unit/Actions`, `tests/Unit/Mcp/Tools`, `tests/Feature/Controllers/App/Vaults`, `tests/Feature/Controllers/Api`

**Target Platform**: server rendered web application, JSON API, MCP server

**Project Type**: web application (Blade, Tailwind, Alpine)

**Performance Goals**: SC-003. Every collection is loaded once with its relations eager loaded

**Constraints**: the spec is at 118 of 120 lines, so this plan links rather than restates

**Scale/Scope**: one contact per request. About eight new files and twelve edited

## Constitution Check

| Principle | How this change satisfies it |
| --- | --- |
| I. Actions hold the business logic | `ExportContactMarkdown` holds the refusal and the journal; controllers and the tool carry transport only |
| II. Simplicity | No new dependency, no Markdown library, no flag. The screen's own blocks are reused for relationships, groups and loans |
| III. Tested before merged | One test file per new class, plus the screen test |
| V. Localized | Headings and labels are translated strings in every locale; user content is left as written |
| VIII. Nothing disappears silently | Every export is journalled; a field the reader cannot reach is left out, and the API reference says so |
| IX. Accessible | The two controls are items of `x-actions-menu`; the copy result is announced in a live region |
| X. Everything through the API | `GET /vaults/{vault}/contacts/{contact}/markdown`, mirrored by the `get-contact-markdown` tool |
| XI. Short record | No `data-model.md`: the feature adds no entity. The document shape lives in the contract only |
| Invariant: sharing | No route under the share link changes |

**Gate result**: pass. **Post-design re-check**: pass. No folder, dependency or entity added.

## Project Structure

### Documentation (this feature)

```text
specs/123-export-contact-markdown/
├── plan.md, research.md, quickstart.md
├── contracts/contact-markdown.md
└── tasks.md   (/speckit-tasks)
```

### Source Code (repository root)

```text
app/
├── Actions/ExportContactMarkdown.php                     # new
├── Enums/UserActionEnum.php                              # edited: ContactExportedAsMarkdown
├── Http/Controllers/App/Vaults/ContactMarkdownController.php  # new: show()
├── Http/Controllers/Api/ContactMarkdownController.php    # new: show()
├── Mcp/Servers/MonicaServer.php                          # edited: register the tool
├── Mcp/Tools/GetContactMarkdown.php                      # new
├── ViewModels/Docs/McpDocsViewModel.php                  # edited: list the tool
├── ViewModels/Vaults/ContactMarkdownViewModel.php        # new: document(), fileName()
└── ViewModels/Vaults/ContactViewModel.php                # edited: markdownUrl()

resources/
├── docs/api/06-contacts.php                              # edited: the endpoint
├── docs/api-changelog/2026-11-13.php                     # edited: one entry
├── js/contact-markdown-copy.js, js/app.js                # new + registration
└── views/app/vaults/contacts/show.blade.php              # edited: two menu items

routes/web.php, routes/api.php                            # edited: one route each
docs/portal/en/8-sharing-export-and-sync/                 # new page + intro edited
docs/FEATURES.md                                          # edited
lang/*.json                                               # via monica:localize
```

**Structure Decision**: every file lands in a folder that exists. The web route is declared above the `vaults/{vault}/contacts/{contact}/{page?}` catch-all, beside `contacts.vcard.show`, for the reason 039 recorded.

## Complexity Tracking

None. The one judgement is a view model used outside Blade, recorded in [research.md](research.md) with its two precedents.
