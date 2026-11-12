# Quickstart: Possession Detail

## Tests, by the layer that owns each rule

| Test | Proves |
| --- | --- |
| `tests/Unit/Actions/UpdatePossessionFeaturedTest.php` | Features last in order, unfeatures and closes the order, other account not found, logged |
| `tests/Unit/Helpers/FieldValueFormatterTest.php` | Each possession field kind written for the user: number, date, yes or no, rating, select |
| `tests/Unit/ViewModels/Vaults/PossessionViewModelTest.php` | Header place with and without "at", "No location", groups in type order, empty fields left out, facts present and absent, deleted giver not named, value or "none recorded", ten sections with nine unavailable |
| `tests/Feature/Views/Components/ButtonSplitTest.php` | `unavailable` marks the main part `aria-disabled` with its reason; the menu still opens |
| `tests/Feature/Controllers/App/Vaults/PossessionControllerTest.php` | Show renders the name; destroy redirects with the message; listing links to the page |
| `tests/Feature/Controllers/App/Vaults/PossessionFeaturedControllerTest.php` | Update features and unfeatures, redirects back with a message |

Another vault or account answering not found is proven once by `ResolvesModelInVault` and
`RouteProtectionTest`.

```bash
php artisan test --compact --filter='Possession|ButtonSplit|FieldValueFormatter'
php artisan monica:localize && php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

## By hand

1. Seed a featured possession at Home › Living room › Display cabinet, with a description, tags,
   two field groups partly filled, a rating, a date, a declared value in EUR, inherited from a contact.
2. Open it from All possessions: header, "kept in Display cabinet at Home", star, tags, Overview.
3. Tab through the sections list: Overview current, the nine others announced as unavailable.
4. Open the Edit menu by keyboard, remove from featured, then delete: warning card, back to the
   listing with the message.
5. Delete the contact it came from: the From fact disappears.
6. Light and dark, `fr_FR`, 375 px wide, 200% zoom.
