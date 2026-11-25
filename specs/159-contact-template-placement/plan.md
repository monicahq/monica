# Implementation Plan: Contact Template Placement

**Branch**: `2026-11-25-contact-template-placement` | **Date**: 2026-11-25 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/159-contact-template-placement/spec.md`

## Summary

The contact page names its template as the last fact of the summary row, linking to the template
builder (design 1a). One entry is appended to `ContactViewModel::headerFacts()`; the existing
partial draws it. Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (installed major)

**Primary Dependencies**: Blade, Tailwind CSS, Alpine Ajax

**Storage**: N/A, no schema change

**Testing**: PHPUnit, unit test of the view model

**Target Platform**: web, the logged in contact page

**Project Type**: web application (monolith)

**Performance Goals**: no added query (R3)

**Constraints**: WCAG 2.2 AA; row wraps at every width

**Scale/Scope**: one view model method, one test, one changelog entry

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. Read only; no action involved |
| II. Simplicity | Pass. One array entry, no new method or component |
| III. Tested before merged | Pass. One view model test (below) |
| V. Localized | Pass. `__('Template')` already translated in every locale (R6) |
| VI. Nothing changes under anybody's feet | Pass. Display only; the contact keeps its version |
| VII. Stable core | Pass. Names the user's template, adds no fixed field |
| IX. Accessible | Pass. Existing linked fact shape: one focusable link with the visible ring, label and name read together (R4) |
| X. Through the API | Pass. `ContactResource` already returns `template_name`; nothing new to expose |
| XI. Short record | Pass. No data-model.md or contracts/: nothing to put in them |

Re-checked after design: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/159-contact-template-placement/
├── spec.md
├── plan.md
├── research.md
├── quickstart.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
app/ViewModels/Vaults/ContactViewModel.php           # headerFacts(): append the template fact
tests/Unit/ViewModels/Vaults/ContactViewModelTest.php # one test: last fact names and links the template
resources/views/app/vaults/contacts/_facts.blade.php  # read only: draws the fact unchanged
```

**Structure Decision**: the existing Laravel layout; the change lives in the view model that owns
what the screen shows (testing rules: the view model owns labels and ordering).

## What the change does

- `headerFacts()` appends `['icon' => 'layers', 'label' => __('Template'), 'value' => <template name>,
  'reminder' => null, 'url' => $this->templateUrl()]` after the Reminders entry.
- The return docblock already allows `url?: string`; it stays as is.

## Tests

- View model: the last header fact is labelled "Template", carries the template's name and links to
  `settings.templateBuilder.index` for that template. A rename shows the new name (same test, after
  updating the template).
- No feature test: the partial is unchanged and the page's rendering test already exists.
- Existing assertion `assertNull(end($facts)['reminder'])` stays true, since the new last fact has no
  reminder.

## Definition of done

- `changelog` skill: one What's new entry ("The contact page names its template").
- Translations check, PHPStan, Pint, the view model test and the contact page suite.
- No API, API documentation, MCP tool or product documentation change (principle X already met).

## Complexity Tracking

None.
