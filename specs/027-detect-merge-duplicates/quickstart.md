# Quickstart: proving detection and merging work

## Prerequisites

This worktree has no `vendor/` and no `node_modules/`. Run `composer install` and `bun install` first.
The routes and payloads are in [contracts/duplicates-and-merges.md](contracts/duplicates-and-merges.md).

## Verification

```bash
php artisan migrate:fresh --seed --quiet
php artisan route:list --path=duplicates              # four: index and dismissal, web and api
php artisan route:list --path=merge                   # seven: four web, three api
php artisan test --compact tests/Unit/Jobs/DetectDuplicateContactsTest.php
php artisan test --compact tests/Unit/Actions/PreviewContactMergeTest.php
php artisan test --compact tests/Unit/Actions/MergeContactsTest.php
php artisan test --compact tests/Unit/Actions/UndoContactMergeTest.php
php artisan test --compact tests/Unit/Actions/DismissDuplicateCandidateTest.php
php artisan test --compact tests/Feature/Controllers/App/Vaults/ContactDuplicateControllerTest.php
php artisan test --compact tests/Feature/Controllers/App/Vaults/ContactMergeControllerTest.php
php artisan test --compact tests/Feature/Controllers/Api/ContactMergeControllerTest.php
php artisan test --compact tests/Unit/Models/ContactMergeTest.php tests/Unit/Models/ContactDuplicateCandidateTest.php
php artisan monica:localize --check                   # expected: nothing missing, nothing stale
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --memory-limit=2G
```

The suite must also pass on the other two drivers, since detection groups and compares without
driver-specific SQL:

```bash
DB_CONNECTION=mysql php artisan test --compact tests/Unit/Jobs/DetectDuplicateContactsTest.php
DB_CONNECTION=pgsql php artisan test --compact tests/Unit/Jobs/DetectDuplicateContactsTest.php
```

## By hand, on the seeded account

1. Open a vault, create "Jim Halpert" and "James Halpert", and give both the same email address.
2. Run `php artisan queue:work --once --queue=low` twice, once per contact, so detection catches up.
3. Open `vaults/{vault}/duplicates`. The pair is listed, and its reasons name both the shared address
   and the similar name.
4. Open the preview. The differing values sit side by side, each with a radio choice, and the
   confirmation warns that a contact will be absorbed.
5. Confirm. The vault lists one person, and the absorbed contact's old address lands on the survivor.
6. Undo from the survivor. Both contacts are back, each holding what it held before.
7. Dismiss the pair instead, reload, and confirm it does not return.

## What proves the accessibility criterion

Reach the preview with the keyboard alone, choose every conflict without a pointer, and confirm the
choice is announced as a radio group rather than shown by colour. Zoom to 200% and check the side by
side comparison still reads on a phone width.
