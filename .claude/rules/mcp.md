# MCP tools

A tool under `app/Mcp/Tools` exposes one API endpoint to an assistant. It answers exactly as that
endpoint does, which the server instructions promise and principle X of the constitution requires.
`specs/087-location-api-mcp/research.md` (R9) has the reasoning.

## The controller is the one implementation

A tool holds no rule of its own. Validation, paging, update semantics and the response body belong
to the API controller method its `$mirrors` route names, and the tool reaches it through
`MirrorsAnApiCapability::answerThroughApi()`:

```php
public function handle(Request $request): Response
{
    $validated = $request->validate([
        'location' => ['required', 'string'],
    ]);

    $location = $this->location($request, $validated['location']);

    return $this->answerThroughApi($request, $location->uuid);
}
```

Look the addressed record up through the trait first, then pass its identifier to the controller.
The trait gives the translated "does not exist" sentence and records the subject in the MCP history.
The controller's `firstOrFail()` gives neither, and reaches the assistant as a generic server error.

The copies this replaces had drifted on all three of validation, paging and update semantics before
anybody noticed. Two copies of a rule is the defect, whatever the reason for the second one.

## What follows from it

- **An update keeps what is left out and clears what is sent as null**, as the `PUT` does. A field
  that cannot be empty, such as a name, refuses null. The schema marks what the endpoint requires as
  required, and the server instructions state the rule once, so a description does not repeat it.
- **A list is paged.** The schema takes `page` and `per_page`, and the answer is `{data, meta}`.
- **A single record comes back without `data`.** `answerThroughApi()` removes the wrapper when it is
  the only key, so a record reads as it does from every other tool.
- **A refusal is the controller's.** A validation error reaches the assistant word for word, and the
  MCP history records it as invalid parameters, as the endpoint answers it with a 422.
- **An answer without a body**, such as a 204, comes back empty. A delete tool returns its own short
  sentence instead.

## The call skips the route's middleware

`answerThroughApi()` calls the controller method in process. The `/mcp` route already ran
authentication, the flag and the rate limit, and the MCP request stays the current one, so the
account log still records the change as arriving from an assistant.

The two global middlewares that reshape input, `TrimStrings` and `ConvertEmptyStringsToNull`, are
applied by the adapter. Without them `parent_id: ""` reached `firstOrFail()` as an empty string and
broke the call. A controller that reads something else a middleware sets (a route parameter it binds,
`abort_unless` on the vault, `currentAccessToken()`) has to be checked before its tool moves over.

## In the tests

What the endpoint does is proven once, in the controller's feature test. The wiring is proven once
too, not per tool:

- `answerThroughApi()` and the lookups of `MirrorsAnApiCapability` have their own test: the body is
  passed on, the `data` wrapper is removed, a refusal is relayed word for word, an answer without a
  body comes back empty, and a record of another account does not exist.
- One test walks every registered tool, sends another account's record in the first parameter that
  addresses one (the vault when there is one), and expects "does not exist". A record nested under the
  vault is scoped twice: by the trait's lookup, which takes its parent, and by the controller, whose
  own test proves it. That is why the walk stops at the first parameter.
- `McpCatalogueTest` proves every tool names a route that exists and is registered.

A tool gets a test file of its own only for what it adds beyond a lookup and `answerThroughApi()`. A
tool that adds nothing has no test file.
