# Research: QUERY method on the API

Seven decisions, each checked against the installed Laravel 13.40 and the code as it stands.

## D1. One `Route::match(['GET', 'QUERY'], ...)` per read route

**Decision**: every `Route::get(` in `routes/api.php` (112 lines) becomes `Route::match(['GET', 'QUERY'], `.
The Route constructor adds HEAD to any route holding GET, so HEAD is kept. A test fails when a route
under `api/v1/` holds GET without QUERY.

**Rationale**: the route file stays the one place that says what a path answers to, `route:list` and
`route:cache` see the truth, and nothing reaches into the framework's route collection.

**Alternatives**: rewriting each GET route's `methods` in the `then:` callback of `withRouting` and
rebuilding the collection. Rejected: it relies on the collection's internal index, and a forgotten
rebuild lists every route twice. Registering a second QUERY route per path: rejected, because two
routes sharing a name break `route:cache`.

## D2. One middleware turns the body into parameters

**Decision**: `App\Http\Middleware\MergeQueryRequestBody` does nothing unless the method is QUERY.
Otherwise it reads the body (`json()->all()` for JSON, the parsed form otherwise). It refuses with a
422 when a key is also in the address. Then it adds the body to the address parameters.

**Rationale**: 50 controller calls read `$request->query(...)`, which sees the address only. After
the merge they, `input()`, `string()`, `integer()`, `validate()` and the paginator all read the same
values, and no controller changes.

**Alternatives**: rewriting the 50 calls to `$request->string()` and friends. Rejected for this
change: it touches 30 controllers and would still let the body win silently over the address,
since `input()` gives the body priority.

## D3. It runs after authentication

**Decision**: the middleware, aliased `query.body`, sits on the `auth:sanctum` group in
`routes/api.php`, after `auth:sanctum` in the same array. Nothing outside that group takes parameters worth merging:
`status` reads none.

**Rationale**: the spec answers an unauthenticated QUERY with 401. Group middleware would run first
and answer 422 instead.

## D4. The reference treats QUERY as it treats HEAD

**Decision**: `ApiDocsViewModel::registeredRoutes()` skips QUERY beside HEAD and OPTIONS, so the
parity tests keep comparing GET with the documented sections. `endpoint.blade.php` and
`ApiDocsMarkdown` print one line on a GET section linking to a new guide section in
`01-getting-started.php`. That guide borrows `GET /vaults/{vault}/search`, as guides borrow a real
endpoint.

## D5. OpenAPI declares a `query` operation beside each `get`

**Decision**: `BuildOpenApiDocument` copies each GET operation under `query`, with an `operationId`
suffix and a JSON `requestBody` whose properties are the GET's query parameters.

**Rationale**: the document already declares 3.2.0 (spec 035, D1), and 3.2 defines `query`. This
departs from 035's "plain constructs" constraint on purpose: a tool reading only part of 3.2
ignores the extra operation and keeps the GET beside it.

## D6. The changelog entry lists its endpoints by hand

**Decision**: one dated file in `resources/docs/api-changelog`, kind Added, not breaking. Its
`endpoints` list is written out at release, not computed. A computed list would grow with every
later route and rewrite history.

## D7. Nothing below the framework is proven by the suite

PHPUnit goes through the kernel, so it cannot show that the web server, a reverse proxy or a CDN in
front of an instance passes a QUERY with its body. CORS needs nothing: no `config/cors.php` exists
and the framework default allows every method. The global `ValidatePostSize` middleware already
caps a QUERY body as it caps a POST body. The quickstart's `curl` against a running instance is the
only check of the full path.

Observed on 2026-11-16: PHP 8.5's built-in server, behind `php artisan serve` and `composer run dev`,
answers every QUERY with 501 before the application sees it. Local development cannot exercise
QUERY over HTTP; the suite can. The Docker image serves through Apache, whose `.htaccess` limits no
method, so it is expected to pass QUERY, but that is unverified.
