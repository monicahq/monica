# Implementation Plan: What’s new with the panda

**Branch**: `2026-11-19-changelog-panda-redesign` | **Date**: 2026-11-19 | **Spec**: [spec.md](spec.md)

## Summary

Two Blade changes on the What’s new screen: the panda illustration beside the page header, and a
compact feedback row where the comment control becomes an icon button next to the votes and the
visible “Do you like it?” prompt goes away. One WebP file, one new icon glyph, no data, route or
API change. The decisions behind each choice are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel, Blade, Tailwind CSS 4, Alpine.js and Alpine Ajax

**Primary Dependencies**: none added. ImageMagick (`magick`) on the developer machine for the one off conversion

**Storage**: two static files, `public/images/panda-whats-new.webp` and its dark theme version

**Testing**: PHPUnit. Existing `ChangelogControllerTest`, `ChangelogVoteControllerTest`, `ChangelogViewModelTest`

**Target Platform**: the web application, light and dark themes, phone to desktop

**Project Type**: web application (server rendered)

**Performance Goals**: the illustration stays under 50 KB (SC-002)

**Constraints**: WCAG 2.2 AA (constitution IX); the vote group keeps its `id`, since a vote answers with `back()` and Alpine Ajax swaps that element by id

**Scale/Scope**: one screen, three Blade files, one component file

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. No logic moves; views only |
| II. Simplicity over abstraction | Pass. No new component: the header wrapper stands in one file, under the three file bar |
| III. Tested before merged | Pass. Layout only change; existing tests run, one copy assertion added (research R6) |
| V. Localized | Pass. No new string; “Do you like it?” stops being used and `monica:localize` removes it |
| VI. Nothing changes under anybody’s feet | Not concerned. No structure or record changes |
| IX. Accessible to everybody | Pass, see research R3 and R4. Decorative image, named icon button, vote confirmation kept for screen readers |
| X. Everything goes through the API | Not concerned. No capability added; the changelog API is untouched |
| XI. The written record is short | Pass. `data-model.md`, `contracts/` and `quickstart.md` omitted: there is no data, no interface, and the verification fits below |

Re-checked after design: no change.

## Project Structure

### Documentation (this feature)

```text
specs/136-changelog-panda-redesign/
├── spec.md
├── plan.md
├── research.md
├── checklists/requirements.md
└── tasks.md          (created by /speckit-tasks)
```

### Source Code (repository root)

```text
public/images/panda-whats-new.webp                  new, 360 by 360, from the supplied PNG
public/images/panda-whats-new-dark.webp             new, the dark theme version
resources/views/app/changelog/index.blade.php       header row: x-page-header and the panda side by side
resources/views/app/changelog/_feedback.blade.php   Try it left; comment icon, rule and votes right
resources/views/components/icon.blade.php           new `comment` glyph (speech bubble from the design)
tests/Feature/Controllers/App/ChangelogControllerTest.php   prompt absent, illustration present
lang/*.json                                         `monica:localize` drops the unused prompt
What’s new entry                                    per the `changelog` skill
```

**Structure Decision**: edit the existing changelog views in place. The fixes list includes
`_feedback.blade.php`, so it inherits the new row without its own change.

## Verification

1. Open `/changelog` in the light theme, then the dark theme: the panda stands right of the title, no box or fringe.
2. At 375 pixels wide: the panda shrinks, nothing scrolls sideways.
3. Tab through one entry: Try it, Comment (its name carries the count), I like this change, I do not like this change.
4. Activate Comment: the comments open, `aria-expanded` turns true; again: they close.
5. Vote: the pressed state shows, the screen reader hears “Thanks, noted”, no visible prompt anywhere.
6. Expand a fix: the same row appears.
7. `php artisan test --compact tests/Feature/Controllers/App tests/Unit/ViewModels/ChangelogViewModelTest.php`, then `composer test:parallel`.

## Complexity Tracking

None.
