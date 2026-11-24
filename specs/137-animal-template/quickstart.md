# Quickstart: verifying the Animal template

## Prerequisites

- On branch `2026-11-19-animal-template`, stacked on the branch holding `6d12e152`.
- Never run a migration against the local database. The backfill migration is proven by the job's
  test, not by running it here.

## Automated checks

```bash
php artisan test --compact tests/Unit/Jobs/CreateDefaultTemplateTest.php tests/Unit/Jobs/SupplyAnimalTemplateTest.php
```

```bash
php artisan test --compact tests/Unit/Actions/CreateAccountTest.php tests/Unit/Actions/CreateEntityTest.php tests/Unit/Actions/MoveRecordToStructureVersionTest.php
```

```bash
php artisan test --compact tests/Unit/ViewModels/Vaults/EntityViewModelTest.php tests/Feature/Controllers/App/Vaults/EntityControllerTest.php
```

```bash
php artisan monica:localize --check
```

Expected: every test passes, and the localisation check reports nothing.

## By hand, at monica.test

1. Sign up a new account. Settings, Templates: Basic animal and Animal are both listed. Open Animal:
   two pages, seven sections, as in [data-model.md](data-model.md).
2. Add an animal: the form offers Basic animal (current) and Animal. Pick Animal, give a name only: it saves on Animal.
3. Add one with species, breed and a date of birth without a year. Open it: the three show under the
   name, the date without a year.
4. Switch the account to French: every label of Animal reads as in the spec's tables.
5. In an account that existed before, check that Basic animal and its animals look as before, and
   that a new vault still starts animals on Basic animal.

## Covered elsewhere

- That the backfill leaves vault defaults and values alone, reruns safely and skips refusals:
  `SupplyAnimalTemplateTest`.
- That an animal moved from Basic animal keeps the seven shared concepts and sets the rest aside:
  `MoveRecordToStructureVersionTest`. No screen moves an animal yet.
