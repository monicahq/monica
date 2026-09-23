# Quickstart: Contact list columns

The table is new, so rebuild the database: `php artisan migrate:fresh --seed`. A database that predates
the change cannot be patched (migrations skill).

## Prove it with the tests

```bash
php artisan test --compact tests/Unit/Actions/UpdateContactListColumnsTest.php
php artisan test --compact tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php
php artisan test --compact tests/Unit/ViewModels/Vaults/ContactListCellsTest.php
php artisan test --compact tests/Unit/ViewModels/Vaults/ContactListColumnsViewModelTest.php
php artisan test --compact tests/Unit/Enums/ContactListColumnSourceEnumTest.php
php artisan test --compact tests/Feature/Controllers/App/Vaults
php artisan test --compact tests/Feature/Controllers/Api/ContactListColumnControllerTest.php
php artisan test --compact tests/Feature/RouteProtectionTest.php
```

Then the full suite, since `ContactsViewModel` loses its letter headings and several feature tests
read them.

| Test | Proves |
| --- | --- |
| Action | a set is replaced, a repeated source is refused, another account's vault and section are refused, a field no template carries cannot be added but survives a resave, one log entry |
| `ContactListCells` | Michael's two emails come first-then-rest, Dwight on another template gets no Company, a detached value is left out, email gathers every email field whatever the section |
| `ContactsViewModel` | the defaults with no rows, favourites first in Everybody with its headings, a page opening mid-group repeats no heading, a hidden column is not a heading |
| `ContactListColumnsViewModel` | the two menu groups, template badges, sensitive, layout and file types left out, email, phone and birthday left out, a column no template supplies marked |
| Web controller | the edit screen renders, update redirects with the flash, validation refuses a field column with no key, a wrong vault answers 404 |
| API controller | the `GET` and `PUT` structure, 422 on a refusal, and the contact list `columns` array |

## Look at it

Open People in a seeded vault. Then open Customise view, add a field, rename it, move it with the
keyboard, save, and check the column on People. Screenshots go through Iris (`take-screenshot`
skill), never the browser tools.

## Finish

`php artisan monica:localize`, then translate what it reports, then `php artisan monica:localize --check`.
Then `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`.
