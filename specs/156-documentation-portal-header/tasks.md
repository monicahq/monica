# Tasks: Documentation portal header

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/header.md](contracts/header.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III; layers per `.claude/rules/testing.md`.

## Phase 1: Setup

- [X] T001 Add `marketing_url` (env `MONICA_MARKETING_URL`, default `https://www.monicahq.com`) in config/monica.php
- [X] T002 [P] Add `--color-identity-1`, `-2`, `-3`, `-5`, `-6`, `-7`, `--text-lede-sm` and `--ease-pop` to `@theme` with the marketing values (research R1) in resources/css/app.css
- [X] T003 [P] Add the marketing `code` glyph to the paths table in resources/views/components/icon.blade.php

## Phase 2: Foundational

- [X] T004 Create `MarketingSite` with `locale()`, `url()` and `handbookArticle()` per contracts/header.md, the locale map of research R3 and the 24 page slugs copied from the marketing `config.php` routes, throwing on an unknown page, in app/Helpers/MarketingSite.php
- [X] T005 [P] Test the locale map (fr_FR to fr, pt_PT and pt_BR to pt, ja_JP to en), a localised slug (`pricing` in fr is `/fr/tarifs/`), `home`, a handbook article and the unknown page exception in tests/Unit/Helpers/MarketingSiteTest.php

## Phase 3: User Story 1, the portal wears the marketing header (P1) MVP

**Independent test**: quickstart steps 1 and 2.

- [X] T006 [US1] Rewrite the bar from the marketing site-header (mark and wordmark, `<nav aria-label="{{ __('Main') }}">`, Pricing, Blog, Sales, GitHub mark and `__(':figure stars, Monica on GitHub')` label with figure `24k+`, theme button per research R6, Sign in as `__('Log in')` per R1, Get started as `x-button` reading `__('Sign up')`, the marketing English), utilities mapped per research R1, `focus-ring` on every control, `print:hidden`, in resources/views/components/docs/header.blade.php
- [X] T007 [US1] Assert the landmark, the Pricing, Blog and Sales destinations from `MarketingSite`, and the GitHub label containing `24k+` in tests/Feature/Views/Components/Docs/HeaderTest.php

## Phase 4: User Story 2, the two menus (P1)

**Independent test**: quickstart steps 3, 4 and 6.

- [X] T008 [P] [US2] Write the Alpine `headerMenu` component (Escape closes and focuses the `<summary>`, click outside or on a link closes, mouse only open on rest with 250ms close, closes the other open menu, closes on `turbo:before-cache`) per research R5 in resources/js/header-menu.js
- [X] T009 [US2] Import and register `headerMenu` in resources/js/app.js
- [X] T010 [US2] Add the Features menu from the marketing site-header (three columns, the strip of spaces, the MCP card) after Features, links from `MarketingSite`, in resources/views/components/docs/header.blade.php
- [X] T011 [US2] Add the Docs menu from the marketing docs-menu after Blog, with `aria-current="page"` on its `<summary>` and destinations per research R2, in resources/views/components/docs/header.blade.php
- [X] T012 [US2] Assert `x-data="headerMenu"` on both menus, `aria-current="page"` only on the Docs `<summary>`, and the portal links (`docs.index`, `docs.portal.index` en, `docs.api.index`) in tests/Feature/Views/Components/Docs/HeaderTest.php

## Phase 5: User Story 3, signing in stays on this instance (P2)

**Independent test**: quickstart step 5.

- [X] T013 [US3] Branch the actions: a visitor gets Sign in to `auth.login.new` and Get started to `auth.register.new` only while `config('monica.registration_enabled')`; a signed in reader gets one `x-button` to `home.index` labelled `__('Dashboard')`, in resources/views/components/docs/header.blade.php
- [X] T014 [US3] Assert the three cases (visitor, registration closed, signed in) in tests/Feature/Views/Components/Docs/HeaderTest.php

## Phase 6: User Story 4, the reader's language (P2)

**Independent test**: quickstart step 4.

- [X] T015 [US4] Run `php artisan monica:localize` to add the new phrases to every file in lang/
- [X] T016 [US4] Fill the new phrases with the marketing `nav` wording for fr_FR, de_DE, es_ES, pt_BR, nl_NL, zh_CN and ru_RU, leaving phrases the files already translate untouched (research R4), in lang/*.json
- [X] T017 [US4] Translate the remaining locales through Sonnet subagents per the translations skill, in lang/*.json
- [X] T018 [US4] Assert that under the fr_FR locale Pricing leads to `/fr/tarifs/` in tests/Feature/Views/Components/Docs/HeaderTest.php

## Phase 7: Polish

- [X] T019 Write the What's new entry per the changelog skill in changelog/
- [X] T020 Run `vendor/bin/pint --dirty --format agent`, PHPStan, `php artisan monica:localize --check` and `composer test:parallel`
- [X] T021 Run the `accessibility-reviewer` and `rules-reviewer` agents on the diff and fix what they confirm
- [X] T022 Walk quickstart.md by eye with screenshots of both headers at the four widths, both themes (take-screenshot skill)
- [X] T023 Run the definition-of-done skill

## Dependencies

- T001 and T004 before T006, T010, T011; T002 and T003 before T006, T010, T011.
- US1 (T006) before US2 and US3, which edit the same file; T008 runs beside them.
- T015 after every phrase is written (T006, T010, T011, T013); T016 and T017 after T015.
- Polish last.

## Parallel examples

- T002, T003 and T005 together; T008 beside T010 and T011.

## Strategy

MVP is US1: the bar alone already replaces the poor header. US2 completes the marketing look; US3 and
US4 make it correct for every reader.
