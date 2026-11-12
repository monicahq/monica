# Implementation Plan: End of an API reference page

**Branch**: `2026-11-12-docs-portal-design` | **Date**: 2026-11-12 | **Spec**: [spec.md](spec.md)

## Summary

Every page under `/docs/api/{section}` ends with variant 1a of the design: previous and next cards,
then a footer of one sentence per destination (support, `llms.txt`) and the date the reference was
last generated. The view model supplies the neighbours, the support link and the date; one new Blade
component draws them. Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)
**Primary Dependencies**: Blade, Tailwind CSS 4, existing `x-icon`
**Storage**: N/A, the reference is read from `resources/docs/api/*.php`
**Testing**: PHPUnit, `php artisan test --compact`
**Target Platform**: the public docs portal, signed in or not
**Project Type**: web application (Laravel monolith)
**Performance Goals**: no extra query; neighbours come from the list the page already builds
**Constraints**: WCAG 2.2 AA; English copy in markup, as the rest of the API reference
**Scale/Scope**: one view model, one controller method, one component, one view, one skill line

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | N/A: nothing is written, a view model reads |
| II. Simplicity | Pass: one component, three methods on an existing view model |
| III. Tested before merged | Pass: view model unit tests, one controller render test |
| V. Localized | Pass by existing exception: the API reference is English only (R4) |
| IX. Accessible | Pass: named `nav` landmark, `aria-hidden` icons, `focus-ring`, `<time datetime>` |
| X. Through the API | N/A: documentation screen |
| XI. Short record | Pass: data-model and contracts omitted, the plan carries them |

Re-checked after design: no change.

## Project Structure

```text
app/ViewModels/Docs/ApiDocsViewModel.php          # + LAST_GENERATED, previous(), next(), lastGenerated(), supportUrl()
app/Http/Controllers/App/Docs/ApiDocsController.php  # show() passes the four values
resources/views/components/api-docs/page-end.blade.php  # new: cards + footer, variant 1a
resources/views/app/docs/api/show.blade.php       # renders x-api-docs.page-end after the endpoint
.claude/skills/api-docs-writer/SKILL.md           # + move LAST_GENERATED when the reference changes
tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php      # neighbours, date, support
tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php  # the page ends with cards and footer
```

## Design

**View model** (`ApiDocsViewModel`):

| Member | Returns |
| --- | --- |
| `const string LAST_GENERATED = '2026-11-12'` | the date, moved by hand with the reference |
| `previous(ApiDocsSection $section): ?ApiDocsSection` | the entry before it in `sections()`, or `null` |
| `next(ApiDocsSection $section): ?ApiDocsSection` | the entry after it, or `null` |
| `lastGenerated(): CarbonImmutable` | `LAST_GENERATED` parsed |
| `supportUrl(): ?string` | `route('support.requests.new')` while support is enabled, else `null` |

Match on `$section->id`, never on object identity.

**Controller**: `show()` adds `previous`, `next`, `lastGenerated` and `supportUrl` to the view data.

**Component** `x-api-docs.page-end`, props `previous`, `next`, `lastGenerated`, `supportUrl`:

- Wrapper `mt-16 flex max-w-184 flex-col gap-8` (736px, the design's column).
- `<nav aria-label="Previous and next pages">` holding a two column grid, only when either card
  exists. Each card is an `<a>` with `rounded-lg border border-hairline shadow-panel px-4 py-3
  hover:bg-hover focus-ring`: a small muted line ("Previous" with `arrow-right` rotated 180°, or
  "Next" with `arrow-right`) and the title in `font-semibold text-ink`. The next card is
  `col-start-2 items-end` so it keeps the right side alone. Hidden in print.
- `<footer class="border-t border-hairline pt-8 flex flex-col gap-6">`: a `<ul>` of sentences with a
  muted 16px icon (`aria-hidden`) and an underlined link (`underline decoration-hairline-strong
  underline-offset-3 hover:decoration-ink`):
  - only when `supportUrl`: `mail`, "Stuck on something? <a>Write to support</a>."
  - always: `file`, "Reading this with an LLM? <a href=llmsText.index>Use llms.txt</a>."
- Then `<p class="text-[12.5px] text-muted">Last generated on <time datetime="Y-m-d">j F Y</time></p>`.

Opening comment and `@var` block per `.claude/rules/code-style.md`.

## Tests

| Layer | Test |
| --- | --- |
| View model | first page has no previous; last has no next; middle page names both; a section hidden by a flag is skipped; `lastGenerated()` parses the constant; `supportUrl()` is null while support is off |
| Controller | a middle page shows both card titles with their `docs.api.show` links, the `llms.txt` link, the support link and the `datetime` |

No view test of the look (`.claude/rules/testing.md`). No translation change (R4).

## Complexity Tracking

None.
