# Quickstart: validating life events over the API and MCP

Shapes are in [contracts/api.md](contracts/api.md) and [contracts/mcp.md](contracts/mcp.md).

## Automated

```bash
php artisan test --compact tests/Feature/Controllers/Api/LifeEventControllerTest.php \
  tests/Feature/Controllers/Api/LifeEventRestorationControllerTest.php \
  tests/Unit/Mcp/Tools --filter=LifeEvent
php artisan test --compact tests/Unit/Actions/CreateLifeEventTest.php tests/Unit/Actions/UpdateLifeEventTest.php
php artisan test --compact tests/Unit/Services/ApiDocumentationTest.php \
  tests/Feature/Controllers/Marketing/Docs/ApiDocsControllerTest.php \
  tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php tests/Feature/Mcp/McpCatalogueTest.php
```

Then the gates of `CLAUDE.md`: `php artisan monica:localize --check`, PHPStan, Pint.

## By hand, with an API key on a local instance

1. `GET /api/v1/life-event-types/{type}/fields` on a type with a text field: note its `id`.
2. `POST /api/v1/vaults/{vault}/life-events` with the type, a date, two contacts and `fields: {"<id>": "Pasta"}`: 201, `created_from` is `api`, both contacts' pages show it.
3. `PUT` it with a new `happened_on` and no `fields`: 200, the text value is still there.
4. `DELETE` it: 204, gone from both pages. `PUT …/restoration`: 200, back on both.
5. Repeat 2 with a file field in `fields`: 422 naming that field.
6. Open `/docs`: "Life events" is in the sidebar, each section shows a request and an answer.
7. Open `/docs/mcp`: the two life event groups list six tools.
