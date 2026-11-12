# Quickstart: Possession Locations

Each layer proves its own rules (`.claude/rules/testing.md`). Run the narrowest file, then the suite.

| Test | Proves |
| --- | --- |
| `Unit/Actions/MovePossessionTest` | closes the open stay, opens the new one, writes the current location, journals; works with no open stay; refuses another account's location, the same location, a day before the open stay began |
| `Unit/Actions/UpdatePossessionLocationStayTest` | changes one stay only; the open stay's location moves the current location; refuses a past stay without `left_on`, `left_on` before `arrived_on`, `left_on` on the open stay |
| `Unit/Actions/DestroyPossessionLocationStayTest` | deletes; the open one clears the current location; journals; refuses another account |
| `Unit/Actions/CreatePossessionTest`, `UpdatePossessionTest` | a location opens a stay today; a change moves; clearing closes; no change leaves stays alone |
| `Unit/Policies/LocationPolicyTest` | a location with a stay is refused with its count; one with none is allowed |
| `Unit/Models/PossessionTest` | `locationStays()` order: unknown first, ties by id; `openLocationStay()` |
| `Unit/ViewModels/Vaults/PossessionLocationsViewModelTest` | current block, empty state, newest first, periods, lengths, gap lines, count line, delete wording, location options as paths |
| `Unit/ViewModels/Vaults/PossessionViewModelTest` | Locations has its URL and count |
| `Feature/Controllers/App/Vaults/PossessionLocationStayControllerTest` | index, create, edit, update, destroy; one refusal reaches the form; wrong possession 404 |
| `Feature/Controllers/Api/Possessions/PossessionLocationStayControllerTest` | JSON shape, 201/200/204, 422, list leaves out other possessions |
| `Unit/Mcp/Tools/*PossessionLocationStay*Test`, `MovePossessionTest` | each tool reaches its action |

```bash
php artisan test --compact tests/Unit/Actions/MovePossessionTest.php
php artisan monica:localize && php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

## By hand, at erica.test

1. A possession added with a location: Locations shows one open stay since today, count 1.
2. Move it with a reason: the preview names both stays; the old one closes, "Here now" moves, the
   possessions list counts it under the new location.
3. Move it to where it is, or before the open stay began: the error sits beside the field.
4. Correct a past stay so a gap appears: the gap line reads between them.
5. Delete the open stay: the empty state shows; the list no longer places it anywhere.
6. Try to delete a location in Settings that a stay refers to: refused with the count.
7. Check by keyboard alone, in light and dark, in `fr_FR`, at 375 px and at 200%.
