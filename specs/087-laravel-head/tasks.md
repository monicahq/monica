# Tasks: Document head metadata through Laravel Head

Design: [plan.md](plan.md), [research.md](research.md), [contracts/head.md](contracts/head.md), [quickstart.md](quickstart.md). Each task writes its test in the layer `.claude/rules/testing.md` assigns, and the test must pass before the task is done.

## Phase 1: Setup

- [X] T001 Save the rendered `<head>` of one screen per layout (`shell`, `account`, `admin`, `guest`, `marketplace`) and of `/docs/en` to the scratchpad, as the baseline T005 compares against (SC-003)
- [X] T002 `composer require laravel/head` and confirm with `composer show laravel/head` that it resolved against Laravel 13.35 (R1); `composer.json`, `composer.lock`

## Phase 2: Foundational

- [X] T003 Add `configureHead()` to `app/Providers/AppServiceProvider.php`, called from `boot()`: `Head::defaults()` with `title(config('app.name'), exact: true)`, `robots('noindex')`, `og(siteName: config('app.name'), type: OgType::Website)`; `Head::errors()` with `defaults(robots: 'noindex')` (R3)
- [X] T004 In `resources/views/partials/meta.blade.php`, replace the `<title>` and description tags with an `@php` block calling `Head::title($title, exact: true)` only when `$title` is set and `Head::description($description)` only when it is not empty, then `@head`; keep every other tag where it is (R2)
- [X] T005 Render the T001 screens again and diff each head against the baseline: same `<title>`, no second title, icon or manifest written by `@head`; remove a duplicate from the defaults rather than from the partial

## Phase 3: User Story 1, private pages stay out of search engines (P1) MVP

Independent test: open a private link, its password form, a sign in screen, a vault screen and a 404, and read each robots tag.

- [X] T006 [US1] Add `->withHead(robots: 'noindex, nofollow')` to `share.show` and `share.password.create` in `routes/web.php` (R5)
- [X] T007 [P] [US1] Add `search_engine_indexing` to `config/monica.php`, `(bool) env('MONICA_SEARCH_ENGINE_INDEXING', true)`, with a comment block written like `registration_enabled`; add the variable to `.env.example` (R6)
- [X] T008 [US1] Create `app/Http/Middleware/MarkPageSearchable.php`: when `config('monica.search_engine_indexing')` is true, `Head::searchableByRobots()`; alias it `searchable` in `bootstrap/app.php` (R4)
- [X] T009 [US1] Put `searchable` on every HTML docs route (`docs.index`, `docs.mcp.index`, `docs.carddav.index`, `docs.api.index`, `docs.api.show`, `docs.portal.index`, `docs.portal.show`) and on the `flag:marketplace` group in `routes/web.php`; leave the `.md` and OpenAPI routes alone (FR-008)
- [X] T010 [US1] `tests/Feature/HeadMetadataTest.php`: a `#[DataProvider]` over a vault screen, settings, an admin screen, the sign in screen, a private link, its password form, a 404 under `/docs`, the docs index, an API section, a guide page and the marketplace, asserting the robots value in contracts/head.md; and the public routes answering `noindex` once `monica.search_engine_indexing` is false (SC-001)

## Phase 4: User Story 2, public pages describe themselves (P2)

Independent test: open each public page and read its title, description, canonical and `og:` tags.

- [X] T011 [US2] In `app/Http/Middleware/MarkPageSearchable.php`, add `Head::canonical($request->url(), forceHttps: false)` inside the same condition (R4); in `HeadMetadataTest`, a query string never reaches the canonical, an http request keeps http, and no canonical with the setting off
- [X] T012 [P] [US2] Pass a `description` to `partials.meta` in `resources/views/app/docs/index.blade.php`, `carddav.blade.php`, `mcp.blade.php` (one new `__()` sentence each) and `app/docs/api/show.blade.php` (the section's existing `description`) (R7)
- [X] T013 [P] [US2] Add a `description` prop to `resources/views/layouts/marketplace.blade.php` passed to `partials.meta`, and set it in `resources/views/app/marketplace/index.blade.php` with one new `__()` sentence (R7)
- [X] T014 [US2] Make `description` required in `PortalCatalogue::frontmatter()` and carry it on each page in `app/ViewModels/Docs/PortalCatalogue.php`; pass `$page->description` to `partials.meta` in `resources/views/app/docs/portal.blade.php`; tests in `tests/Unit/ViewModels/Docs/PortalCatalogueTest.php` (read, and a page without one left out)
- [X] T015 [US2] Write a one sentence `description` into the front matter of every file under `docs/portal/en/` (73 files), in the guide's plain register; T014's catalogue test that loads every shipped page must pass
- [X] T016 [US2] Withdrawn after review (R8): add the alternates to `app/ViewModels/Docs/PortalDocsViewModel.php` (pages sharing the current `id` across `PortalCatalogue::locales()`), passed to `Head::alternates()` from `portal.blade.php` only when there are two or more; test in `tests/Unit/ViewModels/Docs/PortalDocsViewModelTest.php` (FR-004, R8)
- [X] T017 [US2] In `HeadMetadataTest`, a guide page and an API section carry `og:title` and `og:description` matching their title and description (SC-002)

## Phase 5: User Story 3, one place declares the head of every page (P3)

Independent test: render one screen per layout and compare title, icons and manifest with T001.

- [X] T018 [US3] In `HeadMetadataTest`, one screen per layout keeps its `<title>`, its csrf token when a session exists, `turbo-cache-control` and the manifest link; a screen passing no title shows the application name (SC-003, SC-004)

## Phase 6: Polish

- [X] T019 [P] Correct the middleware count in `.claude/CLAUDE.md` (it says six; there are fourteen after T008) and name `searchable` among the aliases
- [X] T020 `php artisan monica:localize`, translate the five new sentences in every locale, then `--check`; `vendor/bin/phpstan analyse --no-progress`; `vendor/bin/pint --dirty --format agent`; the full suite, since `partials/meta` is under every screen
- [ ] T021 Run the manual checks in quickstart.md
- [X] T022 Record the feature in docs/FEATURES.md with the feature-documentator skill

## Dependencies

- Phase 2 needs T002; every story needs Phase 2. T005 needs T001 and T004.
- T008 needs T007; T009 needs T008; T010 needs T006 and T009.
- T011 needs T008. T015 needs T014. T016 needs T014. T017 needs T011 to T015.
- US3 needs only Phase 2; it can run beside US1.
- Parallel: T007 beside T006; T012 and T013 beside each other and beside T014.
- Ship order: US1 alone already stops private pages being indexed; US2 and US3 follow in the same pull request.
