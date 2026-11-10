# Implementation Plan: One top bar, outside and inside a vault

**Branch**: `2026-11-10-vault-header` | **Date**: 2026-11-10 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/110-top-bar/spec.md`

## Summary

Design 1a becomes the top bar of the vault list, every vault screen and every settings screen.
`x-bar-links` grows into the whole right side of the bar, rendered once by the account layout and
once by the shell, so the two bars cannot drift. The theme switch and the account block leave the
navigation columns. Decisions and their reasons are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (installed major)

**Primary Dependencies**: Blade, Tailwind CSS 4, Alpine.js, Alpine Ajax

**Storage**: N/A

**Testing**: PHPUnit feature tests under `tests/Feature/Views`

**Target Platform**: The responsive web application, 375 pixels and up

**Project Type**: Web application, server rendered

**Performance Goals**: No new query. The bar already loads the unread count once per page

**Constraints**: WCAG 2.2 AA; the marketplace, admin and docs bars keep their own markup (FR-016)

**Scale/Scope**: 2 layouts, 2 navigation columns, 5 components, 1 glyph catalogue

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass. Presentation only; no action, no controller, no view model changes |
| II. Simplicity | Pass. One existing component extended, one orphaned component deleted |
| III. Tested before merged | Pass. Existing view tests rewritten to the new contract, see below |
| V. Localized | Pass. One new string, "Your account", through `monica:localize` |
| VIII. Nothing disappears silently | Pass. Every control removed from a column is still on the screen, in the bar |
| IX. Accessible | Pass by design: names, focus order and Escape in [contracts/top-bar.md](contracts/top-bar.md) |
| X. Through the API | N/A. No capability is added or removed |
| XI. Short record | `spec.md` trimmed under its cap in this step |

Re-checked after design: no change.

## Project Structure

### Documentation (this feature)

```text
specs/110-top-bar/
├── plan.md
├── research.md
├── quickstart.md
├── contracts/top-bar.md
└── tasks.md            # next step
```

No `data-model.md`: the feature stores nothing.

### Source Code (repository root)

```text
resources/views/components/
├── bar-links.blade.php          # the whole right side, in the order of FR-001 (R1, R2)
├── notification-bar.blade.php   # 32 pixel trigger, primary count (R4)
├── account-menu.blade.php       # avatar 28, chevron, "Your account" (R5)
├── icon.blade.php               # journal and bell redrawn, shop and shield added (R3)
├── vault/sidebar.blade.php      # theme switch and foot removed (R6)
├── settings/sidebar.blade.php   # theme switch and foot removed (R6)
└── sidebar-foot.blade.php       # deleted (R6)
resources/views/layouts/
├── account.blade.php            # product link at 28, then x-bar-links alone
└── shell.blade.php              # x-bar-links after the screen's actions, unchanged order
tests/Feature/Views/
├── Components/BarLinksTest.php, AccountMenuTest.php, NotificationBarTest.php, SidebarTest.php
└── Layouts/TopBarTest.php
```

**Structure Decision**: Everything lives in existing Blade components. The tests that describe the
old placement (who is signed in at the foot of the column, the way out kept behind the name) are
rewritten to the new one, not deleted: they still prove each control is on the screen exactly once.

## Complexity Tracking

None.
