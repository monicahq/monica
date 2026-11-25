---
description: "Task list for help bubbles"
---

# Tasks: Help bubbles

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: included; constitution principle III requires them. Layers follow `.claude/rules/testing.md`: no class string, tag or look is asserted.

## Phase 1: Setup

- [X] T001 Read `.claude/rules/components.md`, `.claude/skills/components/SKILL.md`, `.claude/skills/views/SKILL.md`, `.claude/rules/testing.md` and the current `resources/views/components/help.blade.php` before editing anything

## Phase 2: Foundational

- [X] T002 [P] Add `public static function pageForReader(string $id): ?PortalPage` to `app/ViewModels/Docs/PortalCatalogue.php`: look up `$id` in `new self(locale: app()->getLocale())`, then in `new self(locale: 'en')`, return null when neither has it (research R2)
- [X] T003 [P] Add three tests to `tests/Unit/ViewModels/Docs/PortalCatalogueTest.php`: reader locale `en` returns the `en` page; reader locale `fr_FR` (no portal folder) falls back to the `en` page; unknown id returns null

**Checkpoint**: `php artisan test --compact tests/Unit/ViewModels/Docs/PortalCatalogueTest.php` passes

## Phase 3: User Story 1, understand a setting without leaving the screen (P1) MVP

**Goal**: `x-help` drawn as design 3c with the disclosure contract of spec FR-A.

**Independent Test**: render the component with every prop and slot in `HelpTest`, then the quickstart rows on the API key form.

- [X] T004 [US1] Rewrite `resources/views/components/help.blade.php`: props `label`, `title`, `guide`, `align` ('left'); default slot is the explanation, optional `example` slot; first line comment and `@var` block per `code-style.md`
- [X] T005 [US1] In the same file, the mark: `<button type="button">` with `aria-label="{{ $label }}"`, `:aria-expanded`, `:aria-controls="$id('help')"`, glyph in `text-muted` (R7), `relative after:absolute after:-inset-1 after:content-['']` for the 24 px target (R6), `focus-ring`
- [X] T006 [US1] In the same file, the bubble: `role="group"`, `:id="$id('help')"`, `aria-labelledby` pointing at the title element, rendered right after the button; title, explanation, "For example" block (`x-caption` + text) only when the slot is filled; keep today's arrow, `align` and narrow screen sheet (R1)
- [X] T007 [US1] In the same file, the footer: resolve `guide` with `PortalCatalogue::pageForReader()`; a right aligned "Read the guide" link styled as the 3c button when it resolves, footer absent otherwise; `target="_blank" rel="noopener"` with "(opens in a new tab)" in their accessible name (FR-004, FR-A10)
- [X] T008 [US1] In the same file, the Alpine state (R5): hover opens on a fine pointer only, click toggles pinned, focus alone opens nothing, `@keydown.escape` closes and returns focus to the button when focus was inside, `@focusout` leaving the component closes an unpinned bubble, `@mousedown.outside` closes, opening dispatches `help-opened` with `$id('help')` on `window` and listening closes the others; transitions use `duration-(--duration-fast)` classes, never bare `x-transition` (R8)
- [X] T009 [US1] Migrate `resources/views/app/settings/account/security/_api-keys.blade.php:92` to `<x-help :label="__('Help about sensitive fields')" :title="__('Why leave this off?')" guide="security.api-keys">` keeping its two sentences
- [X] T010 [P] [US1] Create `tests/Feature/Views/Components/HelpTest.php` (`php artisan make:test --phpunit HelpTest`, then move): the `aria-label`, `aria-expanded` and `aria-controls` bindings; `role="group"` with `aria-labelledby`; no `role="tooltip"`; guide URL `/docs/en/your-account-and-security/your-profile` with `target="_blank"` and the new tab wording; example block only when given; an unknown guide renders the bubble without "Read the guide". Named slots after the default content (`testing.md`)
- [X] T011 [US1] Run `php artisan monica:localize`, translate every new string in every configured locale following `.claude/rules/translations.md`, then `php artisan monica:localize --check`

