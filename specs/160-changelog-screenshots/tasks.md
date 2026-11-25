# Tasks: Screenshots in What's new

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [quickstart.md](quickstart.md). Tests are required by constitution principle III.

## Phase 1: Setup

- [X] T001 Create `public/changelog/.gitkeep` so the screenshot folder exists before the first image
- [X] T002 [P] Generate tiny WebP fixtures with `imagewebp()` once and commit them: valid ones in `tests/Fixtures/Changelog/Screenshots/Valid/` named `<id>-<n>.webp` for the ids of `tests/Fixtures/Changelog/Valid/`, and in `tests/Fixtures/Changelog/Screenshots/Invalid/` a PNG renamed `.webp`, an empty `.webp`, a file over 1 MB and an orphan (R9)

## Phase 2: Foundational

- [X] T003 Create readonly `App\Helpers\ChangelogScreenshot` in `app/Helpers/ChangelogScreenshot.php` with `position`, `description`, `file`, `width`, `height` and `url()` returning `asset("changelog/{file}")` (R2)
- [X] T004 Add `public array $screenshots = []` (`@var list<ChangelogScreenshot>`) as the last constructor argument of `app/Helpers/ChangelogEntry.php`
- [X] T005 In `app/Helpers/ChangelogEntries.php`, add the optional `screenshotDirectory` constructor argument defaulting to `public_path('changelog')`; in `read()`, collect the `screenshot_<n>` keys, sort them by `n`, and build a `ChangelogScreenshot` per key from `<id>-<n>.webp` with `getimagesize()` dimensions; carry the screenshots through the cache rows and back in `all()`; add the names and modification times of the screenshot folder's files to the cache signature (R1, R4)
- [X] T006 Add `screenshot_1` and `screenshot_2` to one entry of `tests/Fixtures/Changelog/Valid/`, and test in `tests/Unit/Helpers/ChangelogEntriesTest.php` that the entry carries both in order with their description, file and dimensions, and that an entry without keys carries `[]`

## Phase 3: User Story 1 - See what changed (P1)

- [X] T007 [US1] In `app/ViewModels/ChangelogViewModel.php`, add `'screenshots'` to each row: a list of `{url, description (translated with __()), width, height}`
- [X] T008 [P] [US1] Test the translated screenshots on a row, and `[]` for an entry without any, in `tests/Unit/ViewModels/ChangelogViewModelTest.php`
- [X] T009 [P] [US1] Add `--scrim-strong` (`rgb(9 9 11 / 0.92)`), `--on-scrim` (`#fafafa`) and `--on-scrim-muted` (`#e4e4e7`) to `:root` only, so both themes share them, and map them in `@theme`, in `resources/css/app.css` (R6)
- [X] T010 [US1] Create `resources/views/components/screenshot-gallery.blade.php` (props `screenshots`, `title`): one screenshot as a preview at its natural size with "View full screen" in its lower right corner; several as a `grid-cols-[repeat(auto-fill,minmax(180px,1fr))]` grid of previews keeping their proportions; each preview a `<button>` in `rounded-lg border border-hairline bg-card shadow-panel cursor-zoom-in`, holding `x-image` with `srcset="<url> 2x"`, half the pixel dimensions and the description as `alt`, named by `aria-labelledby` (hidden "View full screen" plus the caption), and an `aria-hidden` `<figcaption>` in `text-xs text-muted` (FR-017, R5)
- [X] T011 [US1] In `resources/views/app/changelog/_entry.blade.php`, render `x-screenshot-gallery` between the body and the feedback when the row has screenshots
- [X] T012 [US1] In `resources/views/app/changelog/_fixes.blade.php`, render `x-screenshot-gallery` as a grid in the expanded detail of a fix that has screenshots
- [X] T013 [US1] In `app/Http/Resources/ChangelogEntryResource.php`, add `attributes.screenshots` as `{position, url, description, width, height}` with the description translated, `[]` when none (R8)
- [X] T014 [P] [US1] Assert `screenshots` with its url and translated description, and `[]` on an entry without any, in `tests/Feature/Controllers/Api/ChangelogEntryControllerTest.php`
- [X] T015 [P] [US1] Document `screenshots` and its example in `resources/docs/api/40-changelog-entries.php`, and add `resources/docs/api-changelog/2026-11-25.php`, written as the `api-docs-writer` skill says
- [X] T016 [US1] In `app/Console/Commands/LocalizeCommand.php`, add each screenshot description to the keys collected from entries, and extend `it_writes_the_title_area_and_description_of_every_changelog_entry` in `tests/Unit/Console/Commands/LocalizeCommandTest.php` to assert it; that test builds `ChangelogEntries` on the Valid fixtures, so give it the Valid screenshot directory too (R7)

