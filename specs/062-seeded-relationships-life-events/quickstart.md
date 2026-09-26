# Quickstart: Relationships and life events in the example account

## Run

```bash
php artisan migrate:fresh --seed --quiet
```

Then sign in as `monica.geller@centralperk.example`, password `password`.

## Check by hand

| Open | Expect |
| --- | --- |
| Chandler | Spouse Monica (current). Parent of Jack and Erica Bing. Child of Nora and Helena Bing. Janice (ended) |
| Ross | Sibling Monica. Parent of Ben and Emma. Three ended marriages. Partner Rachel (current) |
| Mr. Heckles (archived) | Neighbour of four people. Twenty events or more, all dated no later than 1995 |
| Any timeline | Twenty events or more, in several categories |

## Check automatically

```bash
php artisan test --compact tests/Unit/Seeders/ExampleAccountSeederTest.php
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```
