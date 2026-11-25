# Quickstart: validating the dashboard tabs and the Past slice

## Automated

```bash
php artisan test --compact tests/Unit/Helpers/LifeEventTimelineTest.php
php artisan test --compact tests/Unit/ViewModels/Vaults/DashboardTimelineBlockTest.php tests/Unit/ViewModels/Vaults/VaultDashboardViewModelTest.php
php artisan test --compact tests/Feature/Controllers/Api/LifeEventControllerTest.php tests/Feature/Controllers/Api/LifeEventSliceControllerTest.php
php artisan test --compact tests/Feature/Controllers/App/Vaults/VaultControllerTest.php
php artisan monica:localize --check
composer test:parallel
```

## By hand

Use a vault with three favourites, five recently opened contacts, one life event after today, and seven before today spread over two years.

| Step | Expected |
| --- | --- |
| Open the dashboard | No people panel above the bar. Four tabs with counts 8, n, 3, 5. Timeline is selected. Period reads "Past · 7 entries" |
| Open the Period choice | Upcoming, Past, then the years under "Years" |
| Pick a year, then reload | The year stays selected |
| Select "Favourite people" | Sentence, three rows with a star. "Add a life event" is hidden |
| Select "Recently viewed" | Sentence, five rows with the day each was opened |
| Arrows, Home, End on the bar | Selection moves and wraps. Only the selected tab is in the tab order |
| Vault without a Me contact | Same four tabs, no "Add a life event" |
| Width 375px | No sideways scroll, every tab label readable |
| `GET /api/vaults/{vault}/life-event-slices` | `past` second, counts matching the dashboard |
| `GET /api/vaults/{vault}/life-events?slice=past` | Life events before today, most recent first |
