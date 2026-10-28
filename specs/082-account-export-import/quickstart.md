# Quickstart: validating export and import

Shapes are in [contracts/](contracts/); what lands where is in [contracts/monica-4-mapping.md](contracts/monica-4-mapping.md).

## Automated

```bash
php artisan test --compact tests/Unit/Actions --filter=Account
php artisan test --compact tests/Unit/Jobs --filter=Account
php artisan test --compact tests/Unit/Policies/AccountExportPolicyTest.php tests/Unit/Policies/AccountImportPolicyTest.php
php artisan test --compact tests/Feature/Controllers/App/Settings/Account/Transfer tests/Feature/Controllers/Api --filter=Account
```

Then the gates of `CLAUDE.md`: `php artisan monica:localize --check`, PHPStan, Pint, and the whole suite, since contact creation and the merge are shared.

## By hand, on a local instance with a queue worker

1. Settings, Export and import, pick a Monica 4 export (a real one stays outside the repository): the summary counts contacts, relationships and files, lists what will not come over, and nothing is in any vault yet.
2. Choose a new vault and confirm Me: progress moves, an email arrives when it ends, and the report accounts for every item.
3. Open a contact with a birthday and no year, one with a phone and an address, Me (carrying the file's Me's relationships), and a contact whose gift or debt became a note.
4. Import the same file into the same vault: the screen says it was imported there before.
5. Start a third import and cancel it halfway: the vault and the storage total are back to what step 2 left.
6. On an account on the free offer, import the file: refused before writing, naming the contact cap and the number in the file.
7. Export that vault: warned first, an email when ready, the download is JSON with every file inside. Signed in as somebody else, its link is not found.
8. Import that export into an empty vault on a second instance: the same contacts, values, files, relationships and life events, on a copy of the template at the same version.
9. With `monica:import-account file.json --email=…` the same file imports from the console, printing each kind and a summary.
10. With an API key holding `sensitive-fields`, follow contracts/api.md end to end; without it, `POST exports` answers 403.
