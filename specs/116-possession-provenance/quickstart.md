# Quickstart: Possession Provenance

Each layer proves its own rules (`.claude/rules/testing.md`). Run the narrowest file, then the suite.

| Test | Proves |
| --- | --- |
| `Unit/Enums/PossessionProvenanceEventTypeEnumTest` | label, icon and group per type |
| `Unit/Enums/PossessionProvenanceDatePrecisionEnumTest` | label and help per precision |
| `Unit/Actions/CreatePossessionProvenanceEventTest` | writes, normalises each precision, journals; refuses another account, a date not matching its precision, a malformed link, another possession's transaction |
| `Unit/Actions/UpdatePossessionProvenanceEventTest` | updates, clears the date for Unknown, journals; same refusals |
| `Unit/Actions/DestroyPossessionProvenanceEventTest` | deletes, keeps the linked transaction, journals, refuses another account |
| `Unit/Actions/DestroyPossessionTransactionTest` | a linked event survives with its link cleared |
| `Unit/ViewModels/Vaults/PossessionProvenanceViewModelTest` | chain order (unknown last, ties), dates by precision, title fallback, who line, origin, owners (distinct, ordered), verified line, filter counts, empty states, transaction options and link, delete wording |
| `Unit/ViewModels/Vaults/PossessionViewModelTest` | Provenance has its URL and count |
| `Feature/Controllers/App/Vaults/PossessionProvenanceEventControllerTest` | index, create, edit, update, destroy; inline validation; wrong possession 404 |
| `Feature/Controllers/Api/Possessions/PossessionProvenanceEventControllerTest` | JSON shape, 201/200/204, 422, another possession 404, list leaves out others |
| `Unit/Mcp/Tools/*PossessionProvenanceEvent*Test` | each tool reaches its action |

```bash
php artisan test --compact tests/Unit/Actions/CreatePossessionProvenanceEventTest.php
php artisan monica:localize && php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

## By hand, at monica.test

1. Open a possession with two transactions and select Provenance: empty state, count absent.
2. Add an Origin around 1921, unverified: the summary names the place and "not verified".
3. Add an Acquisition on an exact date, linked to the purchase, verified: it sorts after the origin,
   shows the transaction total and leads to Transactions; the owner chain names the "to".
4. Add an event with an unknown date: it goes last. Switch the precision: the date field changes and
   empties.
5. Filter by Custody with none: "Nothing of this kind yet". Edit an event; delete one, the warning
   says the linked purchase stays.
6. Delete the linked transaction: the event stays, unlinked.
7. Check the page by keyboard alone, in light and dark, in `fr_FR`, at 375 px and at 200%.
