# Quickstart: Possession Transactions

Each layer proves its own rules (`.claude/rules/testing.md`). Run the narrowest file, then the suite.

| Test | Proves |
| --- | --- |
| `Unit/Enums/PossessionTransactionTypeEnumTest` | ownership, acquisition and counterparty label per type |
| `Unit/Enums/CurrencyEnumTest` | every case has a code and a flag |
| `Unit/Models/PossessionTransactionTest` | total entered, summed, unknown, and its source |
| `Unit/Models/PossessionTest` | acquisition: earliest, undated first, ties by id, none |
| `Unit/Actions/CreatePossessionTransactionTest` | writes, journals, refuses another account, a negative figure, a figure without currency |
| `Unit/Actions/UpdatePossessionTransactionTest` | changes what is sent, journals, refuses another account |
| `Unit/Actions/DestroyPossessionTransactionTest` | deletes, detaches every document, journals |
| `Unit/Actions/AttachPossessionTransactionDocumentTest` | stores, counts, refuses another account's file |
| `Unit/Actions/DestroyPossessionTest` | also detaches its transactions' documents |
| `Unit/ViewModels/Vaults/PossessionTransactionsViewModelTest` | summary, costs since (refund, other currency), list order, filters' counts, delete wording, preselected currency |
| `Unit/ViewModels/Vaults/PossessionViewModelTest` | sections' colours and count; facts read the acquisition transaction |
| `Unit/Migrations/ConvertPossessionAcquisitionsTest` | each of the four ways, with and without date and contact, a trashed contact |
| `Feature/Controllers/App/Vaults/PossessionTransactionControllerTest` | index, edit, create, update, destroy; validation; wrong possession 404 |
| `Feature/Controllers/Api/Possessions/PossessionTransactionControllerTest` | JSON shape, 201/204, 422, another possession 404, list leaves out others |
| `Feature/Controllers/Api/Possessions/PossessionControllerTest` | derived `acquisition`, 422 on the removed fields |
| `Feature/Views/Components/CurrencyPickerTest` | listbox roles, `aria-selected`, accessible names, hidden input |
| `Unit/Mcp/Tools/*PossessionTransaction*Test` | each tool reaches its action |

```bash
php artisan test --compact tests/Unit/Actions --filter=PossessionTransaction
php artisan monica:localize && php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

## By hand, at erica.test

1. Open a possession, select Transactions: summary reads "Unknown", list is empty.
2. Add a purchase dated 14 March 2005 with $40, $3.20 tax, $6 shipping: the line reads $49.20, the
   summary names it the acquisition, the sections list reads 1.
3. Add a fee and a refund, then switch filters: counts and "Costs since" follow.
4. Edit the shipping, delete the fee: the warning names what follows; "Keep it" keeps it.
5. Open the currency picker by keyboard only: arrows move, Enter chooses, Escape closes.
6. Check the sections list colours in light and dark, the page in `fr_FR`, at 375 px and at 200%.
