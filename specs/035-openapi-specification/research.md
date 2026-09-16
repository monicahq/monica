# Research: OpenAPI description of the public API

Eight decisions, each settled before Phase 1. The mapping from the reference format to OpenAPI is in [data-model.md](data-model.md) and is not repeated here.

## D1. OpenAPI 3.2.0

**Decision**: the document declares `openapi: 3.2.0`.

**Rationale**: 3.2 is the current version of the format, and it is what was asked for. Redocly validates this document against it with no error and no warning, and Swagger UI, Swagger Editor and Swagger Client all read 3.2.

**What it costs, and this is measured rather than expected**: `openapi-generator` cannot read the document at all. The same bytes with only the version field changed generate a client of 109 operations across 18 classes when they say 3.1.0, and generate nothing at all when they say 3.2.0, failing before a single file is written even with validation switched off. The cause is `swagger-parser`, which `openapi-generator` is built on and which has no 3.2 support yet, so waiting for a newer generator release does not help until that lands. SC-001 is about generating a working client, so with that generator it is not currently reachable. The whole remedy is one constant in `BuildOpenApiDocument::execute()`.

**Alternatives**: 3.1.0, which every tool tried here reads, including the generator, and which this document declared until it was changed. 3.0.3, rejected as the legacy branch with nothing to offer this API.

## D2. No new dependency

**Decision**: the document is an array the application builds and encodes. No OpenAPI package is added to `composer.json`.

**Rationale**: every package in this space exists to read annotations or attributes off controllers and infer a document. The reference files already hold a better description than inference could produce, including prose, worked examples and per endpoint rate limits, so a package would be asked to do the one job that is already done. Adding one would also need approval it does not need to ask for.

**Alternatives**: `zircote/swagger-php` and `dedoc/scramble`, both rejected for the reason above. Neither can read the reference files, so either would mean abandoning FR-013.

## D3. Built when it is asked for, never stored

**Decision**: an action builds the document on each request to `/docs/api/v1/openapi.json`. No file is written, no Artisan command exists, and nothing is committed.

**Rationale**: the reference pages are already `require`d and rendered on every view of `/docs/api`, so building one more thing from them on a request is what this portal does all day. Nothing stored is nothing that can go stale, so the freshness problem a committed copy creates never arises and nobody has to remember to run anything.

**Alternatives**: writing `resources/docs/openapi.json` from a `monica:generate-openapi` command and holding it fresh with a `--check` mode the suite runs, which is how `monica:localize` keeps `lang/*.json` honest. Rejected: it buys a diffable artifact at the price of a command to forget, a large generated file sitting under every review, and a staleness failure mode that building on demand simply does not have. Building it only at release time, rejected for the same reasons and because a contributor would not see their own change until after merge.

## D4. Served at `/docs/api/v1/openapi.json`

**Decision**: one route in `routes/web.php`, declared before `docs/api/{section}`, answering with the built document and asking for no credential.

**Rationale**: it sits next to the reference it comes from, which is where somebody looks for it, and it carries the API version it describes, so FR-010 is answered by the address rather than only by the document. `docs/api.md` already sits in that group and is already declared before the catch all for the same reason, so the ordering problem is one this file has solved before.

**Alternatives**: `/api/v1/openapi.json`, rejected because `routes/api.php` is the API surface rather than a place for artifacts about it, and because the existing parity test treats every `api/v1/` route as an endpoint owing a reference entry, which this one does not. An address without the version, rejected because a second API version would then have nowhere of its own to go.

## D5. JSON only

**Decision**: one representation, `openapi.json`.

**Rationale**: every tool reads JSON, and the audience for a YAML document is a person, who already has `/docs/api` and `/docs/api.md`. A second representation is a second thing to keep in step for no reader.

**Alternatives**: YAML as well, or instead. Rejected for the reason above, and because emitting YAML would mean depending on a dumper to serve an audience that does not exist.

## D6. The builder is an action

**Decision**: `app/Actions/BuildOpenApiDocument` holds the transformation. The command and the controller hold transport.

**Rationale**: constitution principle I puts the logic in an action so every surface reaches the same one, and principle I equally forbids the obvious alternative: `app/ViewModels` is read only and consumed by Blade and nothing else, so the class that already reads these files for the portal cannot be the one that builds this document.

**Alternatives**: a service class, rejected because `app/Services` does not exist and creating it is a constitutional amendment for no gain. Extending the existing view model, rejected for the reason above.

## D7. Validation is external and named in the quickstart

**Decision**: FR-002 is proved with `bunx @redocly/cli@latest lint`, run by hand and named in [quickstart.md](quickstart.md), not wired into CI.

**Rationale**: the validator is a Node tool and this is a PHP repository whose CI installs no Node for static analysis. Wiring it in would add a toolchain to a pipeline to check a document the suite already proves is derived correctly from a source the suite already checks against the routes.

FR-002 asks for no warning, not only no error, and Redocly's default ruleset warns about a missing `operationId`, missing `tags` and a missing top level `description`, `contact` or `license`. The builder therefore emits all of them rather than leaving them for the validator to complain about.

**Alternatives**: Spectral with a rule pack, equivalent in outcome and rejected only because one validator named in one place is enough. Adding either to `static.yml`, rejected for the reason above, and reconsiderable the day that workflow needs Node for something else.

## D8. The document describes the instance that served it

**Decision**: `servers` is `ApiDocsViewModel::baseUrl()`, which is the address of the instance answering the request.

**Rationale**: with nothing published once and for all, there is no single address to pin, and the instance answering is the one the caller wants described. A self-hosted instance therefore hands out a document pointing at itself, and the `links.self` values the reference pages build from that same base agree with it without any work. This is the one place where building on demand is better rather than merely equal.

**Alternatives**: a pinned constant naming the hosted instance, which a committed copy would have needed. Rejected here because it would tell every self-hosted caller to send their requests somewhere else.
