# Quickstart: Possession Conditions

## Prerequisites

`php artisan migrate`, then sign in to an account.

## What each layer proves

| Layer | File | Proves |
| --- | --- | --- |
| Actions | `tests/Unit/Actions/{Create,Update,Destroy}PossessionConditionTest.php`, `UpdatePossessionConditionPositionTest.php`, `CreateDefaultPossessionConditionsTest.php` | appended position; sanitising; empty and duplicate names refused (case ignored, own name excluded); icon outside the catalogue refused; null icon; splice and renumber, past the end goes last; deletion closes the gap; another account refused; the six defaults in order, colours and locale; nothing added when one exists; journal entries |
| Job | `tests/Unit/Jobs/SeedPossessionConditionsTest.php` | the account receives the six |
| View model | `tests/Unit/ViewModels/Settings/Account/PossessionConditions/PossessionConditionsViewModelTest.php` | order by position, first and last flags, count line at 0, 1, many, colours without neutral, glyphs |
| Web controllers | `tests/Feature/Controllers/App/Settings/Account/PossessionConditions/*Test.php` | each route answers; inline validation; one action refusal reaching the form; flashes; `moved` and `direction`; the screen's contract |
| API | `tests/Feature/Controllers/Api/PossessionConditions/*Test.php` | shape, order, another account's left out, 404, 422s, position |
| Tools | `tests/Unit/Mcp/Tools/*PossessionCondition*Test.php` | each tool answers; unknown condition; update keeps what is left out |
| Docs | `ApiDocsControllerTest` 188→194, `McpDocsViewModelTest` 124→130, `ApiDocsReturnFieldsTest`, `ApiDocsViewModelTest`, `PortalCatalogueTest` | the new pages are listed |

## Run

```bash
php -d memory_limit=4G vendor/bin/phpunit --no-progress --filter PossessionCondition
php -d memory_limit=4G vendor/bin/phpunit --no-progress
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

## By hand

1. A fresh account (`php artisan migrate:fresh --seed`) shows New, Like new, Used, Worn, Damaged,
   Broken under Settings › Possessions › Possession conditions.
2. Add "Mint" in teal with the star; it lands last. Move it up five times from the keyboard; focus
   stays on its Move up button, and it ends first.
3. Edit "Worn": rename "Well loved", give it the heart, save; then remove the icon.
4. Delete "Damaged"; positions close up.
5. Light and dark, phone width, 200% zoom: the pickers open over the panel's edge without clipping.
