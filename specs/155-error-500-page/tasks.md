---
description: "Task list for the error 500 page"
---

# Tasks: Error 500 page

**Input**: Design documents from `specs/155-error-500-page/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/error-page.md](contracts/error-page.md), [quickstart.md](quickstart.md)

**Tests**: Included. Principle III of the constitution makes them a delivery condition. They follow
`.claude/rules/testing.md`: they assert copy, links, status and the accessibility contract, never a
class string or the look.

**Organization**: One phase per user story, in the spec's priority order.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: The user story the task belongs to (US1, US2, US3)

## Phase 1: Setup

No setup: no dependency, configuration, route or migration is added.

## Phase 2: Foundational

No blocking prerequisite. `x-error-page`, `x-button`, `x-icon` and the error view convention already
exist (research R1, R2).

---

## Phase 3: User Story 1, a server failure shows a Monica page (Priority: P1) MVP

**Goal**: Every page request failing on the server answers 500 with the heading, the line, the
instance name and the footer, in the reader's language, without touching the database or showing the
exception.

**Independent Test**: `php artisan test --compact tests/Feature/Views/Errors/ErrorPageTest.php` passes,
including the 500 case, the no leak case and the no database case.

### Tests for User Story 1

- [X] T001 [US1] In `tests/Feature/Views/Errors/ErrorPageTest.php`, add `'server error' => [500]` to the `failures()` data provider, so the existing `it_answers_with_a_page_carrying_the_assets_turbo_compares` case covers status 500
- [X] T002 [US1] In `tests/Feature/Views/Errors/ErrorPageTest.php`, register in `setUp()` a `web` route `failing-server` that throws `new RuntimeException('SELECT * FROM dunder_mifflin_payroll')`, and add `it_says_the_fault_is_on_the_instance_side_without_revealing_it`: `$this->get('failing-server')` asserts status 500, `assertSee('We’re sorry, something went wrong on our end.')`, `assertSee('Please try again in a moment.')` and `assertDontSee('dunder_mifflin_payroll')`. `APP_DEBUG` is already false in `phpunit.xml` (research R3)
- [X] T003 [US1] In `tests/Feature/Views/Errors/ErrorPageTest.php`, add `it_answers_without_reaching_the_database`: set `config(['database.default' => 'unreachable'])`, call `DB::purge()`, then `$this->get('failing-server')` asserts status 500 and `assertSee('We’re sorry, something went wrong on our end.')`. If the page read the database its rendering would fail and Laravel's generic screen, which lacks the heading, would answer instead (research R2)
- [X] T004 [US1] Run `php artisan test --compact tests/Feature/Views/Errors/ErrorPageTest.php` and confirm T001 to T003 fail because `errors/500` does not exist yet

### Implementation for User Story 1

- [X] T005 [US1] Create `resources/views/errors/500.blade.php` on `x-error-page`, modelled on `resources/views/errors/404.blade.php`: first line `{{-- What somebody sees when the instance fails while answering their request. --}}`, `:title="__('Server error')"`, `:heading="__('We’re sorry, something went wrong on our end.')"`, `:line="__('Please try again in a moment.')"`, and for now a single `<x-button :href="route('home.index')">{{ __('Return home') }}</x-button>` in the default slot. Never read `$exception` (FR-008). Use the typographic apostrophe `’` in the copy
- [X] T006 [US1] Run `php artisan monica:localize` to add `Server error`, `We’re sorry, something went wrong on our end.` and `Please try again in a moment.` to every `lang/*.json`, then have the missing values translated by Sonnet subagents following the `translations` skill, the glossary and the per locale typography in `.claude/rules/translations.md` (for `fr_FR`: `’`, and a U+202F before `?` and `!`). Finish with `php artisan monica:localize --check` passing
- [X] T007 [US1] Run `php artisan test --compact tests/Feature/Views/Errors/ErrorPageTest.php` and confirm every case passes

**Checkpoint**: The page answers every server failure. It already replaces the framework screen.

---

## Phase 4: User Story 2, the illustration follows the colour scheme (Priority: P1)

**Goal**: The daylight scene under a light scheme, the night scene under a dark scheme, never both.

**Independent Test**: Quickstart steps 2 and 3. No automated test: which image is visible is the look,
which `.claude/rules/testing.md` forbids asserting.

- [X] T008 [P] [US2] Crop the scene out of `/home/djaiss/Downloads/Panda Crew Repairs Error 500.png` (1448 × 1086, light) with ImageMagick into `public/images/server-error.webp`: keep the clouds, sun, mountains, panda crew and meadow, drop the logo at the top and stop above the heading (grass ends near y 745, heading starts near y 780). Target about 1370 × 600, WebP quality around 82, under 150 KB. Look at the result before keeping it (research R5)
- [X] T009 [P] [US2] Crop the scene out of `/home/djaiss/Downloads/Panda Crew Repairs Error 500(1).png` (1585 × 992, dark) the same way into `public/images/server-error-dark.webp`: keep the clouds, moon, panels, crew, cone and barrier, drop the logo and stop above the heading (near y 680). Target about 1330 × 540, under 150 KB. Look at the result before keeping it
- [X] T010 [US2] In `resources/views/errors/500.blade.php`, add `<x-slot:illustration>` after the default slot holding the two `<img>` exactly as the 404 does: `alt=""`, `width` and `height` set to the real pixel size of each file from T008 and T009 (`magick identify`), `loading="lazy"`, `decoding="async"`, `class="h-auto w-full max-w-5xl dark:hidden"` on the light one and `class="hidden h-auto w-full max-w-5xl dark:block"` on the dark one
- [X] T011 [US2] Dropped after the rules review: asserting which image file loads describes the look, which `.claude/rules/testing.md` forbids, and the 404 test asserts none. Originally: In `tests/Feature/Views/Errors/ErrorPageTest.php`, extend the T002 case with `assertSee('images/server-error.webp')` and `assertSee('images/server-error-dark.webp')` (whether the block is present, which the testing rules allow), then run the file

**Checkpoint**: The page matches both mockups in its illustration.

---

## Phase 5: User Story 3, try again or leave (Priority: P2)

**Goal**: "Try again" as the main action, which never resubmits a form; "Return home" as the secondary.

**Independent Test**: The two link cases below pass.

### Tests for User Story 3

- [X] T012 [P] [US3] In `tests/Feature/Views/Errors/ErrorPageTest.php`, add `it_offers_to_ask_for_the_same_address_again`: `$this->get('failing-server?branch=scranton')` asserts `assertSee('Try again')` and `assertSee('href="'.url('failing-server?branch=scranton').'"', escape: false)`, and `assertSee('href="'.route('home.index').'"', escape: false)`
- [X] T013 [P] [US3] In `tests/Feature/Views/Errors/ErrorPageTest.php`, register in `setUp()` a `web` `post` route `failing-submission` throwing a `RuntimeException`, and add `it_never_offers_to_submit_a_failed_form_again`: `$this->from(url('settings/account/preferences'))->post('failing-submission')` asserts status 500 and that the `Try again` link points at `url('settings/account/preferences')`. Exclude CSRF in the test with `withoutMiddleware(ValidateCsrfToken::class)` if the route needs it

### Implementation for User Story 3

- [X] T014 [P] [US3] In `resources/views/components/icon.blade.php`, add a `reload` glyph: a circular arrow drawn with the same `viewBox`, stroke width and line caps as the neighbouring glyphs (compare with `arrow-right` and `check-circle`). Rendered `aria-hidden` like every other glyph (research R6)
- [X] T015 [US3] In `resources/views/errors/500.blade.php`, replace the single button with, in this order: `<x-button :href="$retryUrl"><x-icon name="reload" /> {{ __('Try again') }}</x-button>` then `<x-button variant="secondary" :href="route('home.index')">{{ __('Return home') }}</x-button>`, wrapped in a `div` laying them out in a row that wraps on narrow screens. Compute `$retryUrl` in a `@php` block: `request()->isMethod('GET') || request()->isMethod('HEAD') ? request()->fullUrl() : url()->previous(route('home.index'))` (research R4). Check `x-button` accepts `variant="secondary"` with `href` and how it spaces an icon, by reading `resources/views/components/button/index.blade.php`
- [X] T016 [US3] Run `php artisan test --compact tests/Feature/Views/Errors/ErrorPageTest.php` and confirm every case passes

**Checkpoint**: The whole spec is built.

---

## Phase 6: Polish and cross-cutting concerns

- [X] T017 Run `vendor/bin/pint --dirty --format agent`
- [X] T018 Run the `accessibility-reviewer` agent on `resources/views/errors/500.blade.php` and the new `reload` glyph, check its evidence, and fix what it confirms (FR-010)
- [X] T019 Run the `rules-reviewer` agent on the change, check its evidence, and fix what it confirms
- [X] T020 Skipped: the changelog skill says "When you are unsure, leave it out", and none of the four existing error pages carries an entry. Originally: Write the What's new entry with the `changelog` skill: server failures now show a Monica page with a way to try again
- [X] T021 Run the quickstart by hand (`specs/155-error-500-page/quickstart.md`), with screenshots in light and dark at desktop and 375 px width through the `take-screenshot` skill
- [X] T022 Run the `definition-of-done` skill, which includes `composer test:parallel` for the whole suite (the icon component is shared)

---

## Dependencies and execution order

- US1 (T001 to T007) comes first: it creates the view the other two stories edit.
- US2 and US3 both edit `resources/views/errors/500.blade.php` (T010, T015), so their view edits run one
  after the other. Their other tasks are independent: T008, T009, T012, T013 and T014 can run in
  parallel once US1 is done.
- Polish runs last, T022 at the very end.

## Parallel example

```text
After T007:
  T008 crop the light scene      T009 crop the dark scene
  T012 retry link test           T013 failed form test
  T014 reload glyph
Then T010, then T015, then T011 and T016.
```

## Implementation strategy

- **MVP**: US1 alone. A server failure already shows a Monica page with "Return home", in every
  language, instead of the framework screen.
- **Then** US2 (the illustrations) and US3 (Try again), each shippable on its own.
