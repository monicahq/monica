# Tasks: API changelog

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md)

Tests are included: `CLAUDE.md` makes a covering test part of "done".

## Phase 1: Setup

- [X] T001 Create `resources/docs/api-changelog/.gitkeep` (the folder ships empty, research R1)
- [X] T002 [P] Create fixture releases in `tests/Fixtures/Docs/ApiChangelog/Valid/` (`2026-09-16.php` with one non breaking entry in group `Vaults`; `2026-11-04.php` with three entries: a breaking `Changed` in `Contacts` with before and after examples, an `Added` in `Contacts` naming a documented endpoint, a `Removed` in `Your profile` naming an undocumented endpoint and no example; one entry in a group hidden by a flag) using The Office data, in the format of `data-model.md`
- [X] T003 [P] Create fixture releases in `tests/Fixtures/Docs/ApiChangelog/Invalid/`: one defect each (missing `summary`, unknown `kind`, unknown `group`, `breaking` without `impact`, a shared `id`, file name differing from `date`, empty `entries`)

## Phase 2: Foundational

- [X] T004 Create `app/ViewModels/Docs/ApiChangelogEntry.php`: readonly properties from one entry array plus its release `date` (`CarbonImmutable`); `endpoints(): list<array{method: string, path: string}>` (distinct, parsed from `'METHOD /path'`); `isBreaking(): bool`
- [X] T005 Create `app/ViewModels/Docs/ApiChangelogViewModel.php` with `__construct(?string $directory = null)` defaulting to `resource_path('docs/api-changelog')`, `releases()` (newest first, entries grouped by `group` in sidebar order, an entry dropped when its group is not among `new ApiDocsViewModel()->groups()` names, an emptied release dropped), `entry(string $id): ?ApiChangelogEntry`, `sameRelease(ApiChangelogEntry $entry): list<ApiChangelogEntry>`
- [X] T006 Add `problems(): list<string>` to `app/ViewModels/Docs/ApiChangelogViewModel.php` naming file and entry for: a missing required field (`date`, `entries`, `id`, `kind`, `group`, `title`, `summary`, `why`, `changes`, `example.after` when `example` is set), `kind` or `change` not "Added, Changed, Deprecated or Removed", `id` not `[a-z0-9-]+` or shared across releases, `group` not "the 'name' of a file in resources/docs/api", "required when breaking" `impact` missing, file name differing from `date`, empty `entries` or `changes`
- [X] T007 Add `sectionFor(string $method, string $path): ?ApiDocsSection` to `app/ViewModels/Docs/ApiDocsViewModel.php`, over non guide sections (research R3)
- [X] T008 Register the four routes of plan, Routes, in `routes/web.php` above `docs/api/{section}.md`, `{entry}` constrained to `[a-z0-9\-]+`, the list route with `searchable`; add the four names to the public list in `tests/Feature/RouteProtectionTest.php`
- [X] T009 Move the "Copy for LLM" and "View as Markdown" pair from `resources/views/components/api-docs/endpoint.blade.php` into `resources/views/components/api-docs/markdown-actions.blade.php` (props `markdown`, `url`), and use it in the endpoint (research R7)
- [X] T010 In `app/ViewModels/Docs/ApiDocsViewModel.php` `navigation()`, give each guide a `url` and append Changelog with `route('docs.api.changelog.index')`; read `$guide['url']` in `resources/views/components/api-docs/sidebar.blade.php`
- [X] T011 [P] Unit tests in `tests/Unit/ViewModels/Docs/ApiChangelogViewModelTest.php` on the `valid/` fixtures: newest first, grouping, hidden group dropped, `entry()` null for unknown and hidden, `sameRelease()`; `problems()` empty for `valid/`, one message per `invalid/` file; the shipped folder reports no problem
- [X] T012 [P] Unit tests in `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php`: `sectionFor()` finds a documented endpoint and returns null for an unknown one; the guides end with Changelog

## Phase 3: User Story 1, the list (P1)

Independent test: with `valid/` fixtures bound, releases read newest first with Latest, Breaking and endpoint counts; as shipped, the empty sentence.

- [X] T013 [US1] Create `app/Http/Controllers/App/Docs/ApiChangelogController.php` `index(ApiChangelogViewModel $viewModel): View` passing `navigation`, `releases`, `markdown` (research R5)
- [X] T014 [US1] Create `resources/views/components/api-docs/changelog-row.blade.php`: one `<a>` to `docs.api.changelog.show` with kind, title, `x-badge tone="danger"` Breaking, and "1 endpoint", ":count endpoints" or "All endpoints"; `focus-ring-inset`
- [X] T015 [US1] Create `resources/views/app/docs/api/changelog/index.blade.php` per plan, Screens, List: shell of `show.blade.php`, breadcrumb ending "Changelog", sidebar `current="changelog"`, introduction of FR-006, "No change has been recorded yet." when `releases` is empty, else releases with `<time>`, `x-badge tone="success"` Latest, note, groups of rows, and the "Releases" aside
- [X] T016 [US1] Controller tests in `tests/Feature/Controllers/App/Docs/ApiChangelogControllerTest.php`: as shipped the list shows the empty sentence and no filter; with `valid/` bound it shows a row title and its entry URL