**Checkpoint**: `HelpTest` passes; the API key bubble behaves per quickstart

## Phase 4: User Story 2, a maintainer knows when and how to add help (P2)

**Goal**: the skill and the coverage test that keep every bubble pointed at a real page.

**Independent Test**: break a guide id in a view and watch `HelpGuideCoverageTest` fail naming it.

- [X] T012 [P] [US2] Create `tests/Unit/ViewModels/Docs/HelpGuideCoverageTest.php`: glob `resources/views/**/*.blade.php`, find every `<x-help` tag, fail when it has no `guide`, fail naming the id and the file when a `guide` is not a page of `new PortalCatalogue(locale: 'en')` (R3)
- [X] T013 [P] [US2] Create `.claude/skills/help-bubbles/SKILL.md` with frontmatter `name` and `description` (triggers: a form, a setting or a concept added or changed); body as in plan.md, section "The skill"; no dashes as punctuation
- [X] T014 [P] [US2] Add an `x-help` row to the component table in `.claude/rules/components.md` ("A question mark opening a short explanation and the way to its guide") and point it at the new skill

**Checkpoint**: coverage test passes on the two call sites and fails when one id is changed to `account.nothing`

## Phase 5: User Story 3, the profile screen explains what is not obvious (P3)

**Goal**: one bubble beside the Email address section title.

**Independent Test**: open `https://monica.test/settings/account/profile` and run the quickstart table.

- [X] T015 [US3] Add an optional `help` slot to `resources/views/components/page-section.blade.php`, rendered right after the `<h2>` and outside it, with `@var` entry (R9)
- [X] T016 [P] [US3] Add one test to `tests/Feature/Views/Components/PageSectionTest.php`: the `help` slot content renders and the heading text stays only the title
- [X] T017 [US3] Add the bubble to the Email address section of `resources/views/app/settings/account/profile/index.blade.php` with label, title, guide `account.profile` and the explanation from plan.md, section "Call sites"
- [X] T018 [US3] Run `php artisan monica:localize` and translate the new profile strings in every locale, then `--check`

**Checkpoint**: profile screen shows the bubble; coverage test still passes

## Phase 6: Polish

- [X] T019 Run the whole suite, `php artisan test --compact` (shared `x-page-section` and `x-help` changed); rewrite any feature test that asserted the old `x-help` markup to assert behaviour instead
- [X] T020 [P] Run `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [X] T021 Run quickstart.md by hand on `https://monica.test`, with screenshots through the `take-screenshot` skill, light and dark, 320 px and desktop
- [X] T022 Record the feature in docs/FEATURES.md with the feature-documentator skill (always the last task)

## Phase 7: Guide sections (added after review)

- [X] T023 Accept `guide="page.id#section"` in `resources/views/components/help.blade.php`, appending the section to the page URL
- [X] T024 [P] Add a section case to `tests/Feature/Views/Components/HelpTest.php` and make `tests/Unit/ViewModels/Docs/HelpGuideCoverageTest.php` fail on a section missing from the `en` page
- [X] T025 Point `resources/views/app/settings/account/profile/index.blade.php` at `account.profile#your-email-address` and `_api-keys.blade.php` at `security.api-keys#create-a-key`; describe sections in `.claude/skills/help-bubbles/SKILL.md`

## Dependencies

- T001 → everything. T002 → T007, T010, T012. T004 → T005 → T006 → T007 → T008 (one file, in order).
- US1 (T004 to T011) → US3 (T017 needs the component). US2 depends only on T002 and on at least one call site (T009).
- T015 → T016, T017. Polish after all stories.

## Parallel Example

```text
After T002: T003, T010, T012, T013, T014 touch different files and run together.
After T008: T009 and T015 run together.
```

## Implementation Strategy

MVP is Phases 1 to 3: the rewritten component on the API key form. US2 then locks guides down, US3 adds the profile bubble. Commit after each phase.
