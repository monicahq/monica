---
description: "Task list for the template named on the contact edit screen"
---

# Tasks: Template named on the edit screen of a record

**Input**: Design documents from `specs/137-record-template-indicator/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, quickstart.md

**Tests**: Included. The constitution (III) makes tests a merge condition.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

None. No dependency, migration or configuration.

## Phase 2: Foundational

None.

## Phase 3: User Story 1, see which template shapes a contact while editing it (Priority: P1) MVP

**Goal**: The contact edit screen names its template in the header and links to the template builder.

**Independent Test**: Open the edit screen of a contact; "Current template" and the template name appear, and the name links to `settings.templateBuilder.index`.

### Tests for User Story 1

- [X] T001 [P] [US1] Create `tests/Unit/ViewModels/Vaults/ContactEditViewModelTest.php` (strict types, `#[Test]`, `RefreshDatabase`, The Office data) proving: `template()` returns the template the contact's published version belongs to; `templateUrl()` is `route('settings.templateBuilder.index', $template)`; `template()` is null once the template is soft deleted. The translated name of a supplied template is the `Template::name` accessor's rule and is not tested again here
- [X] T002 [P] [US1] Extend `the_edit_screen_carries_the_name_and_the_way_out` in `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php` to assert the copy "Current template", the template's name and its `settings.templateBuilder.index` URL

### Implementation for User Story 1

- [X] T003 [US1] Add `template(): ?Template` (via `$this->contact->structureVersion->structure->owner`, returning null unless it is a `Template`) and `templateUrl(): ?string` to `app/ViewModels/Vaults/ContactEditViewModel.php`
- [X] T004 [US1] In `resources/views/app/vaults/contacts/edit.blade.php`, fill the `actions` slot of `x-page-header` when `template()` is not null: `x-caption` "Current template" (with an `id`), under it a bordered link (`focus-ring`, `layers` icon, template name, muted `arrow-right` icon marked decorative) to `templateUrl()`, carrying `aria-describedby` pointing at the caption
- [X] T005 [US1] Add "Current template" to every locale with `php artisan monica:localize`, translations written by Sonnet subagents per the translations skill, in `lang/*.json`

## Phase 4: Polish

- [X] T006 Run `vendor/bin/pint --dirty --format agent`, PHPStan, `php artisan monica:localize --check`, and `composer test:parallel`
- [X] T007 Write the changelog entry per the `changelog` skill and run the `definition-of-done` skill

## Dependencies

- T001 and T002 first (they fail), then T003, then T004, then T005, then T006 and T007.
- User Story 2 (entities) was removed by clarification; nothing depends on it.

## Parallel Example

```text
T001 and T002 touch different files and can be written together.
```

## Implementation Strategy

User Story 1 is the whole feature and the MVP. Ship it after Phase 4.
