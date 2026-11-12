# Quickstart: OpenAPI description of the public API

How to build the document, prove it is correct, and prove it is useful. A fresh worktree has no `vendor/`, so nothing runs until the prerequisites do. The decisions behind these steps are in [research.md](research.md), and the mapping is in [data-model.md](data-model.md).

## Prerequisites

```bash
composer install && bun install && bun run build
```

## Build it

Nothing to build. The document is assembled on the request, so serve the application and ask for it:

```bash
php artisan serve
curl -s http://localhost:8000/docs/api/v1/openapi.json | head -40
```

## Prove it is in step with the API

```bash
php artisan test --compact tests/Unit/Actions/BuildOpenApiDocumentTest.php tests/Feature/Controllers/App/Docs/OpenApiDocumentControllerTest.php
```

The action's test is what holds the built document to the routes. Then the two checks this feature extends rather than replaces, holding the reference to the routes in both directions and the documented fields to real responses:

```bash
php artisan test --compact tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php
```

## Prove it is valid

```bash
curl -s http://localhost:8000/docs/api/v1/openapi.json > /tmp/monica-openapi.json
bunx @redocly/cli@latest lint /tmp/monica-openapi.json
```

FR-002 asks for no error and no warning, so a warning here is a failure, not a note.

## Prove it is useful

Import `http://localhost:8000/docs/api/v1/openapi.json` into Postman, Bruno or Swagger UI by URL, set a bearer token from Settings, and send `GET /api/v1/vaults`. The answer must carry the fields the document promised and no others, which is SC-002 met.

Client generation is the part 3.2 currently costs. `openapi-generator` is built on `swagger-parser`, which cannot read 3.2, so it fails before writing a file. To generate a client today, serve the document as 3.1 by changing the one constant in `BuildOpenApiDocument::execute()`, then:

```bash
bunx @openapitools/openapi-generator-cli generate -i /tmp/monica-openapi.json -g typescript-fetch -o /tmp/monica-client
```

That produces 109 operations across 18 classes, one per tag, which is SC-001. See [research D1](research.md).
