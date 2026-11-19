# Implementation Plan: Template named on the edit screen of a record

**Branch**: `2026-11-19-changelog-panda-redesign` | **Date**: 2026-11-19 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/137-record-template-indicator/spec.md`

## Summary

The contact edit screen names the template the contact renders from, in the `actions` slot of the
existing `x-page-header`: the caption "Current template" above a link to the template builder.
`ContactEditViewModel` gains two methods, `template()` and `templateUrl()`. No model, migration,
action, route or API change.

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: Blade, Tailwind CSS, existing components `x-page-header`, `x-caption`, `x-icon`

**Storage**: N/A. The template is reached through `contact → structureVersion → structure → owner`.

**Testing**: PHPUnit. One view model test, one feature assertion on the existing edit screen test.

**Target Platform**: Web

**Project Type**: Laravel web application

**Performance Goals**: Two extra relation loads on one screen. Negligible.

**Constraints**: WCAG 2.2 AA, no horizontal scroll at 320px, every string translated.

**Scale/Scope**: One screen, one view model, one string.

## Constitution Check

| Principle | Result |
| --- | --- |
| I. Actions hold the business logic | Pass. Nothing is written; reading for a screen belongs to the view model. |
| II. Simplicity | Pass. Reuses `x-page-header`'s `actions` slot rather than a new component. |
| III. Tested before merged | Pass. View model test and one feature assertion. |
| V. Localized | Pass. "Current template" goes through `__()` into every locale. |
| VI. Nothing changes under anybody's feet | Pass. Read only. |
| IX. Accessible | Pass. A real link with a visible focus ring and a name that says where it leads. |
| X. Everything goes through the API | Pass. `ContactResource` already returns `template_name`. No new capability. |

Re-evaluated after design: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/137-record-template-indicator/
├── spec.md
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
└── tasks.md
```

### Source Code (repository root)

```text
app/ViewModels/Vaults/ContactEditViewModel.php        # template(), templateUrl()
resources/views/app/vaults/contacts/edit.blade.php    # actions slot of x-page-header
lang/*.json                                           # "Current template"
tests/Unit/ViewModels/Vaults/ContactEditViewModelTest.php   # new
tests/Feature/Controllers/App/Vaults/ContactControllerTest.php  # one assertion added
```

**Structure Decision**: Existing view model and view only. No new component: the block stands in one
file, below the three file bar of `.claude/rules/components.md`.

## Complexity Tracking

None.