## Phase 4: User Story 2 - Full screen (P1)

- [X] T017 [US2] In `resources/views/components/screenshot-gallery.blade.php`, add the viewer: inline `x-data` with the list, the open index and `move(step)` wrapping; teleported to `body`; `x-trap.noscroll.inert`; `role="dialog" aria-modal="true"` labelled by the entry title and the position; `bg-scrim-strong`; top row with ":position of :total" (or "Screenshot" alone) in `text-on-scrim` and a small secondary Close button focused on open; image `object-contain rounded-lg shadow-pop` with the description as `alt`; Previous and Next buttons only when there are several; `aria-hidden` caption in `text-on-scrim-muted` (FR-017, R6)
- [X] T018 [US2] In the same component, close on Escape, on Close and on a click on the backdrop but not on the image; move with the arrow keys; use `duration-(--duration-standard)` for the fade (FR-010 to FR-013)
- [X] T019 [US2] Create `tests/Feature/Views/Components/ScreenshotGalleryTest.php`: one screenshot renders the full screen label and no Previous/Next; several render Previous/Next and the position; the preview's `aria-labelledby` points at the caption; the dialog carries `role`, `aria-modal` and its label; the gallery's `x-data` hook is present

## Phase 5: User Story 3 - Add screenshots without guessing (P2)

- [X] T020 [US3] In `app/Helpers/ChangelogEntries.php`, report one sentence naming the entry file for: "`screenshot_7` or more is refused", a gap in the numbering, an empty description, a missing file, a file that is not a valid WebP image (`getimagesize()` type is not `IMAGETYPE_WEBP`), a file over 1 MB (1048576 bytes); keep such an entry without screenshots (R3)
- [X] T021 [US3] In `parse()` of `app/Helpers/ChangelogEntries.php`, report each file of the screenshot folder that no valid entry declares, naming the file; skip dotfiles, so `.gitkeep` is not reported
- [X] T022 [US3] Add one invalid entry per rule to `tests/Fixtures/Changelog/Invalid/`, point the invalid case at `tests/Fixtures/Changelog/Screenshots/Invalid/`, and update the expected problems and their count in `tests/Unit/Helpers/ChangelogEntriesTest.php`
- [X] T023 [P] [US3] In `.claude/skills/changelog/SKILL.md`, add a Screenshots section: screenshots by default for a visible change (FR-016); one unless several each show something new, and across several steps only the one showing the most value, never a confirmation screen, unless asked (FR-019); capture with Iris through `monica:screenshot-link`, `--selector '<css>' --padding 24 --scale 2 --size 720x900`, never the top bar and sidebar unless the change is there (FR-018); convert with `magick shot.png -quality 82 public/changelog/<id>-<n>.webp` without resizing; declare `screenshot_<n>`; the six and 1 MB caps; inspect before committing (R9)
- [X] T024 [P] [US3] In `.claude/skills/take-screenshot/SKILL.md`, add `public/changelog` as the second exception under Output, pointing at the changelog skill for naming and framing (FR-020)

## Phase 6: Polish

- [X] T025 By exception, decided on 2026-11-25, this feature carries no What’s new entry of its own; instead, add one Iris capture of the contact header to `changelog/2026-11-25-01M89JMB5VTPNX2AXN39C4XSZ5-contact-template.md`, following the updated `changelog` skill
- [X] T026 Run `php artisan monica:localize` and translate every reported string in every locale through sonnet subagents, as the `translations` skill says
- [X] T027 Run the `accessibility-reviewer` and `rules-reviewer` agents on the change and fix what they confirm
- [X] T028 Run the `definition-of-done` skill and the quickstart checks in [quickstart.md](quickstart.md)

## Dependencies

- Setup, then Foundational, block everything.
- US1 needs T003 to T005. US2 extends the component of T010. US3 needs T005 only and can run beside US1.
- T025 needs US1, US2 and T023 done; T026 needs T016 and every new string.

Parallel: T002 beside T001; T008, T009, T014, T015 once T007 and T013 exist; T023 and T024 at any time, since they touch only the skills.

## Implementation strategy

US1 and US2 ship together (both P1): previews without the viewer would leave small screenshots unreadable. US3 follows in the same pull request, before the first real screenshot is committed in T025.
