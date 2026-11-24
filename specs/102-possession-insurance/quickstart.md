# Quickstart: Possession Insurance

Each layer proves its own rules (`.claude/rules/testing.md`). Run the narrowest file, then the suite.

| Test | Proves |
| --- | --- |
| `Unit/Enums/PossessionInsuranceStatusEnumTest` | label, tone, icon and position per status |
| `Unit/Models/PossessionTest` | current record: latest active start, undated last, ties by id, none |
| `Unit/Actions/CreatePossessionInsuranceRecordTest` | writes, journals, refuses another account, a negative value, an end before the start |
| `Unit/Actions/UpdatePossessionInsuranceRecordTest` | changes what is sent, journals, refuses another account |
| `Unit/Actions/DestroyPossessionInsuranceRecordTest` | deletes, journals, refuses another account |
| `Unit/ViewModels/Vaults/PossessionInsuranceViewModelTest` | summary with and without a current record, gap and warning (same and other currency), renewal wording, period, list order, delete wording |
| `Unit/ViewModels/Vaults/PossessionViewModelTest` | Insurance under Transactions with its URL and count; the "Insured" fact |
| `Feature/Controllers/App/Vaults/PossessionInsuranceRecordControllerTest` | index, edit, create, update, destroy; validation; wrong possession 404 |
| `Feature/Controllers/Api/Possessions/PossessionInsuranceRecordControllerTest` | JSON shape, 201/204, 422, another possession 404, list leaves out others |
| `Unit/Mcp/Tools/*PossessionInsuranceRecord*Test` | each tool reaches its action |

```bash
php artisan test --compact tests/Unit/Actions/CreatePossessionInsuranceRecordTest.php   # one file at a time: --filter loads the whole suite
php artisan monica:localize && php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

## By hand, at monica.test

1. Open a possession: Insurance sits under Transactions. Select it: "Not insured", empty list.
2. Add an active Sabre record for less than the declared value: the summary, the warning and the
   sections count follow; the Overview reads "Insured".
3. Add a pending record: it lists after the active one. Edit it, then delete it; "Keep it" keeps it.
4. Check the page by keyboard alone, in light and dark, in `fr_FR`, at 375 px and at 200%.
