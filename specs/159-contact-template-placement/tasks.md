---
description: "Task list for Contact Template Placement"
---

# Tasks: Contact Template Placement

**Input**: `specs/159-contact-template-placement/` (spec.md, plan.md, research.md, quickstart.md)

**Tests**: included, since constitution principle III requires one for every view model change.

## Format: `[ID] [P?] [Story] Description`

No setup or foundational phase: the change reuses the existing view model, partial, icon and string.

## Phase 1: User Story 1 - See which template shapes a contact's page (P1)

**Goal**: the summary row ends with "Template <name>", linking to the template builder.

**Independent Test**: `php artisan test --compact tests/Unit/ViewModels/Vaults/ContactViewModelTest.php`

- [X] T001 [US1] Add `it_ends_the_header_with_the_template_the_page_is_built_from` to tests/Unit/ViewModels/Vaults/ContactViewModelTest.php: build a contact with the class's `version()` and `contact()` helpers, assert `end($facts)` has label `Template`, icon `layers`, the template's name as value, `reminder` null and `url` equal to `route('settings.templateBuilder.index', $template)`; then rename the template, rebuild the view model and assert the new name. Use The Office names. Run it and watch it fail
- [X] T002 [US1] In `headerFacts()` of app/ViewModels/Vaults/ContactViewModel.php, append after the Reminders entry `['icon' => 'layers', 'label' => __('Template'), 'value' => $this->contact->structureVersion->structure->owner->name, 'reminder' => null, 'url' => $this->templateUrl()]` (research R1, R2); in resources/views/app/vaults/contacts/_facts.blade.php only add the wrap rule of R5b
- [X] T003 [US1] Run tests/Unit/ViewModels/Vaults/ContactViewModelTest.php and tests/Feature/Controllers/App/Vaults/ContactControllerTest.php; both pass

**Checkpoint**: the fact renders on every contact page, the shared page is untouched (R7).

## Phase 2: Polish & Cross-Cutting Concerns

- [X] T004 [P] Write the What's new entry `changelog/2026-11-25-<ulid>-contact-template.md` following .claude/skills/changelog/SKILL.md, then run `php artisan monica:localize` and translate its strings in every locale with Sonnet subagents, per the translations skill
- [X] T005 Run `php artisan test --compact tests/Unit/Helpers/ChangelogEntriesTest.php` and `php artisan monica:localize --check`
- [X] T006 Follow the by-hand checks in specs/159-contact-template-placement/quickstart.md, including phone width and the private share link (the share link was checked by reading `SharedContactViewModel`, not in a browser)
- [X] T007 Run the `definition-of-done` skill (Pint, PHPStan, full suite) and the `rules-reviewer` and `accessibility-reviewer` agents on the diff

## Dependencies

- T001 → T002 → T003 → T006 → T007
- T004 → T005 → T007; T004 may run alongside T001 to T003 (different files)

## Parallel Example

```text
T004 (changelog and its translations) alongside T001–T003 (view model and its test)
```

## Implementation Strategy

US1 is the whole feature and the MVP: T001 to T003 deliver it; T004 to T007 make it shippable.
