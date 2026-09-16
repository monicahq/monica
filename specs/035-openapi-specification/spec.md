# Feature Specification: OpenAPI description of the public API

**Feature Branch**: `2026-09-15-openapi-specification` | **Created**: 2026-09-15 | **Status**: Draft

**Input**: "Provide OpenAPI api specs"

## Overview

The JSON API routes more than a hundred endpoints under `/api/v1`, and the only description of them is prose a person reads at `/docs/api`. Nothing a machine can read exists, so a caller writing a client, importing the API into the tool they already use, or wiring it into a generator has to transcribe that prose by hand and keep the transcription current on their own.

This feature publishes one OpenAPI document describing that API in full, so a caller can generate a client, load the API into an ordinary API client, and be told by their own tooling that a request is wrong before they send it. [Constitution principle X](../../.specify/memory/constitution.md) and [spec.md §3.6](../../spec/spec.md) already require the API to be publicly documented and versioned. This is that documentation in the form tooling reads.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Generate a client without reading prose (Priority: P1)

Somebody writing an integration fetches one document, hands it to the generator of their language, and gets a client covering every endpoint, with the right parameters, the right request bodies and typed answers.

**Why this priority**: it is the whole point of a machine-readable description, and it delivers on its own even if nothing else here is built. **Independent test**: fetch the document, run a standard generator over it, and confirm the client it produces covers every routed endpoint and compiles.

**Acceptance Scenarios**:

1. **Given** a caller with no credential, **When** they fetch the document at its published address, **Then** it comes back in full and is valid against the OpenAPI version it declares.
2. **Given** the document, **When** a validator reads it, **Then** it reports no error and no warning.
3. **Given** the document, **When** a caller counts the endpoints it describes, **Then** every endpoint the API routes is there exactly once, and nothing is described that the API does not route.
4. **Given** an endpoint that needs a token, **When** the caller reads its description, **Then** it names the authentication the endpoint expects, and an endpoint that needs no credential is marked as needing none.
5. **Given** any endpoint, **When** the caller reads it, **Then** it carries a short title, a description, its parameters with their types and whether each is required, the answer it gives when it succeeds, and every refusal it can give.

---

### User Story 2 - Load the API into the tool you already use (Priority: P2)

Somebody exploring the API imports the document into their API client, picks an endpoint, fills in a token and sends a real request that works, without writing any code.

**Why this priority**: it is what turns the document from a specification into something a person uses on their first afternoon, and it is the strongest proof the description is accurate rather than merely valid. **Independent test**: import the document into a common API client, send one request from it against a running instance, and confirm the answer matches the shape the document promised.

**Acceptance Scenarios**:

1. **Given** the document, **When** it is imported into a common API client, **Then** the import succeeds and every endpoint appears, grouped the way the reference groups them.
2. **Given** an imported endpoint and a valid token, **When** the request is sent, **Then** it succeeds and the answer carries the fields the document described, with no field the document did not mention.
3. **Given** an endpoint that pages its answers, **When** the caller reads its description, **Then** the parameters that page it and the counts that come back are described.
4. **Given** a caller who sends an invalid body, **When** the refusal comes back, **Then** its shape is the one the document described for an invalid request.
5. **Given** a caller who exceeds the rate limit, **When** the refusal comes back, **Then** the document had already stated that endpoint's limit and described this refusal.

---

### User Story 3 - The description cannot fall behind the API (Priority: P3)

Somebody adding, changing or removing an endpoint is told by the build that the description no longer matches, before their change can merge.

**Why this priority**: a description that drifts is worse than none, because it is trusted. It is third only because the first two deliver value on the day they land, while this protects that value from then on. **Independent test**: add a route without describing it, and remove a route that is described, and confirm the build refuses each.

**Acceptance Scenarios**:

1. **Given** a new endpoint on the API, **When** the document does not describe it, **Then** the build fails and names the endpoint.
2. **Given** an endpoint the document describes, **When** the API no longer routes it, **Then** the build fails and names it.
3. **Given** an endpoint whose method or path changed, **When** the document still describes the old one, **Then** the build fails.
4. **Given** a change that updates both together, **When** the build runs, **Then** it passes and the difference is readable in the pull request as ordinary text.

---

### Edge Cases

