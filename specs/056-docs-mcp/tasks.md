---
description: "Task list for the MCP documentation page"
---

# Tasks: MCP documentation page

**Input**: Design documents from `specs/056-docs-mcp/`

**Prerequisites**: plan.md, spec.md, research.md, contracts/page-content.md, quickstart.md

**Tests**: Required (constitution III). Follow `.claude/rules/testing.md`: strict types, `#[Test]`, snake_case `it_` names, named arguments, no comments, no class string or markup assertion. The view model owns what the page says; the controllers own the routes.

**Copy**: every sentence is the design's (`Docs MCP.dc.html`) unless the spec's "Copy corrections" replaces it. English literals, as the API reference writes them (research R1).

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

No setup: no dependency, configuration or schema change.

## Phase 2: Foundational

- [X] T001 Create `resources/views/components/docs/header.blade.php` from the top bar of `resources/views/app/docs/index.blade.php` (logo to `home.index`, `x-theme-toggle`, Sign in under `@guest`), opening with its one line `{{-- --}}` comment; replace the header in `resources/views/app/docs/index.blade.php` and `resources/views/app/docs/api/show.blade.php` with `<x-docs.header />`
- [X] T002 Run `php artisan test --compact tests/Feature/Controllers/App/Docs` and fix any assertion that expected Sign in on `/docs/api` for a signed in user

## Phase 3: User Story 1 - Connect an assistant to Monica (Priority: P1)

**Goal**: `/docs/mcp` answers publicly with the overview, the server address, the setup steps, the seven client tabs and "Check your connection".

**Independent Test**: spec US1.

