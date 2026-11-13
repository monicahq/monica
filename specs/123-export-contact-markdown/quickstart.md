# Quickstart: Export a contact as Markdown

## Run the tests

```bash
php artisan test --compact tests/Unit/ViewModels/Vaults/ContactMarkdownViewModelTest.php \
  tests/Unit/Actions/ExportContactMarkdownTest.php \
  tests/Unit/Mcp/Tools/GetContactMarkdownTest.php \
  tests/Feature/Controllers/App/Vaults/ContactMarkdownControllerTest.php \
  tests/Feature/Controllers/Api/ContactMarkdownControllerTest.php
php artisan test --compact tests/Feature/Mcp tests/Feature/RouteProtectionTest.php
```

## By hand

1. Open a contact holding fields, a relationship and a life event. Open **More actions**, choose **Export as Markdown**. A `{slug}.md` file downloads; it matches [the contract](contracts/contact-markdown.md).
2. Choose **Copy as Markdown**, paste into an editor: the same text. The menu announces "Copied".
3. Call the API with a key lacking `sensitive-fields`: the sensitive value is missing; with the scope, it is there.
4. Call `get-contact-markdown` from an MCP client: the same text.
5. Open the account journal: one entry per export, naming the contact and the surface.

## Gates

```bash
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```