- An endpoint answers no content at all. It is described with the status it answers and no body, rather than being left without an answer.
- An endpoint outside the authenticated group, where an account is registered or a session is opened, has its own rate limit and needs no token. The document says so per endpoint rather than once for the whole API.
- A path parameter is an identifier the API will only accept in one form, and a path in any other form answers not found. The description states the form.
- A tool reads only an older OpenAPI version than the one declared. The document declares one version, which is named in the reference so nobody has to guess.
- An endpoint is described but its answer example is stale. The example is a promise, so it is held to the same check as the rest.
- A caller is refused because a rule of the product forbids what they asked rather than because their request was malformed. This API answers that as an invalid request and never as a forbidden one, and the document describes what the API does rather than what a generated client would expect.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The product MUST publish one OpenAPI document describing every endpoint the JSON API routes, at a stable public address, readable without a credential.
- **FR-002**: The document MUST declare which OpenAPI version it follows, and MUST be valid against it with no error and no warning from a standard validator.
- **FR-003**: Every endpoint MUST carry its method, its path, a short title, a description of what it does, and the group it belongs to, so a generated client names its methods the way the reference names its endpoints.
- **FR-004**: Every path parameter, query parameter and request body field MUST be described with its name, its type, whether it is required, its default where it has one, and an example.
- **FR-005**: Every endpoint MUST describe the answer it gives when it succeeds, with the status it answers and a worked example, including the endpoints that answer no content.
- **FR-006**: Every endpoint MUST describe the refusals a caller can receive from it: no credential, credential refused, record not found, request invalid, and rate limit reached, each with the shape the API actually returns.
- **FR-007**: The document MUST describe how a caller authenticates, and MUST mark per endpoint whether a credential is required, since a few endpoints need none.
- **FR-008**: Every endpoint MUST state its rate limit, because the limit is not the same on all of them.
- **FR-009**: Every endpoint answering a collection MUST describe how it is paged: the parameters that ask for a page and its size, the bounds on them, and the counts that come back.
- **FR-010**: The document MUST name the API version it describes. A future major version gets its own document at its own address, and this one keeps describing the version it describes.
- **FR-011**: A shape that repeats, whether a record type, a page wrapper or a refusal, MUST be described once and referred to, so a generator emits one type rather than a copy per endpoint.
- **FR-012**: An endpoint the API routes but the document omits MUST fail the build, and so MUST a path the document describes but the API does not route, each naming what is missing. The reference is already held to exactly this in both directions, and the document is built from it when it is asked for, so no stored copy exists that could fall behind either of them.
- **FR-013**: The reference pages behind `/docs/api` MUST stay the one place an endpoint is described by hand, and this document MUST be produced from them, so the two cannot disagree about an endpoint, its parameters or its answers. Where the document needs something the pages do not yet record, the pages MUST gain it rather than the document inventing it.
- **FR-014**: A change to what the document says MUST be reviewable in the pull request that makes it. That change is a change to the reference page it is built from, so what a reviewer reads is the prose and the example somebody actually wrote.
- **FR-015**: Publishing the document MUST NOT change any endpoint's behaviour, its address, or the shape of anything it answers.
- **FR-016**: The reference MUST tell a reader where the document is and what it is for, since a caller who wants it will look for it there first.

### Key Entities

- **API description**: one document describing one version of the JSON API in a standard machine-readable form. It describes and decides nothing.
- **Endpoint description**: one method and path, with its title, its prose, its parameters, its request body, the answer it gives and the refusals it can give.
- **Shared shape**: a record type, a page wrapper or a refusal, described once and referred to wherever it occurs.
- **Credential scheme**: how a caller proves who they are, named once and referred to by the endpoints that require it.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A developer who has never seen this API generates a working client and makes a successful authenticated request, using the document alone and reading no prose.
- **SC-002**: The document imports without error into the API clients people commonly use, and every endpoint is reachable from that import.
- **SC-003**: Every endpoint the API routes is described exactly once, and nothing is described that is not routed. Both directions are checked, and the check runs in the build.
- **SC-004**: A change that adds, removes or alters an endpoint without describing it cannot merge.
- **SC-005**: Every refusal a caller can receive is described, so a generated client has a type for it rather than a surprise.
- **SC-006**: A reader can tell from the document alone which endpoints need a credential and what each one costs against its rate limit.
- **SC-007**: No endpoint's behaviour, address or answer changes, which the existing API tests confirm by continuing to pass unchanged.

## Assumptions

- The document is built when it is asked for and is not stored anywhere. Nothing is committed that could fall out of step with the reference it comes from, and nobody has to remember to regenerate it.
- One document covers the whole of `/api/v1`. The API is versioned in its path, so the document is versioned with it, and splitting it per resource is rejected because a caller's tooling expects one address.
- An interactive console where a reader sends a request from the documentation page is out of scope. The document makes that possible for anybody who wants it in their own tool, and building one here is its own feature.
- The endpoints that need no credential, where an account is registered, a session is opened or the instance reports its health, are described alongside the rest rather than in a document of their own.
- An instance describes itself. The addresses a caller reads in the document are the addresses of the instance that served it, which is what a self-hosted instance needs and what a single published copy could not give it.
- The examples the reference already carries are reused rather than invented again, so the two cannot show a caller different answers for the same endpoint.
- The six-endpoint OpenAPI file under `specs/003-api-docs-portal/contracts/` is a spec-kit artifact of an earlier feature, is loaded by nothing and is long out of date. It is not the deliverable, and it is not where the deliverable goes: constitution principle XI caps a file there at 60 lines, which a document covering the whole API cannot meet.
- Webhooks and the MCP server are out of scope. Webhooks are the outbound direction and describe events rather than endpoints, and the MCP server exposes this same API rather than a second one.
