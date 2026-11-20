# Quickstart: Record of an invented type

## Automated

```bash
php artisan test --compact --filter='Entit'
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
composer test:parallel
```

## By hand

1. Open Animals in a vault, add Biscuit with a breed and a birthday. The list row is a link.
2. Open Biscuit: the mark in the type's colour, "Animal" caption, name, Edit; the pages column with field
   counts and the template name and version; each section as a panel of label and value lines; empty
   fields read "Not recorded".
3. Choose another page in the column: the address changes and the page is announced as current.
4. Edit: the form is prefilled. Clear the name and save: refused with the reason. Change the breed and
   save: back on Biscuit with the new breed and a confirmation.
5. Light, dark, 320px: no horizontal scroll; the pages column sits above the sections below 1024px.
6. API: `GET /api/v1/vaults/{vault}/entities/{uuid}` returns `fields`; `PUT` with only `{"name": "Bisc"}`
   keeps every value. See [contracts/api.md](contracts/api.md).
7. MCP: `get-entity` and `update-entity` answer as the API does.
