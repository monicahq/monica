# Research: QUERY requests in the API reference

## R1. Where the QUERY entries come from

**Decision**: a static `ApiDocsViewModel::withQuerySections(array $sections): array` takes the section
arrays of one resource file and returns them with a derived QUERY array directly after each GET
array. `ApiDocsViewModel::groups()` calls it after `offeredToEveryAccount()` for every group that is
not a guide. `BuildOpenApiDocument::groups()` calls it the same way, and the QUERY branch of
`BuildOpenApiDocument::paths()` is deleted.

**Rationale**: every consumer of the reference (sidebar, previous and next, section lookup, Markdown
copies, `llms.txt`, sitemap, route coverage) reads `groups()` or `sections()`. Deriving there lists the
entries everywhere at once. The OpenAPI builder already derives a QUERY operation from each GET
section; moving that derivation into the shared function keeps one copy of the rule (FR-005).

**Alternatives considered**:
- Write the QUERY entries in `resources/docs/api`. About 124 copies to keep in step by hand, which
  FR-005 forbids.
- Derive in `sections()` only. Leaves the OpenAPI builder with its own copy of the rule.
- Derive for guides too. A guide that borrows a GET request to show a sample would gain a QUERY
  twin in the "Getting started" navigation, which no reader asked for.

## R2. Entry id and address

**Decision**: the QUERY entry's id is the GET entry's id followed by `-query`. Its pages are
`/docs/api/{id}-query` and `/docs/api/{id}-query.md`.

**Rationale**: `{id}-query` is already the `operationId` of the QUERY operation in the OpenAPI
document, so the reference and the generated clients use one name. No existing id changes (FR-010).
`it_gives_every_section_a_unique_id` already fails on a collision.

## R3. Title and label

**Decision**: title `"{GET title} with QUERY"`, label unchanged.

**Rationale**: the label is what the sidebar prints next to the method badge, and the badge sits inside
the link, so the link's accessible name already reads "QUERY List contacts". The title is the page
heading, the document title, the OpenAPI `summary` and the link text in `llms.txt`, which lists every
entry by title alone. With the GET title unchanged there, two links would read the same.

**Alternatives considered**: `"{title} (QUERY)"` reads as an aside; a hand written title per entry
breaks R1.

## R4. Parameters and samples

**Decision**: the derived array moves `queryParams` to `bodyParams` and keeps `pathParams`.

**Rationale**: `ApiDocsSection::mergeParams()` and `titleForParams()` then print "Body parameters"
(or "Parameters" beside path parameters), and `exampleBody()` builds the JSON body from the examples.
`ApiDocsCodeSamples` already adds `-X QUERY`, the method option and the content type for any method
other than GET with a body, so the three samples come with no change. No GET section sets
`paramsTitle` or `exampleBody`, checked by grep over `resources/docs/api`, so nothing has to be
unset.

## R5. Status codes

**Decision**: when the GET section has query parameters, the derived array says that a parameter sent
both in the address and in the body answers 422: appended to the `when` of an existing 422 row, or as
a new 422 row when there is none.

**Rationale**: the table of status codes holds one row per status. (The OpenAPI builder does not
read `statusCodes`; its 422 comes from `bodyParams`, so it is unaffected.) An endpoint without query parameters cannot receive one twice, so it gets nothing.

## R6. Links between the GET and QUERY entries

**Decision**: the derivation writes `querySectionId` on the GET array and `derivedFromSectionId` on the QUERY
array; `ApiDocsSection` reads both as nullable properties.
- A GET entry with a `querySectionId` replaces today's sentence with one linking to its QUERY entry.
- A QUERY entry with a `derivedFromSectionId` carries one sentence linking to its GET entry and to the guide.
- The guide's own QUERY section has neither, so it shows neither sentence.

Both sentences are written in `endpoint.blade.php` and in `ApiDocsMarkdown`, the two places that
hold today's sentence.

**Rationale**: testing `method === 'GET'` would print a link on a guide's GET sample to a QUERY
entry that does not exist (R1).

## R7. Coverage

**Decision**: `ApiDocsViewModel::registeredRoutes()` stops skipping QUERY. `documentedRoutes()` then
holds the derived entries, and the two existing tests (`it_documents_every_registered_route`,
`it_registers_every_documented_route`) enforce FR-012 in both directions.
`BuildOpenApiDocumentTest::registered()` stops adding QUERY itself, since the routes now carry it.

## R8. The return field test

**Decision**: `ApiDocsReturnFieldsTest::endpoints()` skips entries with a `derivedFromSectionId`.

**Rationale**: the test calls each endpoint through a `match` on its id, so every derived id would
report "nothing in this test knows how to call it". The QUERY answer is the GET answer by
construction (126, FR-003, proven in the API tests), and its documented fields are the GET entry's.

## R9. Changelog

**Decision**: one entry in a new `resources/docs/api-changelog/2026-11-22.php`, kind "Added", group
"Getting started", `breaking` false, saying every QUERY request has its own reference entry.

## R10. What needs no change

Sidebar, previous and next, `sectionFor()`, the controller's lookup by id, the whole reference
Markdown (FR-009), `llms.txt` and the sitemap all walk `sections()` or `groups()`. The sidebar filter
already matches on label and method.

## R11. Example values and where a sample sends them

**Decision**: a sample takes its body from the body parameters only and puts the query parameters'
examples in the address. `ApiDocsSection::exampleBody()` reads `bodyParams` alone, `exampleQuery()`
reads `queryParams`, and `ApiDocsCodeSamples::url()` appends `exampleQuery()` as a query string. Every
GET query parameter that filters by a value (page, per_page, an enum, a search term) carries an
`example`; one naming a record by its id does not, since an invented id answers 404 or 422. The six
`examplePath` values that held a query string lost it, the values moving to `example`.

**Rationale**: only 3 of the QUERY samples sent a body, because the GET query parameters had no
example. Adding examples exposed an existing fault: `exampleBody()` read every kind of parameter, so
a path or query example landed in a GET sample's body (`billing-payments-show`, `search-index`), which
curl sends as a POST. Each example value was checked against the controller's validation rule.

## R12. One page for GET and QUERY (2026-11-23)

**Decision**: `ApiDocsViewModel::withQuerySections()` no longer adds a QUERY section after each GET
section. It nests the derived QUERY section in the GET section under `querySection`, and
`ApiDocsSection` builds it as `$section->querySection`. The page renders both method badges, one
sentence from `ApiDocsSection::answersGetAndQuery()`, a second code panel for the QUERY sample, and
the status codes of the QUERY section, which add the 422 for a parameter sent twice and say it
applies to QUERY. The sidebar item carries `methods` (one or two) instead of `method`. The Markdown
copy names both methods, holds the sentence, and adds a "Request with QUERY" block.
`BuildOpenApiDocument::paths()`, `documentedRoutes()` and `sectionFor()` read the nested section, so
the OpenAPI document, the route coverage test and the changelog links are unchanged. The ids
`{id}-query`, `querySectionId` and `derivedFromSectionId` are gone; the nested section keeps
`{id}-query` as its OpenAPI operation id.

**Rationale**: the requester read the built reference with a page per QUERY request and asked for
one page per endpoint, with the two methods stacked in the sidebar and the two samples stacked on the
right. The answer is the same for both, which the code confirms: `MergeQueryRequestBody` adds the body
to `$request->query` before the controller runs, so a controller cannot tell the two apart, except
for the 422 the middleware returns when a parameter is sent twice. The reference is back to the 331
pages it had before this feature.

**Alternatives considered**: keeping the separate pages and linking them (the 2026-11-22 design),
rejected by the requester after seeing it.