- [X] T003 [US1] Create `app/ViewModels/Docs/McpDocsViewModel.php` with `instanceAddress(): string` (`rtrim((string) config('app.url'), '/')`), `serverAddress(): string` (`instanceAddress().'/api/mcp'`), and `clients(): list<array{key: string, label: string, blocks: list<array{label: string, lines: list<array{parts: list<array{text: string, colour: string}>}>, code: string}>}>` holding the seven tabs of `contracts/page-content.md` in order, each code line passed through `ApiDocsCodeSamples::highlight()`, JSON with two spaces
- [X] T004 [US1] Create `app/Http/Controllers/App/Docs/McpDocsController.php` with `index(): View` returning `view('app.docs.mcp', ['viewModel' => new McpDocsViewModel])`; add `Route::get('docs/mcp', [McpDocsController::class, 'index'])->name('docs.mcp.index');` after `docs.index` in `routes/web.php`
- [X] T005 [US1] Create `resources/views/app/docs/mcp.blade.php`: a full document like `api/show.blade.php` (`partials.meta` titled "Model Context Protocol (MCP)", `<x-docs.header />`), a breadcrumb Home › Docs › MCP, the `h1` and lead, the overview line and its two anchor links, the server address with `x-copy-button`; "Set up Monica MCP" with the corrected API key steps, the "Your Monica address" `x-input` bound in Alpine (`address`, falling back to `instanceAddress()` when empty, trailing slashes removed), and the tab list per plan (`role="tablist"`/`tab`/`tabpanel`, `aria-controls`, `aria-labelledby`, roving `tabindex`, ArrowLeft, ArrowRight, Home, End), each panel's paragraphs, code blocks drawn like `x-api-docs.code-panel` whose spans and copy text replace `serverAddress()` with the typed address, and the notices through `x-notice` (Claude Desktop warning and VS Code info per the spec's corrections, ChatGPT quiet); "Check your connection" with its two prompts; no `href="#"`
- [X] T006 [US1] In `app/ViewModels/Docs/DocsViewModel.php`, set the MCP area's `url` to `route('docs.mcp.index')`; update `it_links_only_the_areas_that_have_pages` in `tests/Unit/ViewModels/Docs/DocsViewModelTest.php` to expect it
- [X] T007 [P] [US1] Create `tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php`: `it_builds_the_server_address_from_the_instance_address` (`config(['app.url' => 'https://monica.test/crm/'])` gives `https://monica.test/crm/api/mcp`) and `it_offers_every_client_in_the_order_of_the_design` (seven keys, Claude Code first, each configuration's `code` containing `serverAddress()` except Claude Desktop and ChatGPT, which have no block)
- [X] T008 [P] [US1] Create `tests/Feature/Controllers/App/Docs/McpDocsControllerTest.php`: `it_shows_the_mcp_page_to_a_visitor_who_is_not_signed_in` (200, sees "Model Context Protocol (MCP)" and the server address)
- [X] T009 [US1] Add `'docs.mcp.index'` to `$publicRoutes` in `tests/Feature/RouteProtectionTest.php`

## Phase 4: User Story 2 - Know what the assistant can do and how to stop it (Priority: P2)

**Goal**: "Manage access" and "Available tools" as corrected, the table held to the server.

**Independent Test**: spec US2.

- [X] T010 [US2] Add `toolGroups(): list<array{name: string, changesData: bool, tools: list<array{name: string, description: string}>}>` to `McpDocsViewModel.php`, groups and order from `contracts/page-content.md`, descriptions as corrected in the spec
- [X] T011 [US2] In `mcp.blade.php`, add "Manage access" (Settings → Logs, revocation under API keys, the "Assistant is a label, not a limit" info notice saying "every vault in your account") and "Available tools" (a panel per group, `x-caption` for its name, `x-badge tone="warning"` "Changes data" where `changesData`, `<code>` names), the sentence counting the tools from `toolGroups()`, and "Our goal is to make every action available in Monica accessible through MCP."
- [X] T012 [US2] In `McpDocsViewModelTest.php`, add `it_lists_exactly_the_tools_the_server_registers` (reflect `EricaServer`'s `tools` default, build each class, compare the sorted `name()` values with the names in `toolGroups()`, research R5) and `it_marks_only_the_contact_changing_tools_as_changing_data`

## Phase 5: User Story 3 - Fix a failing connection (Priority: P3)

**Goal**: the four troubleshooting entries as corrected, and "See also".

**Independent Test**: spec US3.

- [X] T013 [US3] Add `troubleshooting(): list<array{title: string, body: string}>` to `McpDocsViewModel.php` (refused: "wrong or revoked"; unreachable: ends in `/api/mcp`; empty key; too many requests: "60 requests a minute for each key", and, when `config('monica.self_hosted')`, that this instance sets no limit instead)
- [X] T014 [US3] In `mcp.blade.php`, add "Troubleshooting" and "See also" (`docs.api.index`, `settings.security.index`, `settings.logs.index`)
- [X] T015 [US3] In `McpDocsViewModelTest.php`, add `it_gives_the_hosted_request_limit` and `it_says_a_self_hosted_instance_sets_no_limit`

## Phase 6: User Story 4 - Read the page with an assistant (Priority: P4)

**Goal**: Copy for LLM, View as Markdown, Open in Claude and ChatGPT, `/docs/mcp.md`, `/llms.txt`.

**Independent Test**: spec US4.

- [X] T016 [US4] Add `markdown(): string` to `McpDocsViewModel.php` writing every section in page order from `clients()`, `toolGroups()` and `troubleshooting()` (fenced code blocks with `json`, `bash`, `toml`), and `assistantLinks(): array{claude: string, chatgpt: string}` per research R3 with `rawurlencode` of the prompt naming `route('docs.mcp.markdown.index')`
- [X] T017 [US4] Create `app/Http/Controllers/App/Docs/McpDocsMarkdownController.php` with `index(): Response` returning `markdown()` as `text/markdown; charset=UTF-8`; add `Route::get('docs/mcp.md', [McpDocsMarkdownController::class, 'index'])->name('docs.mcp.markdown.index');` before `docs/mcp` in `routes/web.php`; add it to `$publicRoutes` in `RouteProtectionTest.php`
- [X] T018 [US4] In `mcp.blade.php`, add the action row under the lead: "Copy for LLM" copying `markdown()` through `window.copyText` as `x-api-docs.endpoint` does, "View as Markdown", "Open in Claude", "Open in ChatGPT" (`target="_blank"`, `rel="noopener"`); add the "On this page" aside (hidden below `lg`, Alpine marks the section in view with `aria-current="location"`)
- [X] T019 [US4] In `DocsViewModel::llmsText()`, add `## MCP` and `- [MCP](route('docs.mcp.markdown.index')): connect an assistant to Monica`; extend `it_lists_every_page_of_the_reference_in_the_text_file_for_assistants` in `DocsViewModelTest.php`
- [X] T020 [P] [US4] In `McpDocsViewModelTest.php`, add `it_writes_every_section_and_configuration_in_the_markdown` and `it_points_both_assistant_links_at_the_markdown`
- [X] T021 [P] [US4] Create `tests/Feature/Controllers/App/Docs/McpDocsMarkdownControllerTest.php`: `it_serves_the_mcp_page_as_markdown_to_anybody` (200, `text/markdown; charset=UTF-8`, starts with `# Model Context Protocol (MCP)`)

## Phase 7: Polish

- [X] T022 Run `php artisan monica:localize`, translate what it reports in every locale per `.claude/rules/translations.md`, then `php artisan monica:localize --check`
- [X] T023 Run `php artisan test --compact tests/Unit/ViewModels/Docs tests/Feature/Controllers/App/Docs tests/Feature/RouteProtectionTest.php`, `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`
- [X] T024 Walk `quickstart.md` "By hand" by rendering the page in a throwaway test (no browser tools): one `h1`, tab list contract, no `href="#"`, every sentence against the spec's corrections
- [X] T025 Add the entry for the page to `docs/FEATURES.md`

## Dependencies

- T001 → T002 → T003 → T004 → T005 → T006, T007, T008, T009.
- US2, US3 and US4 each add to the view model and view US1 creates, so they follow US1 in any order; T016 needs T010 and T013.
- T022 to T025 last.

## Parallel Example

- After T005: T007 and T008 together. After T018: T020 and T021 together.

## Implementation Strategy

MVP is US1: the page a reader can connect an assistant from. US2 to US4 each add sections to it.
