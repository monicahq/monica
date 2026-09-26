---
description: "Task list for the documentation portal"
---

# Tasks: Documentation portal

**Input**: Design documents from `specs/055-docs-portal/`

**Prerequisites**: plan.md, spec.md, research.md, contracts/llms-txt.md

**Tests**: Required (constitution III). Follow `.claude/rules/testing.md`: strict types, `#[Test]`, snake_case `it_` names, named arguments, no comments, no class string or markup assertion. Each rule is tested once, in the layer the plan's tests table gives it.

**Organization**: US1 is the page and its cards, US2 the two buttons on it, US3 `/llms.txt` and the note pointing at it.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

No setup: no dependency, configuration or schema change.

## Phase 2: Foundational

No foundational work: each story adds its own route.

## Phase 3: User Story 1 - Find the right documentation area (Priority: P1)

**Goal**: `/docs` answers publicly with the design's header, title, line, illustration, four cards and footer.

**Independent Test**: spec US1.

- [X] T001 [US1] Create `app/ViewModels/Docs/DocsViewModel.php` with `areas(): list<array{icon: string, kind: string, title: string, description: string, url: ?string}>` (the four cards of research R2 and R5, copy from the design through `__()`, `url` = `route('docs.api.index')` for API and null for the others) and `host(): string` (host of `config('app.url')`)
- [X] T002 [US1] Create `app/Http/Controllers/App/Docs/DocsController.php` with `index(): View` returning `view('app.docs.index', ['viewModel' => new DocsViewModel])`; add `Route::get('docs', [DocsController::class, 'index'])->name('docs.index');` above the `docs/api` routes in `routes/web.php`
- [X] T003 [US1] Create `resources/views/app/docs/index.blade.php`: a full document like `resources/views/app/docs/api/show.blade.php` (same `partials.meta` with title `__('Documentation')`, same header with `x-logo`, `x-theme-toggle`, and Sign in under `@guest`); the hero `h1`, line and illustration (`aria-hidden="true"`, `{host}/dav`, `{host}/api/mcp`, two unprefixed made up keys, "2 active" through `trans_choice`); the cards as `<ul role="list">`, an `<a>` with `arrow-right` when `url` is set and a `<div>` without it otherwise; the footer with "Monica is open source and can be self hosted." and "Source code" to `https://github.com/monicahq/monica`; tokens and `focus-ring` only, no new CSS (R4, R5)
- [X] T004 [P] [US1] Create `tests/Unit/ViewModels/Docs/DocsViewModelTest.php`: `it_links_only_the_areas_that_have_pages` (API has `route('docs.api.index')`, the other three null)
- [X] T005 [P] [US1] Create `tests/Feature/Controllers/App/Docs/DocsControllerTest.php`: `it_shows_the_portal_to_a_visitor_who_is_not_signed_in` (200, sees "Documentation" and `route('docs.api.index')`) and `it_does_not_offer_to_sign_in_to_somebody_signed_in`
- [X] T006 [US1] Add `'docs.index'` to `$publicRoutes` in `tests/Feature/RouteProtectionTest.php`

## Phase 4: User Story 2 - Start using Monica or get an API key (Priority: P2)

**Goal**: the two hero buttons lead to registration and to API key creation.

**Independent Test**: spec US2.

- [X] T007 [US2] In `resources/views/app/docs/index.blade.php`, add under the line the primary "Get started with Monica" (`chevron-right`) to `route('auth.register.new')` and the secondary "Get an API key" to `route('settings.security.index')`, drawn with `x-button` if it renders a link, otherwise with the classes the API subheader uses; add `route('auth.register.new')` to the assertion of `it_shows_the_portal_to_a_visitor_who_is_not_signed_in` in `DocsControllerTest.php`

## Phase 5: User Story 3 - Read the documentation with an assistant (Priority: P3)

**Goal**: `/llms.txt` exists per `contracts/llms-txt.md`, and the portal's note links it and the OpenAPI document.

**Independent Test**: spec US3.

- [X] T008 [US3] Add `llmsText(): string` to `app/ViewModels/Docs/DocsViewModel.php`, in English literals (R3): `# Monica`, the blockquote, `## API reference`, the complete reference line to `route('docs.api.markdown.index')`, then one line per `ApiDocsViewModel::sections()` to `route('docs.api.markdown.show', ['section' => $section->id])` titled `$section->title`
- [X] T009 [US3] Create `app/Http/Controllers/App/Docs/LlmsTextController.php` with `index(): Response` returning `llmsText()` with `Content-Type: text/plain; charset=UTF-8`; add `Route::get('llms.txt', [LlmsTextController::class, 'index'])->name('llmsText.index');` in `routes/web.php`
- [X] T010 [US3] In `resources/views/app/docs/index.blade.php`, add the note below the cards: the heading "Reading this with an assistant?" in `text-card-title`, and the sentence linking `route('llmsText.index')` (shown as `/llms.txt` in mono) and `route('docs.api.openapi')` ("OpenAPI spec")
- [X] T011 [P] [US3] In `DocsViewModelTest.php`, add `it_lists_every_page_of_the_reference_in_the_text_file_for_assistants` (contains `route('docs.api.markdown.index')` and each section's Markdown route) and `it_writes_the_text_file_for_assistants_in_english_whatever_the_locale` (`app()->setLocale('fr_FR')`, still contains `## API reference`)
- [X] T012 [P] [US3] Create `tests/Feature/Controllers/App/Docs/LlmsTextControllerTest.php`: `it_serves_the_text_file_for_assistants_to_anybody` (200, `text/plain; charset=UTF-8`, body starts with `# Monica`)
- [X] T013 [US3] Add `'llmsText.index'` to `$publicRoutes` in `tests/Feature/RouteProtectionTest.php`

## Phase 6: Polish

- [X] T014 Run `php artisan monica:localize`, translate every reported string in every configured locale following `.claude/rules/translations.md` and its glossary, then `php artisan monica:localize --check`
- [X] T015 Run `php artisan test --compact tests/Unit/ViewModels/Docs tests/Feature/Controllers/App/Docs tests/Feature/RouteProtectionTest.php`, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [X] T016 Render `/docs` in a throwaway test (no browser tools) and compare it to `Docs portal.dc.html`: keyboard order, one `h1`, no `href="#"`, no horizontal scroll at 320 pixels by reading the layout

## Dependencies

- T001 → T002 → T003 → T004, T005, T006; T007 after T003; T008 → T009 → T010 → T011, T012, T013; T014 after T003, T007, T010; T015 and T016 last.
- US2 and US3 edit the view US1 creates, so they follow US1. US3's route and view model can start once T001 exists.

## Parallel Example

- After T003: T004 and T005 together. After T010: T011 and T012 together.

## Implementation Strategy

MVP is US1: the page with its cards reaching the API reference. US2 and US3 each add one block to it.
