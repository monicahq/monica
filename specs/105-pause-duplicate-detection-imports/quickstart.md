# Quickstart: Pause the duplicate search while an import writes into a vault

## Automated

```bash
php artisan test --compact tests/Unit/Models/VaultTest.php tests/Unit/Models/AccountImportTest.php tests/Unit/Jobs/DetectDuplicateContactsTest.php tests/Unit/Jobs/WriteAccountImportBatchTest.php tests/Unit/Jobs/RollBackAccountImportBatchTest.php tests/Unit/Actions/RollBackAccountImportTest.php tests/Unit/Actions/WriteAccountImportItemTest.php tests/Unit/Actions/CreateContactTest.php tests/Unit/Actions/UpdateContactNameTest.php tests/Unit/Actions/UpdateContactNameRepresentationsTest.php tests/Feature/Controllers/App/Vaults/ContactDuplicateSearchControllerTest.php
```

Then the checks every change passes: `php artisan monica:localize --check`,
`vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`.

## By hand

Run a worker on both queues, `php artisan queue:work --queue=default,low`, and keep
`php artisan queue:failed` at hand.

| Scenario | Do | Expect |
| --- | --- | --- |
| US1 | Export a vault holding Dwight Schrute and Dwight K. Schrute, then import it into a new vault | No `DetectDuplicateContacts` runs while the import writes; one runs after it completes; the duplicates screen proposes the pair |
| US1, other vault | During that import, rename a contact in another vault | Its search runs at once |
| US2 | Import into an existing vault and cancel it halfway | One search of that vault after the rollback; `queue:failed` lists nothing new |
| US2, new vault | Import into a new vault and cancel it halfway | No search for the destroyed vault |
| US3 | During an import, choose **Look for duplicates** in its vault | The message says the search waits for the import; nothing is queued |
| US3, nightly | During an import, run `php artisan schedule:test` and pick the duplicate pass | Every vault but the imported one is searched |

The spec's acceptance scenarios and SC-001 to SC-004 are the criteria; this table only says where to
look for them.
