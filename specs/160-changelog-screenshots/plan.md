# Implementation Plan: Screenshots in What's new

**Branch**: `2026-11-25-changelog-screenshots` | **Date**: 2026-11-25 | **Spec**: [spec.md](spec.md)

## Summary

An entry declares its screenshots as numbered front matter keys whose value is the description. The files are derived from the entry id (`public/changelog/<id>-<n>.webp`). `ChangelogEntries` parses them, measures them and reports every defect through the existing `problems()` check. What's new draws them with one new component, `x-screenshot-gallery`, which holds the previews and the full screen viewer. The API resource lists them. The changelog skill makes a screenshot the default for a visible change and says how to take it with Iris, framed and zoomed on what changed (R9). All decisions are in [research.md](research.md), the format in [data-model.md](data-model.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (latest), Blade, Alpine.js with its focus plugin, Tailwind CSS 4
**Primary Dependencies**: none added. `getimagesize()` reads WebP natively.
**Storage**: files only. WebP images in `public/changelog/`, no schema change.
**Testing**: PHPUnit, run with `composer test:parallel`
**Target Platform**: web application and the JSON API
**Project Type**: Laravel monolith
**Performance Goals**: no query added; images lazy loaded with their dimensions declared, so nothing shifts while they load
**Constraints**: WCAG 2.2 AA; 1 MB per file, six per entry; every new string in every shipped locale
**Scale/Scope**: one helper, one view model, one component, two views, one API resource, docs, two skills

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold business logic | Pass. Nothing is written by a request. Parsing stays in `ChangelogEntries`. |
| II. Simplicity | Pass. One value class (`ChangelogScreenshot`), one component, no JavaScript module (R6). |
| III. Tested | Pass. Parser and check, view model, API controller, component contract, localize extraction. |
| IV. Laravel conventions | Pass. |
| V. Localized | Pass. Descriptions are extracted by `monica:localize` like titles (R7). Five interface strings, none counted. |
| VI. Nothing changes under anybody's feet | Pass. An entry without screenshots renders as today. |
| VII. Stable core | Pass. The Changelog stays the same concept; an entry gains an optional part. |
| VIII. Nothing disappears silently | Pass. The changelog stays append only; adding a screenshot to a published entry adds, never removes. |
| IX. Accessible | Pass if R5 is kept. `accessibility-reviewer` runs before the commit. |
| X. Through the API | Pass. `screenshots` on the changelog entry resource, its docs and an API changelog entry (R8). No MCP tool reads the changelog today, so none changes. |
| XI. Short record | Spec at 120 lines after trimming. Plan, research, data model and quickstart under their caps. `contracts/` omitted: the API shape is in data-model.md. |

Re-check after design: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/160-changelog-screenshots/
├── spec.md
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/Helpers/ChangelogScreenshot.php                  # new value class (R2)
app/Helpers/ChangelogEntry.php                       # gains list<ChangelogScreenshot> $screenshots
app/Helpers/ChangelogEntries.php                     # parse, measure, check, cache signature (R1, R3, R4)
app/ViewModels/ChangelogViewModel.php                # 'screenshots' on each row
app/Http/Resources/ChangelogEntryResource.php        # 'screenshots' attribute (R8)
app/Console/Commands/LocalizeCommand.php             # extracts descriptions (R7)
resources/css/app.css                                # scrim-strong tokens (R6)
resources/views/components/screenshot-gallery.blade.php   # previews and viewer (R5, R6)
resources/views/app/changelog/_entry.blade.php       # gallery under the body
resources/views/app/changelog/_fixes.blade.php       # gallery in the expanded fix
resources/docs/api/40-changelog-entries.php          # documents the attribute
resources/docs/api-changelog/2026-11-25.php          # API changelog entry
.claude/skills/changelog/SKILL.md                    # when to add screenshots, how to capture, convert, declare (R9)
.claude/skills/take-screenshot/SKILL.md              # public/changelog as a second committed exception (R9)
public/changelog/.gitkeep                            # the folder exists before the first image
lang/*.json                                          # via monica:localize
changelog/<new entry>.md                             # the feature's own What's new entry, with screenshots

tests/Unit/Helpers/ChangelogEntriesTest.php
tests/Fixtures/Changelog/{Valid,Invalid}/ and Screenshots/{Valid,Invalid}/
tests/Unit/ViewModels/ChangelogViewModelTest.php
tests/Feature/Controllers/Api/ChangelogEntryControllerTest.php
tests/Feature/Views/Components/ScreenshotGalleryTest.php
```

**Structure Decision**: existing Laravel layout. The gallery is a component rather than a partial because it carries a dialog contract and is used from two views (`.claude/rules/components.md`, the second file exception).

## Complexity Tracking

None.
