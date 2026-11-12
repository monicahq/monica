# Quickstart: Possession Loans

## Tests

```bash
php artisan test --compact tests/Unit/Enums/PossessionLoan*
php artisan test --compact tests/Unit/Actions --filter=PossessionLoan
php artisan test --compact tests/Unit/Actions/MergeContactsTest.php tests/Unit/Actions/UndoContactMergeTest.php
php artisan test --compact tests/Unit/ViewModels --filter='PossessionLoans|ContactLoansBlock|PossessionViewModel'
php artisan test --compact tests/Feature/Controllers --filter=PossessionLoan
php artisan test --compact tests/Unit/Mcp --filter=PossessionLoan
php artisan test --compact tests/Feature/RouteProtectionTest.php
```

| Layer | Proves |
| --- | --- |
| Actions | Account and vault checks, the single active loan, date rules, the contact created with the loan (and rolled back with it), provenance events kept in step, journal lines |
| Merge and undo | Loans follow the kept contact and come back on undo |
| View models | Order, badges including Overdue, due lines, the summary line, empty state, "A deleted contact", contact page block |
| Web controllers | Each route answers, the redirect and flash, one refusal reaching the form, wrong vault 404, candidates list leaves other vaults out |
| API and MCP | Shape in [contracts/api.md](contracts/api.md), 422 on a second active loan, 404 across vaults |

## By hand

1. `composer run dev`, sign in as the seeded user, open a possession, select Loans.
2. Add a loan to an existing contact, by keyboard only; the active panel shows it, the count reads 1.
3. Add a second active loan: refused beside Status.
4. Mark as returned with a condition back; the panel reads "Not lent to anyone right now.".
5. Add a loan creating "Nate"; Nate appears in the vault's contacts with the loan on his page.
6. Tick "Add to the provenance"; Provenance shows two Change of custody events; delete the loan and
   both are gone.
7. Delete a contact holding a loan; the row reads "A deleted contact".

## Gates

`php artisan monica:localize --check`, `vendor/bin/phpstan analyse --no-progress`,
`vendor/bin/pint --dirty --format agent`, then the whole suite.
