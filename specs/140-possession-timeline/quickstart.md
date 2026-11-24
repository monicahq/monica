# Quickstart: verifying the possession timeline

Never run a migration against the local database; this feature adds none.

## Automated

```bash
php artisan test --compact tests/Unit/Helpers/PossessionTimelineTest.php tests/Unit/ViewModels/Vaults/PossessionTimelineViewModelTest.php
```

```bash
php artisan test --compact tests/Feature/Controllers/App/Vaults/PossessionTimelineControllerTest.php tests/Feature/Controllers/Api/Possessions/PossessionTimelineControllerTest.php
```

```bash
php artisan monica:localize --check
```

## By hand, at monica.test

1. Open a possession with records in several sections: Timeline is a link with a count in the column.
2. Open it: entries newest first under their year, each with its section's icon, date, title, detail, badge and amount as in the spec's table.
3. Choose a chip: only that section; reload: the choice stays.
4. Follow an entry's title: its section opens.
5. Lend the possession and return it: two entries appear. Record the loan in the provenance: no third entry.
6. A possession with nothing recorded reads "Nothing recorded in any section yet."
