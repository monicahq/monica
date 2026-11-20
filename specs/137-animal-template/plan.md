# Implementation Plan: A second supplied animal template

**Branch**: `2026-11-19-animal-template`, stacked on the branch holding `6d12e152` | **Date**: 2026-11-19 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/137-animal-template/spec.md`

## Summary

Add a supplied template, Animal, laid out as the spec's tables, beside Basic animal in every account:
new accounts get both, existing accounts receive Animal through a one-off backfill that reuses their
Animals kind. Basic animal stays the default everywhere. The form that adds an animal lets the person
pick the template, which needs `CreateEntity` to accept a chosen version, and the API and MCP tool to
pass one. Species, Breed or mix and Date of birth are highlighted in Animal only, and the animal's page,
added by the commit this work is stacked on, shows the highlighted fields under the name.

## Technical Context

**Language/Version**: PHP 8.5, Laravel (installed major)

**Primary Dependencies**: Blade, Alpine.js, Tailwind CSS; no new dependency

**Storage**: Existing tables (`templates`, `structures`, `structure_versions`, `sections`,
`section_versions`, `fields`, `value_lists`, `entity_types`, `vault_default_templates`). No schema
change. One data migration that only dispatches jobs.

**Testing**: PHPUnit, `php artisan test --compact` on the touched files; `php artisan monica:localize --check`

**Target Platform**: Web application, queue workers on `high` and `low`

**Project Type**: Laravel web application with JSON API and MCP server

**Performance Goals**: The backfill runs on the `low` queue, one job per user, as the 133 backfill did

**Constraints**: Principle VI (no change under anybody's feet), principle VIII (nothing disappears
silently), every migration already in `main` stays untouched, never migrate the local database

**Scale/Scope**: One template, two pages, seven sections, 35 fields including children; one value
list; one header block; one template choice on a form; one API parameter

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Check | Result |
| --- | --- | --- |
| I. Actions hold the business logic | The template is written by the existing `CreateDefaultTemplate` job; which version a record may start from is decided in `CreateEntity`. The header and the choice on offer are presentation, in view models. | Pass |
| II. Simplicity | Enum cases and one per section override, following `fieldLabel()`. No new abstraction, no component extracted for a block that stands in two files. | Pass |
| III. Tested before merged | Tests listed under Project Structure, one per owning layer. | Pass |
| IV. Laravel conventions | Queued job, data migration with `up()` only, enums. | Pass |
| V. Localized | Every label is a translation key, translated through `monica:localize` by Sonnet subagents; French wording from the spec. | Pass |
| VI. Nothing changes under anybody's feet | Basic animal, its versions, its records and every vault default are untouched; Animal is only added. | Pass |
| VII. Stable core | Built only from Template, Page, Section, Field. | Pass |
| VIII. Nothing disappears silently | A move from Basic animal sets aside, never deletes, what has no home (existing behaviour). | Pass |
| IX. Accessible | The header facts reuse the contact page's markup: a list with text labels, icons hidden from assistive technology. | Pass |
| X. Through the API | Picking a template on creation reaches the API and the MCP tool through one optional parameter (R12). | Pass |
| XI. Short written record | The changelog entry is one short paragraph. | Pass |

Re-checked after Phase 1: no change.

## Project Structure

### Documentation (this feature)

```text
specs/137-animal-template/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   ├── supplied-animal-template.md
│   └── animal-page-header.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
app/
├── Enums/
│   ├── DefaultTemplateEnum.php      # Animal case: label, description, pages, entity type, vault default
│   ├── DefaultPageEnum.php          # AnimalProfile, AnimalDailyLife
│   ├── DefaultSectionEnum.php       # seven Animal sections, label overrides, isHighlighted()
│   ├── DefaultFieldEnum.php         # new field cases, kinds, value lists
│   ├── ValueListEnum.php            # NeuterStatuses
│   └── NeuterStatusEnum.php         # new: Yes, No, Unknown
├── Actions/
│   ├── CreateAccount.php            # add SupplyAnimalTemplate to the chain
│   └── CreateEntity.php             # accept a chosen version (R12)
├── Jobs/
│   ├── CreateDefaultTemplate.php    # reuse the Animals kind, per section highlight
│   └── SupplyAnimalTemplate.php     # new: the backfill for one account
├── Http/Controllers/App/Vaults/EntityController.php   # new/{template?}, create with the posted version
├── Http/Controllers/Api/EntityController.php         # optional structure_version_id
├── Mcp/Tools/CreateEntity.php                        # schema names structure_version_id
├── ViewModels/Vaults/EntityFormViewModel.php         # templates() on offer, showsTemplates()
└── ViewModels/Vaults/EntityViewModel.php             # headerFacts()

database/migrations/2026_11_19_000001_backfill_supplied_animal_template.php   # new, dispatches the job

routes/web.php                                        # optional {template?} on entities.new
resources/views/app/vaults/entities/show.blade.php   # the facts under the name
resources/views/app/vaults/entities/form.blade.php   # the template choice
resources/docs/api/…                                  # entity create parameter, API changelog

lang/*.json                                           # new keys, every locale
docs/portal/en/9-templates-and-customisation/9-profile-types.md
docs/portal/en/3-people/6-profiles.md
(changelog entry, through the changelog skill)

tests/Unit/
├── Jobs/CreateDefaultTemplateTest.php         # Animal layout, highlights, reuses the kind
├── Jobs/SupplyAnimalTemplateTest.php          # new: backfill rules
├── Actions/CreateAccountTest.php              # both supplied, Basic animal default
├── Actions/CreateEntityTest.php               # chosen version accepted and refused
├── Actions/MoveRecordToStructureVersionTest.php   # an animal moved keeps the seven concepts
└── ViewModels/Vaults/EntityViewModelTest.php  # new: header facts (the stacked commit adds none)
├── ViewModels/Vaults/EntityFormViewModelTest.php   # templates on offer
tests/Feature/Controllers/App/Vaults/EntityControllerTest.php   # new/{template}, create on it, facts
tests/Feature/Controllers/Api/EntityControllerTest.php          # structure_version_id accepted, 422
```

**Structure Decision**: The existing Laravel layout. Every file sits where its siblings already are.

### Stacked pull requests

This feature is one pull request, against the branch holding `6d12e152`. If that pull request has
not been opened yet, it is opened first against `main`, and this one against it.

## Complexity Tracking

No violation to justify.
