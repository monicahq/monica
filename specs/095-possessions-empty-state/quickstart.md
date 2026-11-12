# Quickstart: Possessions Empty State

## Prerequisites

`php artisan migrate:fresh --seed` (the possession types migration is edited, R1), then `bun run build`.

## What each layer proves

| Layer | File | Proves |
| --- | --- | --- |
| Enum | `tests/Unit/Enums/PossessionTypeEnumTest.php` | every case has an icon in `Section::ICONS`, none is `Section::DEFAULT_ICON` |
| Actions | `tests/Unit/Actions/CreatePossessionTypeTest.php`, `UpdatePossessionTypeTest.php` | icon saved, `''` cleared, outside the catalogue refused |
| Action | `tests/Unit/Actions/CreateDefaultPossessionTypesTest.php` | defaults carry their enum icon |
| Actions | `tests/Unit/Actions/ExportPossessionTypeTest.php`, `ImportPossessionTypeTest.php` | icon written; icon read; absent gives none |
| Helper | `tests/Unit/Helpers/PossessionTypeFileReaderTest.php` | absent or null reads as none; unknown glyph is a problem |
| View models | `tests/Unit/ViewModels/Vaults/PossessionsViewModelTest.php` (new) | empty with sold possessions false; eight types by name; fields line at 0, 1, 2+; stand-in glyph; locations line at 0, 1, 3, 5 top level with total; other accounts left out |
| View model | `PossessionFormViewModelTest.php` | chosen type from the path; old input wins |
| View model | settings `PossessionTypeViewModelTest.php` | glyph options with the current one pressed |
| Web | `tests/Feature/Controllers/App/Vaults/PossessionControllerTest.php` | empty screen renders its copy; non empty keeps the placeholder; `new` with a type answers and preselects; type of another account not found |
| Web | settings `PossessionTypeControllerTest.php` | `icon` validated; set and cleared |
| API | `tests/Feature/Controllers/Api/PossessionTypes/PossessionTypeControllerTest.php`, Possessions `PossessionControllerTest.php` | 422 outside; `PUT` without icon clears; structures carry `icon` |
| Tools | `tests/Unit/Mcp/Tools/{Create,Update,Get}PossessionTypeTest.php` | icon set; update keeps it left out, clears it on null |
| Docs | `ApiDocsReturnFieldsTest` | documented fields match the resources |
| Routes | `RouteProtectionTest` | the optional bound type passes the account check |

## Run

```bash
php -d memory_limit=4G vendor/bin/phpunit --no-progress --filter Possession
php -d memory_limit=4G vendor/bin/phpunit --no-progress
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

## By hand

1. A vault with no possession: All possessions shows the empty state; light, dark, phone width.
2. Select Cars: the form opens with Cars chosen and its fields shown.
3. Settings › Possession types › Cars: pick another icon, then "No icon": the tile shows the stand-in.
4. Delete every location: the locations line disappears, "Set up locations" stays.
5. Export a type, remove `icon` from the file, import it: the import succeeds, no icon.
6. Keyboard only through every link; a screen reader skips the illustrations.
