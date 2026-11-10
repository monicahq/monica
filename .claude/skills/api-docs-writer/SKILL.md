---
name: api-docs-writer
description: Write or update the public-facing API reference for API methods. Use when a new API controller is created, API routes are added or changed, or when the docs portal at /docs/api is out of sync with the codebase.
---

# API docs writer

The API reference is a portal served at `/docs/api`. It is built from the PHP definition files in `resources/docs/api` and rendered with the Blade components in `resources/views/components/api-docs`. There are no markdown files to write, and no controller or route to add for a new page.

## How the portal works

- `resources/docs/api/*.php`: one file per sidebar group, loaded in filename order (the numeric prefix sets the order). Each file returns an array with a `name`, a `description` and a list of `sections`. A group with `'guide' => true` renders under GETTING STARTED in the sidebar; the others render under RESOURCES with method badges.
- `App\ViewModels\Docs\ApiDocsViewModel` loads the groups, builds the sidebar navigation and the markdown, and compares the documented endpoints with the routes (`documentedRoutes()`, `registeredRoutes()`). `ApiDocsSection` normalizes one section, `ApiDocsCodeSamples` writes the cURL, JavaScript and PHP samples, `ApiDocsMarkdown` writes the markdown, and `ApiDocsFields` builds the list of returned fields.
- `App\Http\Controllers\App\Docs\ApiDocsController` renders the portal at `/docs/api` and one section at `/docs/api/{section}`. `ApiDocsMarkdownController` serves the whole reference at `/docs/api.md` and one section at `/docs/api/{section}.md` (used by the Copy for LLM and View as Markdown buttons). `OpenApiDocumentController` serves the OpenAPI document at `/docs/api/v1/openapi`, built by `App\Actions\BuildOpenApiDocument` from the same files.

## Step 1: Research

For the resource being documented, you MUST read:

- the API controller (`app/Http/Controllers/Api/…`): every public method,
- `routes/api.php`: exact URL, verb and route name per endpoint,
- the Eloquent Resource (`app/Http/Resources/…`): exact response shape,
- the action's `validate()` and any policy it asks: what the endpoint refuses,
- a sibling definition file (e.g. `resources/docs/api/06-contacts.php`): to match tone and structure.

## Step 2: Write the definition file

You MUST create a numbered file in `resources/docs/api`, or add sections to an existing group. `ApiDocsSection` reads these keys and no others:

```php
[
    'id' => 'contacts-index',              // required; unique, kebab-case; the anchor and /docs/api/{id}
    'title' => 'List contacts',            // required; section heading
    'label' => 'List contacts',            // sidebar label; defaults to title
    'method' => 'GET',                     // required
    'path' => '/vaults/{vault}/contacts',  // required; route path, {parameters} exactly as in routes/api.php
    'examplePath' => '/vaults/1/contacts', // path used in code samples; defaults to path
    'auth' => true,                        // false drops the Authorization header from the samples
    'description' => '…',                  // required; first paragraph under the title
    'body' => ['…'],                       // extra paragraphs
    'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.', // required
    'pathParams' => [/* params */],
    'queryParams' => [/* params */],
    'bodyParams' => [/* params */],
    'paramsTitle' => '…',                  // heading over the params; derived when left out
    'returns' => '…',                      // one sentence describing the response
    'returnFields' => ApiDocsFields::one('contact', 'contact', $attributes), // every field the response holds
    'responseStatus' => 200,               // defaults to 200; 201 for create, 204 for delete
    'responseMediaType' => 'application/json', // the default
    'response' => [/* body as a PHP array */], // omit for 204 responses
    'exampleBody' => [/* request body */], // overrides the body built from the params' examples
    'statusCodes' => [/* ['status' => 422, 'name' => …, 'when' => …, 'body' => …] */],
    'flag' => 'shared-albums',             // documented only while the flag is on for every account; a list needs every flag on
]
```

A param is `['name' => …, 'type' => …, 'required' => bool, 'description' => …]`, plus optional `'default' => '10'` and `'example' => …`. Body params that define an `example` become the request body of the code samples; use `exampleBody` to override it.

Helpers:

- `ApiDocsViewModel::baseUrl()` returns the API base URL, for the `links` of an example record.
- `ApiDocsFields::one()`, `page()`, `all()` and `nested()` build `returnFields` from a list of attributes (`['name' => …, 'type' => …, 'description' => …]`), for one record, a page of records, an unpaginated list, and a record held inside another.

Conventions:

- You MUST document endpoints in this order: list (`GET`), get one (`GET`), create (`POST`), update (`PUT`), delete (`DELETE`), then extra actions.
- You MUST match example values to the Resource exactly: `id` is a string, timestamps are Unix integers, and the JSON envelope is `type` / `id` / `attributes` / `links`. When a Resource gains an attribute, add it to every example record and to the attribute list of the group.
- You MUST say in `returns` or `body` that a record of another account answers 404. An account carries no role, so there are no role rules to state.
- You MUST name each refusal the endpoint answers with 422, in the words the action or the policy uses.
- You MUST NOT use em dashes in the copy. You MUST rephrase with periods, commas or parentheses.

## Step 3: Test

Three tests fail on their own when the reference and the API disagree. Each new endpoint asks for two edits to the tests:

- `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php` asserts that the documented endpoints match the routes in `routes/api.php` exactly, in both directions. It needs no edit.
- `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php` counts every section in `every_section_of_the_reference_is_reachable()`. You MUST raise the `assertCount()` by the number of sections added. When adding a new group, you MUST also add an `assertSee()` for one of its titles.
- `tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php` calls every documented endpoint and compares what it returns with `returnFields`. You MUST add an arm for each new section id to `answer()`, setting up the record it needs the way the neighbouring arms do.

You MUST then run:

```bash
php artisan test --compact tests/Unit/ViewModels/Docs tests/Feature/Controllers/App/Docs tests/Unit/Actions/BuildOpenApiDocumentTest.php
```
