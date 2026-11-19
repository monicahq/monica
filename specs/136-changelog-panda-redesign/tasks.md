# Tasks: What’s new with the panda

Design decisions are in [research.md](research.md), the files in [plan.md](plan.md).

## Phase 1: Setup

- [X] T001 Convert `/home/djaiss/Downloads/Pastel Panda Update Planner.png` to `public/images/panda-whats-new.webp` with `magick <png> -resize 360x360 -quality 85 -define webp:alpha-quality=100 public/images/panda-whats-new.webp`, and check it is under 50 KB (research R2)

## Phase 2: Foundational

- [X] T002 [P] Add a `comment` glyph to `resources/views/components/icon.blade.php`, next to `thumbs-up`, using the speech bubble path `M3 3.5h10a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H7l-3 2.5v-2.5H3a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1z` (research R5)

## Phase 3: User Story 1, the panda in the header (P1) 🎯 MVP

**Independent test**: open `/changelog` in both themes; the panda stands right of the title without a box or fringe, and at 375 pixels nothing scrolls sideways.

- [X] T003 [US1] In `resources/views/app/changelog/index.blade.php`, wrap `<x-page-header>` in a `flex items-center justify-between gap-6` row and add after it an `<img src="{{ asset('images/panda-whats-new.webp') }}" alt="" width="360" height="360" decoding="async" class="size-24 flex-none sm:size-45">` (research R3)
- [X] T004 [US1] In `tests/Feature/Controllers/App/ChangelogControllerTest.php`, assert in the existing screen test that the response contains `images/panda-whats-new.webp` (research R6)

## Phase 4: User Story 2, the compact feedback row (P2)

**Independent test**: under an entry and an expanded fix, Try it stands left and Comment, a rule and the two votes stand right; Comment opens and closes the comments; no “Do you like it?” shows.

- [X] T005 [US2] In `resources/views/app/changelog/_feedback.blade.php`, replace `<x-button.secondary>` with `<x-icon-button icon="comment" :label="$entry['commentLabel']">` keeping `aria-expanded="false"`, `aria-controls`, `x-bind:aria-expanded` and the `@click` that calls `$ajax` (research R4)
- [X] T006 [US2] In the same file, wrap the comment button, an `<span class="mx-1 h-5 w-px bg-hairline" aria-hidden="true"></span>` rule and the vote group in one `ml-auto flex items-center gap-1.5`, keeping the vote group’s `id="changelog-vote-…"`, `role="group"` and `aria-label` unchanged
- [X] T007 [US2] In the same file, make the live span `sr-only` and print nothing while `$entry['vote'] === null`, and `__('Thanks, noted')` once a vote exists, so “Do you like it?” is no longer used
- [X] T008 [US2] In `tests/Feature/Controllers/App/ChangelogControllerTest.php`, assert the screen with feedback allowed does not contain `Do you like it?` (research R6)

## Phase 5: Polish

- [X] T009 Write a What’s new entry under `changelog/` following `.claude/skills/changelog/SKILL.md`. Skipped on purpose: the changelog skill gives no entry for a styling change, see the report
- [X] T010 Run `php artisan monica:localize`, confirm `Do you like it?` is removed from `lang/*.json`, and translate the changelog entry strings in every locale with Sonnet subagents, per the translations skill
- [X] T011 Run `vendor/bin/pint --dirty --format agent` and `php artisan test --compact tests/Feature/Controllers/App tests/Unit/ViewModels/ChangelogViewModelTest.php tests/Feature/Views/Components`
- [X] T012 Run `composer test:parallel`, since `x-icon` is shared
- [X] T013 Walk the verification list of `plan.md` in the browser (light, dark, 375 pixels, keyboard), with screenshots from the `take-screenshot` skill
- [X] T014 Run the `definition-of-done` skill

## Dependencies

- T001 before T003; T002 before T005.
- US1 (T003, T004) and US2 (T005 to T008) touch different views and can run in either order; T004 and T008 edit the same test file, so not in parallel.
- Polish after both stories. T010 after T007 and T009.
- MVP: Phase 1 and Phase 3 alone ship the panda; US2 follows.
