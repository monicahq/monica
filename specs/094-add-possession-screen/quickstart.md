# Quickstart: Add a Possession Screen

## Prerequisites

`php artisan migrate:fresh --seed` (the possessions migration is edited, R1), then `bun run build`.

## What each layer proves

| Layer | File | Proves |
| --- | --- | --- |
| Enum | `tests/Unit/Enums/PossessionAcquisitionEnumTest.php` | date labels, which cases take a person |
| Actions | `tests/Unit/Actions/CreatePossessionTest.php`, `UpdatePossessionTest.php` | acquisition saved; person dropped for Bought and Made; person of another vault or trashed refused; future date, negative value, value without currency refused; currency dropped without value; values written and a misfit refused with nothing saved; tags reused ignoring case, created, deduplicated, too long refused; update leaves tags when null; one journal entry |
| Action | `tests/Unit/Actions/DestroyPossessionTagTest.php` | the possessions lose the tag and survive |
| Helper | `tests/Unit/Helpers/PossessionTagNamesTest.php` | trims, drops empties, matches ignoring case |
| Model | `tests/Unit/Models/PossessionTest.php` | `acquiredFrom` is null for a trashed contact, back after restore |
| View models | `tests/Unit/ViewModels/Vaults/PossessionFormViewModelTest.php`, `PossessionTypeFieldsBlockTest.php`, `PossessionContactCandidatesViewModelTest.php`; settings `PossessionTagsViewModelTest.php` | recently used (distinct, four, newest first, other vaults left out); location paths and nearest address; default currency per locale; featured count line at 0, 1, many; old input kept; fields grouped and ordered; candidates of this vault only; per tag count |
| Web controllers | `tests/Feature/Controllers/App/Vaults/PossessionControllerTest.php`, `PossessionTypeFieldControllerTest.php`, `PossessionContactCandidateControllerTest.php` | each route answers; inline validation; a type of another account answers a field error; redirect and flash; one action refusal back on the form with input; the screen contract |
| API | `tests/Feature/Controllers/Api/Possessions/PossessionControllerTest.php` | new request fields, resource shape, full replace clears tags, contact of another vault 422 |
| Tools | `tests/Unit/Mcp/Tools/{Create,Update,Get}PossessionTest.php` | new inputs; update keeps tags left out |
| Docs | `ApiDocsReturnFieldsTest`, `PortalCatalogueTest` | the documented fields match the resource |

## Run

```bash
php -d memory_limit=4G vendor/bin/phpunit --no-progress --filter Possession
php -d memory_limit=4G vendor/bin/phpunit --no-progress
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

## By hand

1. All possessions › Add a possession. Save empty: name and type messages, nothing lost.
2. Keyboard only: open the type picker, type "veh", arrow down, Enter. Vehicle's fields appear;
   focus is back on the trigger. Pick Books: the fields change.
3. Choose "Home › Living room": Home's address shows, said to come from Home.
4. Choose "A gift": "Given by" search appears, "Received on" labels the date. Choose "Bought": it goes.
5. Tags: type "Insured", Enter (reuses "insured"), "office party", comma, Backspace removes it.
6. Save: back on All possessions with the flash; the API returns acquisition and tags.
7. Delete "insured" in settings: the confirmation says one possession carries it.
8. Light and dark, phone width, 200% zoom, a screen reader over the picker and the tag box.
