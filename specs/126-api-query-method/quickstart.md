# Quickstart: QUERY method on the API

Checks the whole path, web server included, which the suite cannot reach (research D7).

## Prerequisites

An instance served by Apache or nginx, such as the Docker image, never by `composer run dev`,
whose built-in PHP server answers QUERY with 501 (research D7). Also an API key in `$KEY`, a vault address in `$VAULT`, and
`BASE` set to the instance's `/api/v1` address.

## Scenarios

1. Same answer as GET. Run both and compare: the outputs must be identical.

   ```bash
   curl -s -H "Authorization: Bearer $KEY" "$BASE/vaults/$VAULT/search?query=Dwight"
   ```

   ```bash
   curl -s -X QUERY -H "Authorization: Bearer $KEY" -H "Content-Type: application/json" -d '{"query":"Dwight"}' "$BASE/vaults/$VAULT/search"
   ```

2. Paging from the body: `meta.page` is 2 and `meta.per_page` is 5.

   ```bash
   curl -s -X QUERY -H "Authorization: Bearer $KEY" -H "Content-Type: application/json" -d '{"page":2,"per_page":5}' "$BASE/vaults/$VAULT/contacts"
   ```

3. A parameter sent twice: 422 naming `page`.

   ```bash
   curl -s -X QUERY -H "Authorization: Bearer $KEY" -H "Content-Type: application/json" -d '{"page":2}' "$BASE/vaults/$VAULT/contacts?page=1"
   ```

4. A write-only path: 405 with an `Allow` header that does not name QUERY.

   ```bash
   curl -si -X QUERY -H "Authorization: Bearer $KEY" "$BASE/logout"
   ```

5. Reference and OpenAPI: `/docs/api` shows the QUERY guide and, on a GET endpoint page, the one
   line linking to it. The document at `/docs/api/v1/openapi.json` carries a `query` operation on
   `/vaults/{vault}/search`.

## Behind a proxy

Repeat scenario 1 through the production proxy before release. A proxy that refuses an unknown
method answers 405 or 501 before the request reaches the application.
