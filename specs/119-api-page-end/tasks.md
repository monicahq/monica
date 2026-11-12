# Tasks: End of an API reference page

**Input**: [spec.md](spec.md), [plan.md](plan.md) (Design and Tests sections), [research.md](research.md)

Tests are included: `CLAUDE.md` makes a covering test part of "done".

## Phase 1: Foundational

- [x] T001 Add `LAST_GENERATED = '2026-11-12'` and `lastGenerated(): CarbonImmutable` to `app/ViewModels/Docs/ApiDocsViewModel.php` (plan, Design)
- [x] T002 Create `resources/views/components/api-docs/page-end.blade.php` with its opening comment, `@var` block and `@props(['previous' => null, 'next' => null, 'lastGenerated', 'supportUrl' => null])`, wrapper `mt-16 flex max-w-184 flex-col gap-8`
- [x] T003 Render `<x-api-docs.page-end>` after `<x-api-docs.endpoint>` inside `<main>` in `resources/views/app/docs/api/show.blade.php`, and pass `lastGenerated` from `show()` in `app/Http/Controllers/App/Docs/ApiDocsController.php`

## Phase 2: User Story 1, previous and next cards (P1)

Independent test: a middle page links to both neighbours; the first and last pages show one card.

- [x] T004 [US1] Add `previous(ApiDocsSection $section): ?ApiDocsSection` and `next(...)` to `app/ViewModels/Docs/ApiDocsViewModel.php`, matching on `$section->id` within `sections()`
- [x] T005 [US1] Pass `previous` and `next` from `ApiDocsController::show()` in `app/Http/Controllers/App/Docs/ApiDocsController.php`
- [x] T006 [US1] Draw the `<nav aria-label="Previous and next pages">` card grid in `resources/views/components/api-docs/page-end.blade.php` per plan, Design (next card `col-start-2 items-end`, `focus-ring`, icons `aria-hidden`, `print:hidden`, absent when both are null)
- [x] T007 [P] [US1] Unit tests in `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php`: first has no previous, last has no next, middle names both, a section hidden by a flag is skipped

## Phase 3: User Story 2, footer (P2)

Independent test: the support and `llms.txt` links resolve, support disappears while switched off, the date reads `datetime="2026-11-12"`.

- [x] T008 [US2] Add `supportUrl(): ?string` to `app/ViewModels/Docs/ApiDocsViewModel.php` returning `route('support.requests.new')` only while `SupportAvailability::isEnabled()`, and pass it from `ApiDocsController::show()`
- [x] T009 [US2] Draw the `<footer>` (support sentence when `supportUrl`, `llms.txt` sentence to `route('llmsText.index')`, then `Last generated on <time>`) in `resources/views/components/api-docs/page-end.blade.php` per plan, Design; no changelog sentence
- [x] T010 [P] [US2] Unit tests in `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php`: `supportUrl()` is null while support is off and the route while on; `lastGenerated()` parses the constant
- [x] T011 [US2] Controller test in `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php`: a middle page shows both card titles with their `docs.api.show` URLs, the `llms.txt` URL, the support URL and `datetime="2026-11-12"`

## Phase 4: Polish

- [x] T012 [P] Add one line to `.claude/skills/api-docs-writer/SKILL.md`: move `ApiDocsViewModel::LAST_GENERATED` to the current date whenever the reference changes
- [x] T013 Run `php artisan test --compact tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php tests/Feature/Controllers/App/Docs`, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`; all clean
- [x] T014 Run `php artisan monica:localize --check` and confirm it passes with no new string (copy is English in markup, research R4)

## Dependencies

T001 → T002 → T003 → Phase 2 and Phase 3, which are independent of each other but share `page-end.blade.php` and `ApiDocsViewModel.php`, so run them in sequence. T007 and T010 touch the same test file: write them one after the other. T013 and T014 come last.
