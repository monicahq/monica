# Quickstart: MCP tools for life event types

## Automated

```bash
php artisan test --compact tests/Unit/Mcp/Tools --filter=LifeEvent
php artisan test --compact tests/Feature/Mcp
php artisan test --compact tests/Feature/Controllers/Api/LifeEventTypeFieldControllerTest.php
php artisan test --compact tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php tests/Feature/Controllers/App/Docs
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

Expected: all pass. `LifeEventTypeFieldControllerTest` proves the API behaves as before the rules and
mapping moved to `LifeEventTypeFieldLayout`. `McpCatalogueTest` proves each new tool names a route
that exists, the reading tools are read only and the delete tools destructive. `McpDocsViewModelTest`
proves the page lists exactly the registered tools.

## Scenarios the tests cover

Refer to [contracts/mcp-tools.md](contracts/mcp-tools.md) for inputs.

1. List the types: every type of the account in category order, then type order.
2. Create **Won a Dundie** in a category with icon and colour from the allowed lists: in that
   category, journalled with the MCP source. An icon outside the list is refused.
3. Update only the colour: name, category, description and icon unchanged. `description: null` clears
   it.
4. Switch a type off, then off again: one journal row. Move it to position 1 in its category.
5. Delete a type Michael created and nobody used: gone. Delete a supplied type, or one Jim's life event
   uses: refused with the policy's sentence.
6. Replace the fields of **Won a Dundie** with a group Ceremony holding a text field and a date field:
   the fields tool returns them. Replace again without the date field, keeping the text field's key:
   the text field keeps its key, the date field is gone. An unknown field kind is refused.
7. Create category **Sales**, rename it, list its types in position order, move it, switch it off and
   on. Delete one holding types: refused. Delete an empty one Michael created: gone.
8. Any tool given another account's type or category, including as `life_event_category_id`: the not
   found sentence.

## By hand (optional)

With an API key and a connected assistant, ask it to "record when someone runs a marathon, with the
race name and the time", then open **Settings → Life event types** and **Settings → Logs**.
