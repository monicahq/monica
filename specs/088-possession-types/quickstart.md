# Quickstart: Possession Types

## Automated

```bash
php artisan test --compact --filter=Possession
php artisan test --compact tests/Feature/RouteProtectionTest.php tests/Feature/Mcp/McpCatalogueTest.php tests/Unit/ViewModels/Docs
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

Then the whole suite, since the settings sidebar is shared (`.claude/rules/components.md`).

## By hand, on `https://monica.test/settings/account/possession-types`

1. The sidebar's Possessions group shows Locations and Possession types. The list is empty and offers
   to create or import.
2. New type: "Untitled type" opens in Blue. Rename to "Office equipment", pick Teal; the breadcrumb and
   the list follow. Clear the name: refused.
3. Add "Serial number" standalone, a group "Purchase" with "Price" (number) and "Shop", and
   "Condition" (select: New, Used, Broken). Each change says "Saved" without a button; tab away only.
4. Move "Shop" above "Price" with the keyboard; the first field's move up is unavailable.
5. Remove "Purchase": its fields become standalone, after "Serial number".
6. Export: the JSON lists the type, groups and fields in order; copy it. Import it: a second "Office
   equipment" opens, identical. Paste `{"format":"possession_type"}`: every problem is listed before
   submitting.
7. Delete the copy after the confirmation.
8. With an API key, `GET /api/v1/possession-types` returns both screens' data; through an MCP client,
   `list-possession-types` the same.
9. VoiceOver: each save is announced, the colour swatches are named, reorder buttons have names.
