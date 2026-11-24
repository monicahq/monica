# Quickstart: Possession Valuations

Each layer proves its own rules (`.claude/rules/testing.md`). Run the narrowest file, then the suite.

| Test | Proves |
| --- | --- |
| `Unit/Enums/PossessionValuationTypeEnumTest` | label and icon per type |
| `Unit/Models/PossessionTest` | current valuation: latest date, ties by id, none; current value falls back to the declared value, then null |
| `Unit/Actions/CreatePossessionValuationTest` | writes, journals, refuses another account, a negative amount, a malformed link |
| `Unit/Actions/DestroyPossessionValuationTest` | deletes, detaches its documents, journals, refuses another account |
| `Unit/Actions/{Attach,Detach}PossessionValuationDocumentTest` | attaches and detaches, storage counted, journals, refuses another account |
| `Unit/ViewModels/Vaults/PossessionValuationViewModelTest` | summary (one, several, other currency, none), insured gap, history order and changes, preview sentences, delete wording |
| `Unit/ViewModels/Vaults/PossessionViewModelTest` | Valuations has its URL and count; the value panel reads the valuation, else the declared value |
| `Unit/ViewModels/Vaults/PossessionInsuranceViewModelTest` | worth and warning read the current valuation, with its wording; declared value wording kept without one |
| `Feature/Controllers/App/Vaults/PossessionValuationControllerTest` | index, create with and without a file, destroy; validation; wrong possession 404 |
| `Feature/Controllers/Api/Possessions/PossessionValuationControllerTest` | JSON shape, 201/204, 422, another possession 404, list leaves out others |
| `Feature/Controllers/Api/Possessions/PossessionValuationDocumentControllerTest` | upload 201, detach 204, flag off 404 |
| `Unit/Mcp/Tools/*PossessionValuation*Test` | each tool reaches its action |

```bash
php artisan test --compact tests/Unit/Actions/CreatePossessionValuationTest.php
php artisan monica:localize && php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

## By hand, at monica.test

1. Open a possession with a declared value: the Overview reads it. Select Valuations: empty state.
2. Record an appraisal with a PDF: the preview line, the summary, the count and the Overview follow;
   Insurance now compares against it and says "current value".
3. Record an older own estimate: it goes into the history, the current value stays. Record one in
   another currency: no change is computed.
4. Delete the current one: the warning names the value that takes over; "Keep it" keeps it.
5. Use "Record a valuation" from the Edit menu: the form opens on the Valuations section.
6. Check the page by keyboard alone, in light and dark, in `fr_FR`, at 375 px and at 200%.
