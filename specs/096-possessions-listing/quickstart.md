# Quickstart: Possessions Listing

Shapes are in [contracts/screen-and-api.md](contracts/screen-and-api.md).

## Tests, by the layer that owns each rule

| Test | Proves |
| --- | --- |
| `tests/Unit/Helpers/PossessionValueFormatterTest.php` | Symbol by locale, separators by user, dash, one amount per currency |
| `tests/Unit/ViewModels/Vaults/PossessionsViewModelTest.php` | Summary, selection, inherited address, parts and "No specific spot", featured order, others by name, place, Lent, other vault left out, "No location" |
| `tests/Unit/ViewModels/Vaults/PossessionLocationTreeBlockTest.php` | Order, depth, counts with nested locations, zero, colour per top level, current node |
| `tests/Feature/Controllers/App/Vaults/PossessionControllerTest.php` | Index renders the listing copy, not "Possessions are coming" |
| `tests/Feature/Controllers/App/Vaults/PossessionLocationControllerTest.php` | Renders for a location; another account's location is not found |
| `tests/Feature/Controllers/Api/Possessions/PossessionControllerTest.php` | `location` filter, nested included, foreign uuid 422 |
| `tests/Unit/Mcp/Tools/ListPossessionsTest.php` | `location` argument |
| `tests/Feature/Views/Components/SidebarTest.php` | Count shown above zero, absent at zero |

```bash
php artisan test --compact --filter='Possession'
php artisan monica:localize && php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

## By hand

1. Seed a vault with fifteen possessions, three featured, one Lent, two without value, one in USD,
   under Home › Garage, Home › Living room › Display cabinet and a storage unit.
2. Open All possessions: summary, "Featured here", "Everything else", USD total apart from EUR.
3. Select Garage: tree marks it, address reads "at the address of Home", list titled "Kept at Garage".
4. Reload, then go back: selection follows the address.
5. Tab through the tree and the page; zoom to 200%; switch to `fr_FR`, then to a right to left locale.
6. Empty a vault: the 095 empty state shows.
