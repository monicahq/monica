# Quickstart: Possession Tags

## Automated

```bash
php -d memory_limit=2G vendor/bin/phpunit --no-progress \
  tests/Unit/Actions/CreatePossessionTagTest.php \
  tests/Unit/Actions/UpdatePossessionTagTest.php \
  tests/Unit/Actions/DestroyPossessionTagTest.php \
  tests/Unit/ViewModels/Settings/Account/PossessionTags \
  tests/Feature/Controllers/App/Settings/Account/PossessionTags \
  tests/Feature/Controllers/Api/PossessionTags \
  tests/Unit/Mcp/Tools --filter PossessionTag
```

What each layer proves (testing rules: one rule, one layer):

| Layer | Proves |
| --- | --- |
| Actions | creating with the default colour; refusing an empty name and one taken ignoring case ("insured" beside "Insured"); renaming to a change of case of its own name; another account answering not found; the journal entry of each |
| View model | sorted by name in the reader's language; the count line at one and at several; the eight colours, Blue as default; empty |
| Settings controller | each route answers; `max:50` and the palette refused; the redirect and its flash; the screen renders "Possession tags" |
| API controller | the JSON structure; 201, 200, 204; another account's tags left out of the list; 422 on `name` |
| Tools | each tool answers and names the capability it mirrors |

Then, as for every change: `php artisan monica:localize --check`, `vendor/bin/phpstan analyse
--no-progress`, `vendor/bin/pint --dirty --format agent`, and the whole suite with
`php -d memory_limit=4G vendor/bin/phpunit --no-progress`. `RouteProtectionTest` checks the new
routes on its own.

## By hand

1. Open Settings, then Possessions, then Possession tags. The entry sits after Possession types.
2. Type "Insured", pick Teal: the preview reads "Insured" in teal. Press Enter: the tag is listed and
   the field is empty.
3. Type "insured": "You already have a tag with this name." is announced.
4. Edit "Insured", rename it "INSURED": saved. Press Escape on another row's form: nothing changes.
5. Delete it: the card names the tag; cancelling leaves it, confirming removes it.
6. With the keyboard alone: Tab reaches the name, the colours (arrow keys move between them), "Add
   tag", then each row's Edit and Delete.
7. Settings, Logs: the three entries are there.
