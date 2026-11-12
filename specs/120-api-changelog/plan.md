# Implementation Plan: API changelog

**Branch**: `2026-11-12-docs-portal-design` | **Date**: 2026-11-12 | **Spec**: [spec.md](spec.md)

## Summary

A changelog list at `/docs/api/changelog` and one page per entry at `/docs/api/changelog/{entry}`,
each with a Markdown twin, drawn from the design without its version parts and without "Upgrade".
Entries are PHP files under `resources/docs/api-changelog/`, which ships empty. Decisions are in
[research.md](research.md), the entry format in [data-model.md](data-model.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major); Blade, Tailwind CSS 4, Alpine.js
**Storage**: none, files under `resources/docs/api-changelog/` | **Testing**: PHPUnit
**Target Platform**: the public docs portal (Laravel monolith), signed in or not
**Constraints**: WCAG 2.2 AA; English copy in markup, as the rest of the API reference
**Scale/Scope**: 2 controllers, 2 view model classes, 2 views, 2 components, 1 script, 4 routes

## Constitution Check

| Principle | Status |
| --- | --- |
| II. Simplicity | Pass: arrays in files, as the reference; no table, no command |
| III. Tested before merged | Pass: view model unit tests on fixtures, controller tests, content check |
| V. Localized | Pass by existing exception: the API reference is English only (R6) |
| VI. Nothing changes under anybody's feet | Pass: additive pages; the changelog is how future changes are announced |
| IX. Accessible | Pass: `aria-pressed` buttons, `role="note"`, named landmarks, `focus-ring`, `<time>` |
| XI. Short record | Pass: spec trimmed to 120 lines; contracts omitted, routes are below |

I and X do not apply: nothing is written, and this is a documentation screen. Re-checked after
design: no change.

## Project Structure

```text
resources/docs/api-changelog/.gitkeep                         # new, empty at ship
app/ViewModels/Docs/ApiChangelogEntry.php                     # new
app/ViewModels/Docs/ApiChangelogViewModel.php                 # new: releases, entry, sameRelease, problems, markdown
app/ViewModels/Docs/ApiDocsViewModel.php                      # + sectionFor(); navigation() guides carry url + Changelog
app/ViewModels/Docs/DocsViewModel.php                         # llmsText() and sitemap() list the changelog
app/Http/Controllers/App/Docs/ApiChangelogController.php      # new: index, show
app/Http/Controllers/App/Docs/ApiChangelogMarkdownController.php # new: index, show
routes/web.php                                                # 4 routes, before docs/api/{section}
resources/views/app/docs/api/changelog/index.blade.php        # new: list
resources/views/app/docs/api/changelog/show.blade.php         # new: entry
resources/views/components/api-docs/markdown-actions.blade.php # new, from endpoint.blade.php (R7)
resources/views/components/api-docs/changelog-row.blade.php   # new: one row, used by list and "In the same release"
resources/views/components/api-docs/{endpoint,sidebar}.blade.php # use markdown-actions; guide url
resources/js/api-changelog.js + app.js                        # filters (R6)
.claude/skills/api-docs-writer/SKILL.md                       # every API change adds an entry
tests/Fixtures/Docs/ApiChangelog/{valid,invalid}/*.php       # fixture releases
tests/Unit/ViewModels/Docs/ApiChangelogViewModelTest.php      # new
tests/Feature/Controllers/App/Docs/ApiChangelog{,Markdown}ControllerTest.php # new
tests/Feature/RouteProtectionTest.php                         # 4 public routes
```

## Routes

| Method | URI | Name | Answers |
| --- | --- | --- | --- |
| GET | `docs/api/changelog` | `docs.api.changelog.index` | list, `searchable` |
| GET | `docs/api/changelog.md` | `docs.api.changelog.markdown.index` | `text/markdown` |
| GET | `docs/api/changelog/{entry}` | `docs.api.changelog.show` | entry, 404 when unknown or hidden |
| GET | `docs/api/changelog/{entry}.md` | `docs.api.changelog.markdown.show` | `text/markdown`, 404 likewise |

`{entry}` is `[a-z0-9\-]+`. Registered above `docs/api/{section}.md` and `docs/api/{section}`, whose
pattern also matches `changelog`.

## Screens

**List**: shell of `show.blade.php` (header, breadcrumb ending "Changelog", subheader, sidebar with
`current="changelog"`). Main: `v1` caption and `x-api-docs.markdown-actions`; title; introduction
(FR-006). When `releases()` is empty, one sentence, "No change has been recorded yet.", and nothing
else. Otherwise the filter bar (search, kind group of `aria-pressed` buttons, "Breaking changes only",
`aria-live` count), the "No change matches" block with "Clear filters", then one `<section
id="release-YYYY-MM-DD">` per release (date `<h2>` in a `<time>`, `x-badge` Latest, note, groups of
`x-api-docs.changelog-row`), and the "Releases" `<aside>` of anchor links with total counts.

**Entry**: "All changes" link and markdown actions; meta line (date, kind, group, Breaking); title;
summary; `role="note"` breaking block; "Why it changed"; "Changes" table (field in `<code>`, change,
endpoints as `x-api-docs.method-badge` + path, linked through `sectionFor()`); "In the same release"
rows. Aside, when an example exists: label, Before/After buttons only when `before` exists, "Copy"
through `x-copy-button`, `<pre>`. No Upgrade, no version badge.

## Tests

| Layer | Test |
| --- | --- |
| View model | releases newest first; grouping by group; Latest is the newest; hidden group drops the entry and an emptied release; endpoint count and "All endpoints"; `sameRelease()`; `entry()` null for unknown; `problems()` empty for `valid/`, names each defect in `invalid/`; shipped folder has no problem; Markdown of list and entry has no "Upgrade" |
| ApiDocsViewModel | `sectionFor()` finds a documented endpoint, `null` for an unknown one; guides end with Changelog |
| Controller | list answers with the empty sentence; with fixtures, list shows a row and entry shows its title, a linked endpoint and no "Upgrade"; unknown entry 404 |
| Markdown controller | both answer `text/markdown`; unknown entry 404 |
| DocsViewModel | `llmsText()` and `sitemap()` name the changelog |

No view test of the look (`.claude/rules/testing.md`). No translation change (R6).

## Complexity Tracking

None.