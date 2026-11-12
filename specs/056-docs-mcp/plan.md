# Implementation Plan: MCP documentation page

**Branch**: `2026-09-25-docs-mcp` | **Date**: 2026-09-25 | **Spec**: [spec.md](spec.md)

## Summary

A public page at `/docs/mcp`, and its Markdown twin at `/docs/mcp.md`, built the way the API
reference is: a controller per route, one view model holding the page's content, a Blade view drawing
it. The tools table is written in the view model and a test holds it to the tools `MonicaServer`
registers. The portal card and `/llms.txt` point at the new page.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (current major), Blade, Tailwind CSS 4, Alpine.js

**Primary Dependencies**: `laravel/mcp` (read only: tool names come from `Primitive::name()`)

**Storage**: none

**Testing**: PHPUnit, feature tests for the two routes, unit tests for the view model

**Target Platform**: the web application, desktop, tablet and phone

**Project Type**: server rendered web application

**Constraints**: WCAG 2.2 AA; no new dependency; no new top level folder

**Scale/Scope**: one screen, one Markdown document, two edited files (portal view model, llms.txt)

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions / view models | Pass. Nothing is written; the view model only reads copy and configuration |
| II. Simplicity | Pass. No helper beyond the view model; configurations are literal strings |
| III. Tested | Pass. Controller, Markdown controller, view model and the tool list parity are tested |
| IV. Laravel conventions | Pass. Named routes `docs.mcp.index` and `docs.mcp.markdown.index`, kebab URLs |
| V. Localized | Deviation, inherited: the page copy is English in the markup, as the API reference is ([research](research.md#r1)) |
| IX. Accessible | Pass by design: tab list with arrow keys, `aria-current` in the section list, copy results announced |
| X. Through the API | Not affected. The page documents MCP and adds no capability |
| XI. Short record | Pass: spec 120 lines; `data-model.md` omitted, nothing is stored |

## Project Structure

```text
app/Http/Controllers/App/Docs/McpDocsController.php          # GET docs/mcp
app/Http/Controllers/App/Docs/McpDocsMarkdownController.php  # GET docs/mcp.md
app/ViewModels/Docs/McpDocsViewModel.php     # address, clients, tools, troubleshooting, markdown()
app/ViewModels/Docs/DocsViewModel.php        # MCP card url; llmsText() gains an MCP line
resources/views/app/docs/mcp.blade.php       # the screen
resources/views/components/docs/header.blade.php  # third copy of the docs top bar, extracted
resources/views/app/docs/index.blade.php, api/show.blade.php  # use x-docs.header
routes/web.php                               # two routes, declared before docs/api
tests/Feature/Controllers/App/Docs/McpDocsControllerTest.php
tests/Feature/Controllers/App/Docs/McpDocsMarkdownControllerTest.php
tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php
```

**Structure Decision**: follows `ApiDocsController` / `ApiDocsMarkdownController` /
`ApiDocsViewModel`. The top bar now stands in three files, which is the components rule's bar for
extraction; `api/show` gains the `@guest` guard `index` already has, which the spec asks for.

## Design decisions

- **Server address**: `rtrim(config('app.url'), '/').'/api/mcp'`, which keeps a sub path and a port.
- **Configurations**: built in the view model as lines, highlighted with
  `ApiDocsCodeSamples::highlight()`, drawn like `x-api-docs.code-panel`. Contents are fixed in
  [contracts/page-content.md](contracts/page-content.md).
- **"Your Monica address" field**: Alpine holds the address; each code span and the copy action
  replace the instance address with it. Empty falls back to the instance address.
- **Client tabs**: plain markup in the view (`role="tablist"`, `tab`, `tabpanel`, roving
  `tabindex`, arrow keys, Home and End). `x-tabs.item` renders `aria-selected` from PHP only, and
  one file does not meet the bar for a new component.
- **Tools**: a list of groups in the view model. The unit test builds each tool class `MonicaServer`
  lists (read through reflection of its `$tools`), calls `name()`, and compares with the table.
- **Copy for LLM / View as Markdown**: as `x-api-docs.endpoint` does, with `window.copyText`.
  **Open in Claude / ChatGPT**: links with a prefilled prompt naming `/docs/mcp.md` ([research](research.md#r3)).
- **On this page**: Alpine marks the section in view with `aria-current="location"`; hidden below `lg`.

Post design check: unchanged, no new gate failed.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| English copy in markup (V) | Same register as the API reference, which is the precedent | About 60 reference strings in 30 locales, none reviewed by a native speaker |
