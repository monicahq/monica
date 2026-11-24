# Quickstart: Details of a life event on the vault timeline

## Prerequisites

- A local instance with the example account (`php artisan migrate:fresh --seed`), front end built with `bun run build`.

## Validate

1. Open the dashboard of the Personal vault. Press **Details** on an entry: a placeholder shows, then the panel with the type, date, place, fields, people and footer (spec User Story 1).
2. Press **Hide details**, then **Details** again: the panel reappears with no new request in the browser's network tab (FR-003).
3. Delete that life event in another tab, then open the details of a not yet opened entry for it: the panel says it no longer exists (FR-010).
4. Use the keyboard only: Tab to **Details**, Enter, Tab through the panel, Enter on **Close**: focus is back on **Details** (FR-012).
5. Read the event through the API (`GET /api/v1/vaults/{vault}/life-events/{lifeEvent}`) and check the additions listed in [contracts/interfaces.md](contracts/interfaces.md).

## Tests

```bash
php artisan test --compact tests/Unit/ViewModels/Vaults/LifeEventDetailsViewModelTest.php tests/Unit/ViewModels/Vaults/DashboardTimelineBlockTest.php tests/Feature/Controllers/App/Vaults/VaultLifeEventControllerTest.php tests/Feature/Controllers/Api/LifeEventControllerTest.php
composer test:parallel
```
