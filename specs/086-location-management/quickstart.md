# Quickstart: Location Management

## Automated

```bash
php artisan test --compact tests/Unit/Actions/CreateLocationTest.php tests/Unit/Actions/UpdateLocationTest.php tests/Unit/Actions/DestroyLocationTest.php
```

```bash
php artisan test --compact tests/Unit/Policies/LocationPolicyTest.php tests/Unit/Rules/SingleEmojiTest.php tests/Unit/ViewModels/Settings/Account/Locations/LocationsViewModelTest.php
```

```bash
php artisan test --compact tests/Feature/Controllers/App/Settings/Account/Locations/LocationControllerTest.php tests/Feature/RouteProtectionTest.php
```

Then the gates: `php artisan monica:localize --check`, `vendor/bin/phpstan analyse --no-progress`,
`vendor/bin/pint --dirty --format agent`, and the whole suite.

## By hand, at erica.test

| Step | Expected |
| --- | --- |
| Account settings, Locations in the sidebar | Empty state suggesting a home or a building |
| Add "Dunder Mifflin" with an address, emoji 🏢 | Row with 🏢 and the address |
| "+" on that row, add "Warehouse" with no address | Title says "inside Dunder Mifflin"; the address help names the parent's address; the row says it is at the parent's address |
| Add "Shelf 3" inside Warehouse, no emoji | Default icon; parent list shows "Dunder Mifflin › Warehouse" |
| Edit Dunder Mifflin, open the parent list | Neither it nor Warehouse nor Shelf 3 is offered |
| Delete Dunder Mifflin | Control refused, reason says one location is inside it |
| Delete Shelf 3, confirm | Gone; changelog shows the deletion |
| Type "ab" as emoji | Refused with a message |
| Keyboard only, then VoiceOver | Every control reachable; levels announced; refused delete reads its reason |
| Phone width and 200% zoom | No horizontal scroll; indentation stays readable |
