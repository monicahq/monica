# Quickstart: Possession Maintenance

Each layer proves its own rules (`.claude/rules/testing.md`). Run the narrowest file, then the suite.

| Test | Proves |
| --- | --- |
| `Unit/Enums/PossessionMaintenanceTypeEnumTest` | label and colour per type, no two types sharing a colour |
| `Unit/Models/PossessionTest` | records ordered by date then id; overdue: latest only, today not overdue, none without a date |
| `Unit/Actions/CreatePossessionMaintenanceRecordTest` | writes, journals; refuses another account, another account's condition, a negative cost, a cost without currency, a future date, a next due before the job |
| `Unit/Actions/UpdatePossessionMaintenanceRecordTest` | changes what is sent, journals, refuses another account |
| `Unit/Actions/DestroyPossessionMaintenanceRecordTest` | deletes, journals, refuses another account |
| `Unit/ViewModels/Vaults/PossessionMaintenanceViewModelTest` | card values and their empty wordings, condition wording, overdue chip, empty list, delete wording |
| `Unit/ViewModels/Vaults/PossessionViewModelTest` | Maintenance keeps its place, gains its URL and count |
| `Unit/ViewModels/Settings/Account/PossessionConditions/PossessionConditionsViewModelTest` | the delete warning counts the maintenance records naming a condition, silent at zero |
| `Feature/Controllers/App/Vaults/PossessionMaintenanceRecordControllerTest` | index, edit, create, update, destroy; validation; wrong possession 404 |
| `Feature/Controllers/Api/Possessions/PossessionMaintenanceRecordControllerTest` | JSON shape, 201/204, 422, another possession 404, list leaves out others |
| `Unit/Mcp/Tools/*PossessionMaintenanceRecord*Test` | each tool reaches its action |

```bash
php artisan test --compact tests/Unit/Actions/CreatePossessionMaintenanceRecordTest.php
php artisan monica:localize && php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

## By hand, at erica.test

1. Open a possession and select Maintenance: its own address, the empty list, the help mark.
2. Add a Restoration from Worn to Like new with a next due date already passed: the card matches the
   design, reads "Overdue", and the sections count follows.
3. Add a later Inspection: the first card loses "Overdue". Edit it, then delete it; "Keep it" keeps it.
4. Delete the condition "Worn" in settings: the warning counts the record; the card then reads
   "Not recorded → Like new".
5. Check by keyboard alone, in light and dark, in `fr_FR`, at 375 px and at 200%.
