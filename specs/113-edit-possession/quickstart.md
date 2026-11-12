# Quickstart: Edit a Possession

## Automated

```bash
lerd php artisan test --compact tests/Unit/Helpers/PossessionFieldValuesTest.php
lerd php artisan test --compact tests/Unit/Actions/UpdatePossessionTest.php tests/Unit/Actions/CreatePossessionTest.php tests/Unit/Actions/UpdatePossessionFieldValuesTest.php
lerd php artisan test --compact tests/Unit/ViewModels/Vaults/PossessionFormViewModelTest.php
lerd php artisan test --compact tests/Feature/Controllers/App/Vaults/PossessionControllerTest.php
lerd php artisan test --compact
lerd php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

| Layer | Proves |
| --- | --- |
| Helper | Values checked against the type; unknown field and bad value refused per field; write sets and clears |
| `UpdatePossession` | Values written and cleared; a refused value writes nothing, the name included; null leaves values; one journal entry |
| Form view model | Editing: filled from the possession, old input wins, type fixed, update action, cancel to the page |
| Controller | Edit answers; update redirects to the page with the confirmation; status, quantity and identifier unchanged; wrong vault not found; one action refusal reaches the form |

## By hand

1. Open a Vehicle with a location, tags, a declared value and Mileage. Edit is no longer dimmed.
2. Select Edit: every value is filled, the type is shown and cannot be changed.
3. Change the name, empty Registration, move it, remove a tag, untick Feature; save. The page shows
   each change and "Last edited" now.
4. Edit again, type letters in Mileage, save: the form returns with the message on Mileage and
   everything typed kept; the page shows the possession unchanged.
5. Cancel returns to the page. Keyboard only, then a screen reader: the form is fully operable.
