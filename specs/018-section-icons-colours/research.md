# Research: Section Icons and Colours

Everything here was checked against the working tree. The contrast figures were computed, not assumed.

## The measured decision: which foreground and background each hue gets

`resources/views/components/avatar-initials.blade.php` already maps these eight hues to a per theme
foreground over the hue at 12%, for exactly this reason. Reuse it, lifted onto `VaultColourEnum` so
PHP can read the values, because SC-002 asks for contrast measured rather than asserted and Tailwind
class strings cannot be measured.

Measured over the four grounds a section mark can sit on (canvas and card, light and dark), that
table's worst pair is **Blue at 4.54:1**, clearing both the 3:1 an icon needs and the 4.5:1 for text.
Neutral, being ink on card, measures 19.06:1 light and 16.97:1 dark.

Two alternatives were computed and both fail:

| Alternative | Result |
| --- | --- |
| The design's own `color-mix(in oklab, hue 8%, white)` | Glyph contrast is fine (3.79:1 to 7.77:1), but `.dark` never redefines `--identity-*`, so the tile stays near white while every other surface inverts. A white sticker on a black page. |
| Compositing the tile over the page, keeping the raw hue as the glyph | Inverts correctly, then fails AA: Rust 2.79:1, Violet 2.93:1, Slate 2.15:1. |

Lifting the foreground per theme is the step that cannot be skipped, and the avatar table is where it
has already been taken.

## The rest

| # | Decision | Why | Rejected |
| --- | --- | --- | --- |
| 1 | A nullable `colour` on `sections`, holding one of the eight hexes `VaultColourEnum` backs. Null is neutral. | FR-002 wants nine choices and FR-007 makes "no colour" draw neutral, so the ninth choice and the absence are one state. Hex matches what a vault and an entity type store. | A nine case enum (restates eight hues the database holds); a foreground column beside a background one (FR-003 forbids free pairing, so two columns are two ways to be wrong). |
| 2 | `Section::ICONS`, 35 names, declared as `EntityType::ICONS` is. The 13 missing glyphs go in the `$paths` map of `icon.blade.php`. | That component's docblock says a glyph that does not exist there has to be drawn there, with no font or third party pack. The design supplies all 13 on the 16px grid at 1.4 stroke, so they are transcribed. | Drawing them anywhere else. |
| 3 | Validate icon against `Section::ICONS` and colour against `VaultColourEnum::values()`, in the controller and in `StructureFileReader::section()`. | FR-009 says wherever the value arrives from. That method checks only that the icon is a 64 character identifier, while `entityType()` on the same class already checks membership. | Checking hex shape only, which is what the entity type path settles for. |
| 4 | The design's second arrangement: a name field beside one tile that opens a popover holding the glyph grid and the swatch row. | The design describes it as suiting an inline block under a list of sections where height is expensive, and `builder.blade.php:157` is exactly that. The first arrangement suits a settings screen that does not exist for sections. | Arrangement one. Swatch and hidden input mechanics come from `entity-types/form.blade.php`. |
| 5 | Extend the panel the builder already opens for renaming. `TemplateSectionController::update` validates three inputs and calls `UpdateTemplateVersionSection` for the heading, `UpdateSection` for the mark. | `UpdateSection` exists with no caller, so this wires up an action already written. Two actions in one method follows `create` on the same controller. | A separate route or screen for the mark. |
| 6 | An explicit `isEditable()` check on the template version in the controller. | The mark is on `Section`, so changing it never touches `template_version_sections` and never trips that model's `saving` guard. Without the check, FR-012 would pass only by accident. | Relying on the pivot guard. |
| 7 | Add the column to `2026_08_09_000002_create_sections_table.php` in place. | The migrations skill requires altering existing migrations until the product is in production, which it is not. | A new migration file. |

## One consequence worth naming

The heading is local to the template and the mark is shared by every template carrying the section, so
one panel now holds two scopes. The heading field already says "Only this template sees it", so the
mark needs the matching sentence plus the count FR-013 asks for.

The notice at `_section.blade.php:167` also needs narrowing. It currently reads that changing anything
in the section here gives it a new version, which FR-011 makes untrue of the mark.