## Phase 4: User Story 2, an entry (P1)

Independent test: a breaking entry shows the note, linked and plain endpoints, the same release block and the example toggle, and no "Upgrade".

- [X] T017 [US2] Add `show(ApiChangelogViewModel $viewModel, string $entry): View` to `app/Http/Controllers/App/Docs/ApiChangelogController.php`, `abort_if` null to 404, passing `entry`, `sameRelease`, `navigation`, `markdown`
- [X] T018 [US2] Create `resources/views/app/docs/api/changelog/show.blade.php` per plan, Screens, Entry: breadcrumb ending in the release date, "All changes", meta line, title, summary, `role="note"` breaking block, "Why it changed", "Changes" table with `x-api-docs.method-badge` and links through `sectionFor()`, "In the same release" rows; aside with `aria-pressed` Before/After only when `before` exists, `x-copy-button`, `<pre>`; no Upgrade, no version badge
- [X] T019 [US2] Controller tests in `tests/Feature/Controllers/App/Docs/ApiChangelogControllerTest.php`: the breaking fixture entry shows its title, "Why this is a breaking change", the `docs.api.show` URL of its documented endpoint, and not "Upgrade"; an unknown entry answers 404

## Phase 5: User Story 3, filters (P2)

Independent test: picking a kind, ticking breaking and searching hide rows, groups and releases, and the count follows.

- [X] T020 [US3] Create `resources/js/api-changelog.js` and register `apiChangelog` in `resources/js/app.js`: `query`, `kind`, `breakingOnly`, `matches(row)`, visible count, `clear()` (research R6)
- [X] T021 [US3] Wire `index.blade.php` and `changelog-row.blade.php`: `x-data="apiChangelog"`, `data-kind`, `data-breaking`, `data-text` on rows, `x-show` on rows, groups and releases, kind buttons with `:aria-pressed`, "Breaking changes only" checkbox, `aria-live` count, "No change matches" with "Clear filters"
- [X] T022 [US3] Controller test in `tests/Feature/Controllers/App/Docs/ApiChangelogControllerTest.php`: with fixtures, the list carries `x-data="apiChangelog"`, `aria-pressed` and "Breaking changes only"

## Phase 6: Markdown, sitemap, llms.txt (FR-001, FR-007)

- [X] T023 Add `markdown(?ApiChangelogEntry $entry = null): string` to `app/ViewModels/Docs/ApiChangelogViewModel.php` (list: releases, notes, entries with kind, title, breaking; entry: every block, no upgrade section; empty: the empty sentence)
- [X] T024 Create `app/Http/Controllers/App/Docs/ApiChangelogMarkdownController.php` (`index`, `show`, `text/markdown; charset=UTF-8`, 404 on unknown) and tests in `tests/Feature/Controllers/App/Docs/ApiChangelogMarkdownControllerTest.php`
- [X] T025 Add the changelog list and each visible entry to `llmsText()` and `sitemap()` in `app/ViewModels/Docs/DocsViewModel.php`, with a test in `tests/Unit/ViewModels/Docs/DocsViewModelTest.php`
- [X] T031 Add the changelog sentence deferred by spec 119 FR-007 to the footer list of `resources/views/components/api-docs/page-end.blade.php` (icon, "What changed recently? Read the changelog.", linking `docs.api.changelog.index`), asserted in `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php`
- [X] T026 [P] Markdown unit tests in `tests/Unit/ViewModels/Docs/ApiChangelogViewModelTest.php`: list names every fixture release, entry Markdown holds "Why it changed" and no "Upgrade"

## Phase 7: Polish

- [X] T027 [P] Add a section to `.claude/skills/api-docs-writer/SKILL.md`: a pull request changing the API adds an entry to `resources/docs/api-changelog/`, in the format of `data-model.md`
- [X] T028 Run the `accessibility-reviewer` and `rules-reviewer` agents on the change and fix what they confirm
- [X] T029 Run `php artisan test --compact tests/Unit/ViewModels/Docs tests/Feature/Controllers/App/Docs tests/Feature/RouteProtectionTest.php`, `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent` and `php artisan monica:localize --check`; all clean
- [X] T030 Walk through `quickstart.md`, By hand, and remove any fixture copied into `resources/docs/api-changelog/`

## Dependencies

Setup (T001 to T003) → Foundational (T004 to T012) → US1 → US2 → US3 → Phase 6 → Polish. T004 before T005 before T006; T007 before T018. US1 and US2 share the controller and its test file, US1 and US3 the list view and the row, so run the stories in sequence. T011 and T026 share a file: write them one after the other. [P] tasks touch files no other open task touches.

MVP: Phases 1 to 4 (list and entry). US3 and Markdown follow.
