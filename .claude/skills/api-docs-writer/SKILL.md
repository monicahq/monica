---
name: api-docs-writer
description: Write or update the public-facing API reference for API methods. Use when a new API controller is created, API routes are added or changed, or when the docs portal at /docs is out of sync with the codebase.
---

# API docs writer

The API reference is a single-page portal served at `/docs`. It is built by `App\Services\ApiDocumentation` from the PHP definition files in `resources/docs/api`, and rendered with the Blade components in `resources/views/components/api-docs`. There are no markdown files, no per-page controllers, and no per-page routes to create.

## How the portal works

- `resources/docs/api/*.php`: one file per sidebar group, loaded in filename order (the numeric prefix sets the order). Each file returns an array with a `name` and a list of `sections`. A group with `'guide' => true` renders under GETTING STARTED in the sidebar; the others render under RESOURCES with method badges.
- `App\Services\ApiDocumentation` normalizes every section and generates the cURL, JavaScript and PHP samples, the syntax-highlighted JSON, the sidebar navigation, and a markdown version of each section.
- `Marketing\Docs\ApiDocsController@index` renders the portal. `Marketing\Docs\ApiDocsMarkdownController` serves the whole reference at `/docs.md` and one section at `/docs/{section}.md` (used by the Copy for LLM and View as Markdown buttons).
- `Tests\Unit\Services\ApiDocumentationTest` asserts that the documented endpoints match the routes in `routes/api.php` exactly, in both directions. An API route without documentation fails the suite, and so does documentation for a route that does not exist.

## Step 1 — Research

For the resource being documented, you MUST read:

- the API controller (`app/Http/Controllers/Api/…`): every public method,
- `routes/api.php`: exact URL, verb and route name per endpoint,
- the Eloquent Resource (`app/Http/Resources/…`): exact response shape,
- the Actions' `validate()`: permissions (owners and editors vs any member),
- a sibling definition file (e.g. `resources/docs/api/05-collections.php`): to match tone and structure.

## Step 2 — Write the definition file

You MUST create a numbered file in `resources/docs/api`, or add sections to an existing group. Each section supports:

```php
[
    'id' => 'collections-list',           // unique, kebab-case; used as the anchor and in /docs/{id}.md
    'title' => 'List collections',        // section heading
    'label' => 'List collections',        // sidebar label; defaults to title
    'method' => 'GET',
    'path' => '/collections',             // route path, with {parameters} exactly as in routes/api.php
    'examplePath' => '/collections/1',    // path used in code samples; defaults to path
    'auth' => true,                       // false drops the Authorization header from the samples
    'description' => '…',                 // first paragraph under the title
    'body' => ['…'],                      // optional extra paragraphs
    'permissions' => '…',                 // e.g. 'Owners and editors. Viewers get a 404 response.'
    'pathParams' => [/* params */],
    'queryParams' => [/* params */],
    'bodyParams' => [/* params */],
    'returns' => '…',                     // one sentence describing the response
    'responseStatus' => 200,              // defaults to 200; use 201 for create and 204 for delete
    'response' => [/* body as a PHP array */], // omit for 204 responses
]
```

A param is `['name' => …, 'type' => …, 'required' => bool, 'description' => …]`, plus optional `'default' => '10'` and `'example' => …`. Body params that define an `example` become the request body of the code samples; you MUST use `'exampleBody' => […]` on the section to override it.

Helpers on `ApiDocumentation`: `baseUrl()` returns the API base URL, and `paginated($data, $path)` wraps a list of resources in a realistic paginator body.

Conventions:

- You MUST document endpoints in this order: list (`GET`), get one (`GET`), create (`POST`), update (`PUT`), delete (`DELETE`), then extra actions.
- You MUST match example values to the Resource exactly: `id` is a string, timestamps are Unix integers, and the JSON envelope is `type` / `id` / `attributes` / `links`.
- You MUST state the role rules in `permissions` for account-scoped resources, and say in `returns` that cross-account lookups return 404.
- You MUST NOT use em dashes in the copy. You MUST rephrase with periods, commas or parentheses.

## Step 3 — Test

The route sync test fails on its own when an endpoint is missing, so there is usually no new test to write. When adding a new group, you MUST add an `assertSee()` for one of its titles to `tests/Feature/Controllers/Marketing/Docs/ApiDocsControllerTest.php`. You MUST then run:

```
PAO_DISABLE=1 php artisan test tests/Unit/Services/ApiDocumentationTest.php tests/Feature/Controllers/Marketing/Docs/ApiDocsControllerTest.php
```
