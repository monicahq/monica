# Implementation Plan: Delete actions drawn as danger

**Branch**: `084-delete-action-danger-styling` | **Date**: 2026-10-25 | **Spec**: [spec.md](spec.md)

## Summary

A new `x-delete-section` component draws every section that deletes the screen's own record as a red
panel holding a solid red button ([R1, R2](research.md)). The template, entity type and relationship
type screens move their delete button out of the form's foot into such a section ([R3](research.md)),
and refused buttons stay red but dimmed ([R4](research.md)). `x-delete-action` loses a class clash and
its ghost variant turns red at rest; five other row and part-of-screen buttons follow
([R5, R6](research.md)). No new copy, no PHP, no route.

## Technical Context

| | |
| --- | --- |
| Language | PHP 8.5, Laravel 13, Blade |
| Front end | Tailwind CSS 4 tokens already in `resources/css/app.css` (`error`, `error-tint`, `error-edge`); Alpine untouched |
| Storage | None |
| Testing | PHPUnit, the whole suite (shared components change); scenarios in [quickstart.md](quickstart.md) |
| Scale | 1 new component, 2 components edited, 11 views edited |

No open question remains. `data-model.md` and `contracts/` are omitted: no data, no interface.

## Constitution Check

| Principle | How it holds |
| --- | --- |
| I. Actions | No behaviour changes; no action, controller or view model is touched |
| II. Simplicity | One component, justified by the six file count in R1; no prop beyond title, icon and slots |
| III. Tested | Look only, so no new test (R8); the full suite and PHPStan run |
| IV. Laravel | Anonymous Blade component with `@props`, like every other in `resources/views/components` |
| V. Localized, safe | No string added; every deletion still opens the warning card first (FR-005) |
| VI, VII, VIII, X | Not touched: no data, no API |
| IX. Accessible | Refused buttons keep `aria-disabled` and `aria-describedby`; red is never the only signal (FR-010); contrast checked in both themes (quickstart) |
| XI. Short record | Every artifact under its cap: spec 120, plan, research, quickstart |

## Project Structure

```text
specs/084-delete-action-danger-styling/
├── spec.md, plan.md, research.md, quickstart.md
└── checklists/requirements.md

resources/views/components/delete-section.blade.php          # new (R1)
resources/views/components/button/index.blade.php             # tone prop, danger label token (R5)
resources/views/components/panel.blade.php, resources/css/app.css   # --danger-text (R5)
resources/views/components/delete-action.blade.php            # passes tone (R5)
resources/views/app/settings/account/templates/_inspector.blade.php   # R6
resources/views/app/vaults/contacts/_timeline.blade.php       # R6
resources/views/app/vaults/settings.blade.php                 # x-delete-section
resources/views/app/vaults/contacts/edit.blade.php            # x-delete-section, refused state, photo (R4, R6)
resources/views/app/settings/account/templates/form.blade.php # out of the foot (R3)
resources/views/app/settings/account/templates/{builder,_section,_pages}.blade.php   # R6
resources/views/app/settings/account/entity-types/{form,_delete}.blade.php           # R3, R4
resources/views/app/settings/account/relationship-types/{form,_delete,_category}.blade.php   # R3, R4, R6
resources/views/app/settings/account/life-event-types/form.blade.php                 # x-delete-section, R4
.claude/rules/components.md                                   # add x-delete-section to the table
```

**Structure Decision**: Blade only, inside the existing `resources/views` tree.

## Order

1. `x-delete-action` tint (R5): fixes the rows everywhere at once.
2. `x-delete-section` (R1), then the life event type screen, which already has the target look and
   proves the component reproduces it.
3. Vault and contact, then the three screens moving out of the form's foot (R3), then R6.
4. `php artisan view:clear`, full suite, PHPStan, Pint, then the quickstart in both themes.
