# Quickstart: Immediate family block

How to see the block working and how to prove it. Rules live in [spec.md](spec.md), decisions in [research.md](research.md).

## Prerequisites

A working local install with a seeded account, and the assets built.

```bash
composer install
bun install && bun run build
php artisan migrate:fresh --seed
```

## See it

1. Sign in and open any vault, then any person.
2. On their record, use **Add a relationship** and record a **Parent / Child** link with the person on screen as the parent. Repeat for a second child.
3. Record a **Spouse** link to a third person, leaving the state as current.
4. Record a second **Spouse** link to a fourth person and end it.
5. Reload the record. The right-hand column shows **Immediate family**: two names under Children, one under Current partner, and nobody for the ended one.
6. Open a person with no relationships. The same block explains what would land there and offers the way to record one.

To see an age, give one of the children a **Birthday** with a year on their own record. Give another a birthday with the year marked unknown: no age appears for them.

## Prove it

```bash
php artisan test --compact tests/Unit/Models/ContactTest.php
php artisan test --compact tests/Unit/ViewModels/Vaults/ImmediateFamilyBlockTest.php
php artisan test --compact tests/Feature/Views/Vaults/ImmediateFamilyTest.php
php artisan test --compact tests/Feature/Controllers/App/Vaults/ContactControllerTest.php
```

Then the whole suite, because the overview is a shared screen:

```bash
php artisan test --compact
```

## Before it is done

```bash
php artisan monica:localize          # translate everything it reports, in every locale
php artisan monica:localize --check  # counted phrases and per-locale typography
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
```

## Check the query count

The block must not cost more queries as it lists more people. Open a record with two children and one partner, then one with twenty children, and compare. Both should show the same count: one per list, plus one for every age at once.

```bash
php artisan tinker --execute 'DB::enableQueryLog();'
```

Read the log through the debug bar or `DB::getQueryLog()` in a test asserting the count directly.
