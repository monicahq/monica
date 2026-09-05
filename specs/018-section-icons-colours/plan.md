# Implementation Plan: Section Icons and Colours

**Branch**: `2026-09-04--color-section` | **Date**: 2026-09-04 | **Spec**: [spec.md](spec.md)

## Summary

Give a section a glyph and a colour, choosable when it is created and changeable afterwards, drawn
wherever the section appears. The set is closed: 35 glyphs (22 the icon component already draws, 13
transcribed from the design) and 9 colours (neutral, plus the 8 hues a vault already stores). One
colour choice supplies both foreground and background, taken from the per theme table the generated
avatar already uses, whose worst measured pair is 4.54:1. The mark lives on `Section`, beside the
`icon` column already there, so it is shared by every template carrying the section while the existing
heading override stays local. Details in [research.md](research.md).

## Technical Context

| | |
| --- | --- |
| **Language** | PHP 8.4 |
| **Dependencies** | Laravel (current major), Blade, Tailwind, Alpine.js, Alpine Ajax. None added. |
| **Storage** | One nullable `colour` column on `sections`. No other schema change. |
| **Testing** | PHPUnit. `Tests\Unit` for the actions, model, enum and view models; `Tests\Feature` for the controller and components. |
| **Target** | Server rendered web, desktop to phone, light and dark themes. |
| **Project type** | Web application, server rendered. |
| **Performance** | No new query. The mark is read from the `Section` already loaded through `sectionVersion.section`. |
| **Constraints** | WCAG 2.2 AA both themes; suite green on SQLite, MySQL and PostgreSQL; PHPStan level 5. |
| **Scope** | 3 screens, 1 migration altered, about 12 files touched. |

## Constitution Check

*GATE: passed before Phase 0, re-checked after Phase 1. No violations, so Complexity Tracking is omitted.*

| Principle | How this satisfies it |
| --- | --- |
| I, actions hold the logic | No new action. `CreateSection` and `UpdateSection` each gain a `colour`; the controller adds transport only. |
| II, simplicity | One column, one constant, one enum method. The pair table moves to where it can be read rather than being duplicated. |
| III, tested before merged | The actions, model, enum, three view models, controller and picker each get a test, including the measured contrast test SC-002 asks for. |
| IV, Laravel conventions | Existing migration altered (pre production), inline validation, camelCase route names, `#[Override]` where a parent is overridden. |
| V, localized and safe | Every new string through `__()`, then `monica:localize` and translated in all nine locales. Colour names come from `VaultColourEnum::label()`, already translatable. |
| VI, nothing changes underfoot | The mark is display only and creates no section version, so a published version renders as before. Changing a mark through a published template version is refused. |
| VII, stable core | Nothing new is attached to a record. Sections stay the one personalisation mechanism. |
| VIII, nothing disappears silently | Clearing a mark falls back to the default glyph and neutral. A glyph name the set does not draw falls back rather than rendering nothing. |
| IX, accessible to everybody | Contrast measured on all nine colours over four grounds, keyboard reachable picker, chosen state announced, mark never the only difference between two sections. |
| X, everything through the API | No sections API exists yet, so the logic sits in the actions a future controller reuses, adding no rework later. |
| XI, the written record is short | `spec.md` 120, `plan.md` 86, `research.md` 45. Three artifacts omitted, below. |

### Omitted artifacts

Principle XI requires each to be left out when another artifact already carries its content.

| Artifact | Why omitted |
| --- | --- |
| `data-model.md` | The change is one nullable column on one table, already stated in Technical Context and research decision 1. |
| `contracts/` | There is no sections API and this adds none, so there is no contract to write. |
| `quickstart.md` | The spec's acceptance scenarios are the validation script and `tasks.md` carries the commands. A third copy would go stale first. |

## Project Structure

No new folder and no new namespace. One new file, an anonymous Blade component.

| Path | Change |
| --- | --- |
| `database/migrations/2026_08_09_000002_create_sections_table.php` | The `colour` column, added in place. |
| `app/Models/Section.php` | `ICONS` (35), `DEFAULT_ICON`, `colour` on the fillable list. |
| `app/Enums/VaultColourEnum.php` | The per theme foreground and background for each hue, readable from PHP. |
| `app/Actions/CreateSection.php` | Accepts and stores `colour`. |
| `app/Actions/UpdateSection.php` | Accepts and stores `colour`. Gets its first caller. |
| `app/Http/.../Templates/TemplateSectionController.php` | `create` and `update` carry the mark. `update` guards the freeze explicitly and calls both actions. |
| `app/Helpers/StructureFileReader.php` | Refuses an icon or colour outside the set, in `section()`. |
| `app/Helpers/MarketplacePayload.php`, `StructureWriter.php` | Write and read `colour` beside `icon`. |
| `app/ViewModels/.../TemplateBuilderViewModel.php` | The mark, and the count of other templates for FR-013. |
| `app/ViewModels/Vaults/ContactViewModel.php` | Already reads `icon`; now reads `colour` too. |
| `app/ViewModels/Shared/SharedContactViewModel.php` | The mark on a shared record, which shows none today. |
| `resources/views/components/section-mark.blade.php` | **New.** The tile. Used three times in the builder: the section header, the add block trigger and the popover preview. |
| `resources/views/components/icon.blade.php` | The 13 new glyph paths. |
| `resources/views/components/panel.blade.php` | Its mark accepts a tint instead of always `bg-card text-body`. Both record surfaces already draw their section through this component, so it is where they get the mark. |
| `resources/views/components/avatar-initials.blade.php` | Reads the pair table from the enum instead of holding its own copy. |
| `.../templates/builder.blade.php` | The add block gains the tile and the popover. |
| `.../templates/_section.blade.php` | The edit panel gains the mark controls, the scope sentence and the count. Its version notice is narrowed, since FR-011 makes it untrue of the mark. |
| `lang/*.json` | Nine locales, through `monica:localize`. |
| `tests/Unit`, `tests/Feature` | One file per touched class, plus the contrast test and the component test. |

**Structure Decision**: the contact record and the shared record both already draw a section through
`x-panel`, so they take the mark as a tint on the mark span that component already has. The builder
draws its own header, and needs the tile in three places, so that becomes `section-mark`. What keeps
the two from drifting, as FR-010 requires, is that both read their values from `VaultColourEnum`.
