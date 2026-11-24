# Implementation Plan: Details of a life event on the vault timeline

**Branch**: `2026-11-24-life-event-details` | **Date**: 2026-11-24 | **Spec**: [spec.md](spec.md)

## Summary

Each entry of the vault dashboard timeline gains a **Details** button. On its first press it fetches one Blade fragment for that life event and shows it under the entry; later presses only show or hide it. A new view model assembles the panel in a fixed number of queries, and the API's life event gains the few attributes the panel shows that it did not carry. Decisions are in [research.md](research.md), the interfaces in [contracts/interfaces.md](contracts/interfaces.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (latest)
**Primary Dependencies**: Blade, Tailwind CSS, Alpine.js, Alpine Ajax
**Storage**: the application database, no schema change
**Testing**: PHPUnit, `composer test:parallel`
**Target Platform**: web application and JSON API
**Project Type**: web application (single Laravel project)
**Performance Goals**: the dashboard's query count unchanged; one panel in a fixed number of queries (research R2, R3)
**Constraints**: API additions only, no key changes meaning (research R6)
**Scale/Scope**: one route, one view model, one partial, one API resource extended, one guide section

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | Pass: nothing is written, so no action; the view model only reads |
| II. Simplicity | Pass: the changelog's `$ajax` pattern, no new JavaScript file |
| III. Tested | Pass: view model, controller, API and dashboard query count tests planned |
| IV. Laravel conventions | Pass: `show` on the existing controller, one named route, route model binding |
| V. Localized | Pass: new copy through `__()` and `trans_choice()`, translated by Sonnet subagents |
| VI. Nothing changes under anybody's feet | Pass: the entry keeps everything it shows; the API only gains keys |
| VII. Stable core | Pass: the type's own fields are shown through the existing formatter |
| VIII. Nothing disappears silently | Pass: a deleted event and a failed load are each told in a sentence |
| IX. Accessible | Pass: `aria-expanded`, `aria-controls`, `aria-busy`, focus returned on **Close**, reduced motion |
| X. Everything goes through the API | Pass: the API carries every detail the panel shows (R6) |
| XI. Short record | Pass: no data model file, since no schema changes |

No violation. Complexity Tracking is omitted.

## Project Structure

### Documentation (this feature)

```text
specs/152-life-event-details/
├── spec.md  plan.md  research.md  quickstart.md
├── contracts/interfaces.md
└── checklists/requirements.md
```

### Source Code (repository root)

```text
routes/web.php                                          one GET route
app/Http/Controllers/App/Vaults/VaultLifeEventController.php   show()
app/ViewModels/Vaults/LifeEventDetailsViewModel.php     new
app/ViewModels/Vaults/DashboardTimelineBlock.php        detailsUrl per entry
resources/views/app/vaults/_life-event-details.blade.php   new
resources/views/app/vaults/_timeline.blade.php          Details button, target, placeholder, error states
app/Http/Resources/LifeEventResource.php                attributes of R6
resources/docs/api/22-life-events.php                   new attributes
resources/docs/api-changelog/2026-11-24.php             entry
docs/portal/en/4-relationships-and-life-events/5-the-timeline.md   Details on the vault timeline
changelog/2026-11-24-<ulid>-life-event-details.md       What's new entry
lang/*.json                                             new strings in every locale

tests/Unit/ViewModels/Vaults/LifeEventDetailsViewModelTest.php   new
tests/Unit/ViewModels/Vaults/DashboardTimelineBlockTest.php      detailsUrl; query count unchanged
tests/Feature/Controllers/App/Vaults/VaultLifeEventControllerTest.php   show answers, wrong vault 404, trashed 404
tests/Feature/Controllers/Api/LifeEventControllerTest.php        new attributes
```

**Structure Decision**: the existing Laravel layout; the panel is a partial next to `_timeline.blade.php`, as the dashboard's other blocks are.
