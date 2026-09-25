# Implementation Plan: Notes in a repeater

**Branch**: `2026-09-25-contact-notes-design` | **Date**: 2026-09-25 | **Spec**: [spec.md](spec.md)

## Summary

`ContactViewModel` recognises a repeater holding a single Long text (R1) and hands the screen its
notes already split into shown and hidden paragraphs, with their added date (R2, R3, R7). The section
view includes a new `_notes` partial for that field instead of a detail row: full width prose, a
Read more that works without scripts (R4), and an empty state whose **Add a note** opens the section
editor. Nothing is stored differently and the API does not change, so there is no `data-model.md`
and no `contracts/`.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (latest)

**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js (one `x-data` for Read more)

**Storage**: none added; reads `field_value_rows.created_at` and the Long text value

**Testing**: PHPUnit: the view model, and one screen test on the contact page

**Target Platform**: the contact page inside a vault, desktop and 390px

**Project Type**: server rendered web application

**Performance Goals**: no query added; rows and their values are already eager loaded by
`repetitions()`

**Constraints**: WCAG 2.2 AA; every string in every locale; readable without scripts

**Scale/Scope**: 1 view model edited, 1 partial added, 1 view edited, 5 strings

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | How |
| --- | --- | --- |
| I. Actions hold the business logic | Pass | No business rule; presentation in the view model |
| II. Simplicity over abstraction | Pass | One partial, one key on an existing array, no component |
| III. Tested before merged | Pass | View model test for the rules, one screen test for the copy and ARIA |
| IV. Laravel conventions | Pass | `@include` with named variables, as the views skill asks |
| V. Localized and safe | Pass | Five strings through `monica:localize`; nothing destructive |
| VI. Nothing changes under anybody’s feet | Pass | Same data read, same versions; only its drawing changes |
| VII. Stable core | Pass | Uses Template, Section, Field; no second mechanism |
| VIII. Nothing disappears silently | Pass | Blank rows were empty before; every written note is shown |
| IX. Accessible to everybody | Pass | `dt`/`dd` kept (R5), `aria-expanded`/`aria-controls`, `focus-ring`, 44px on mobile |
| X. Everything goes through the API | Pass | No capability added; the API already reads and writes rows |
| XI. The written record is short | Pass | spec 120, plan under 100, research under 80 |

Re-check after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/049-repeater-notes/
├── spec.md  plan.md  research.md  quickstart.md
├── checklists/requirements.md
└── tasks.md                                          # /speckit-tasks
```

### Source Code (repository root)

```text
app/ViewModels/Vaults/ContactViewModel.php            # notes key on fieldsIn(), R1 to R3, R7
resources/views/app/vaults/contacts/_section.blade.php  # include _notes for a note field
resources/views/app/vaults/contacts/_notes.blade.php  # new: notes, Read more, empty state
tests/Unit/ViewModels/Vaults/ContactViewModelTest.php # the rules
tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php  # the screen renders them
lang/*.json                                           # through monica:localize
```

**Structure Decision**: the existing layout. The partial sits beside `_section.blade.php`, its only
caller.

## Tests

| Layer | Proves |
| --- | --- |
| View model | a note repeater gives `notes`; a standalone Long text and a mixed repeater give `null`; blank rows are left out; paragraphs split on blank lines, two shown, rest hidden; `showsLabel` false when the label equals the section name; `addedLine` in the user’s format and time zone |
| Screen | the note copy and "Added …" render; a long note carries `aria-expanded="false"` and `aria-controls` naming the hidden block; an empty one shows "No note yet" and an Add a note link to the section’s edit address |

## Complexity Tracking

None.
