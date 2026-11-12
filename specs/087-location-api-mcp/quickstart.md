# Quickstart: Location API and MCP Tools

## Automated

```bash
php artisan test --compact tests/Feature/Controllers/Api/LocationControllerTest.php tests/Unit/Mcp/Tools tests/Unit/Helpers/LocationTreeTest.php tests/Unit/ViewModels/Settings/Account/Locations tests/Feature/Mcp tests/Unit/ViewModels/Docs tests/Feature/RouteProtectionTest.php
```

`McpCatalogueTest` proves each tool mirrors a route and is in the catalogue; `ApiDocsViewModelTest`
proves each route is documented.

## By hand, with a key of a test account on erica.test

1. `POST /api/v1/locations` with `{"name": "Dunder Mifflin Scranton", "address": "1725 Slough Avenue"}`:
   201, `path` is the name, `effective_address.location_name` is itself.
2. `POST` "Warehouse" with its `parent_id`, then "Shelf 3" inside Warehouse: `path` reads
   "Dunder Mifflin Scranton › Warehouse › Shelf 3", `effective_address` names Scranton.
3. `PUT` Scranton with `parent_id` set to Shelf 3: 422 on `parent_id`.
4. `DELETE` Warehouse: 422 on `location`, "1 location is inside it. Move it first."
5. `GET /api/v1/locations`: Scranton, Warehouse, Shelf 3 in that order; the screen shows the same.
6. With an assistant connected over MCP: "add a Garage inside Scranton, and a Workbench in it". Both
   appear on the screen, and the account log shows them as arriving from the assistant.
7. `/docs/api` shows a Locations section; `/docs/mcp` lists five location tools.
