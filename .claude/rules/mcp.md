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

- **An update replaces.** It clears what is left out, as the `PUT` does. There is no partial update
  in a tool and no `PATCH` on the API to carry one. The schema marks what the endpoint requires as
  required, and the description says that what is left out is cleared.
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
changelog still records the change as arriving from an assistant.

The two global middlewares that reshape input, `TrimStrings` and `ConvertEmptyStringsToNull`, are
applied by the adapter. Without them `parent_id: ""` reached `firstOrFail()` as an empty string and
broke the call. A controller that reads something else a middleware sets (a route parameter it binds,
`abort_unless` on the vault, `currentAccessToken()`) has to be checked before its tool moves over.

## Most tools are still copies

On 2026-11-16 the five location tools were the only ones answering through the controller. The other
tools still hold their own rules. A new tool never copies a controller, and a tool you change moves to
`answerThroughApi()` in the same change, with its schema and its description brought in line with the
endpoint.

## In the tests

What the endpoint does is proven once, in the controller's feature test. A tool's test proves the
wiring and nothing the controller test already proves: the tool reaches the endpoint and returns its
answer, a record of another account does not exist, and one refusal from the controller is relayed.
