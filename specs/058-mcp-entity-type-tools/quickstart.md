# Quickstart: MCP tools for entity types

## Automated

```bash
php artisan test --compact tests/Unit/Mcp/Tools --filter=EntityType
php artisan test --compact tests/Feature/Mcp
php artisan test --compact tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php tests/Feature/Controllers/App/Docs
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

Expected: all pass. `McpCatalogueTest` proves each new tool names a route that exists, is
registered, and that `get-entity-type` is read only and `delete-entity-type` destructive.
`McpDocsViewModelTest` proves the page lists exactly the registered tools.

## Scenarios the tests cover

Refer to [contracts/mcp-tools.md](contracts/mcp-tools.md) for inputs.

1. Create **Wine bottle / Wine bottles** with no slug: slug `wine-bottle`, last position, active,
   journalled with the MCP source.
2. Create with a slug another type already uses, or `Wine Bottle`: refused, nothing written.
3. Create with icon `dundie`: refused.
4. Update only the colour: names and description unchanged, slug unchanged.
5. Switch off, then switch off again: one journal row.
6. Move to position 1: the list order changes as through the API.
7. Delete an empty type: gone. Delete a type with `entity_count > 0`: refused with
   `The type still holds records and stays.`
8. Any tool given another account's type: `That entity type does not exist.`

## By hand (optional)

With an API key and a connected assistant, ask it to "keep track of my wine bottles", then open
**Settings → Entity types** and **Settings → Logs**.
