# Feature Specification: QUERY method on the API

**Created**: 2026-11-16 | **Status**: Draft

**Input**: User description: "Accept http QUERY word for all api"

## Clarifications

### Session 2026-11-16

- Q: Ship behind a flag or for everybody at once? → A: For everybody at once, with no flag.
- Q: How much should the reference say about QUERY? → A: One guide page with a worked example, and one line on each read endpoint page.
- Q: Does a QUERY body get a size limit of its own? → A: No, it gets the same limit as a write request's body.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read with the parameters in the body (Priority: P1)

A developer filtering a long contact list or searching a vault sends the parameters in the request
body with the QUERY method instead of packing them into the address. The address stays short, the
parameters stay out of proxy and server access logs, and the request is still recognised as a safe
read that may be retried.

**Why this priority**: It is the feature. Without it nothing else here has a purpose.

**Independent Test**: Send the same search to one list endpoint twice, once as GET with the
parameters in the address and once as QUERY with them in the body, and compare the two answers.

1. **Given** a vault holding Michael, Dwight and Jim, **When** a developer sends QUERY to the vault
   search with `{"query": "Dwight"}` in the body, **Then** the answer is identical to GET with
   `?query=Dwight`.
2. **Given** a list of 60 contacts, **When** QUERY carries `{"page": 2, "per_page": 25}`, **Then** the
   answer holds the second page and the same `meta` as the equivalent GET.
3. **Given** a parameter that GET refuses, **When** it is sent in a QUERY body, **Then** the answer is
   the same 422 with the same validation errors.
4. **Given** an endpoint that takes no parameters, **When** QUERY is sent with an empty body,
   **Then** it answers exactly as GET does.

---

### User Story 2 - Find QUERY in the reference (Priority: P2)

A developer reading the API reference or importing the OpenAPI document into a client sees, for
every read endpoint, that QUERY is accepted and that its parameters may travel in the body.

**Why this priority**: An undocumented method is used by nobody, and the constitution requires the
API to be publicly documented.

**Independent Test**: Open the reference page of any read endpoint and the OpenAPI document, and
check that both name QUERY for it.

1. **Given** the reference page of a read endpoint, **When** a developer reads it, **Then** one line
   says the endpoint also answers to QUERY and links to the QUERY guide.
2. **Given** the QUERY guide in "Getting started", **When** a developer reads it, **Then** it shows
   one worked request with a JSON body, the refusal of a parameter sent twice, and how to page.
3. **Given** the OpenAPI document, **When** it is loaded into a client, **Then** every read path
   carries a QUERY operation beside its GET operation.
4. **Given** the API changelog, **When** a developer opens it, **Then** an "Added" entry dated on
   release names QUERY and the number of endpoints it touches.

---

### Edge Cases

- The same parameter is in both the address and the body: refused with a 422 naming the parameter
  (decided with the requester on 2026-11-16).
- A parameter only in the address and another only in the body: both are read, as one set.
- A malformed body, or one of another content type: handled exactly as the API's write endpoints
  handle the same body today, so QUERY brings no new error of its own.
- An oversized body: refused at the same size limit as a write request's body, with no limit of its own.
- QUERY sent to a path that only writes (POST, PUT, PATCH, DELETE): answered with a 405 and an
  `Allow` header listing the methods the path accepts, exactly as any other unsupported method.
- A preflight from a browser asking for QUERY: the cross-origin answer lists QUERY as allowed.
- An unauthenticated QUERY: answered with the same 401 as GET.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every endpoint of the JSON API that answers to GET MUST also answer to QUERY at the same address.
- **FR-002**: A QUERY request MUST accept its parameters in a JSON body, a form encoded body, the address, or a mix of the body and the address.
- **FR-003**: For the same parameters, a QUERY answer MUST be identical to the GET answer: status, body, headers that describe the content, validation errors and refusals.
- **FR-004**: A QUERY request MUST NOT change any data and MUST NOT be written to the account's journal, exactly as GET is not.
- **FR-005**: A QUERY request MUST pass the same authentication, API key scopes, account and vault checks, and rate limit as GET on the same endpoint, and count against the same allowance.
- **FR-006**: A paginated list MUST read `page` and `per_page` from the body as it reads them from the address.
- **FR-007**: Paths that do not answer to GET MUST NOT start answering to QUERY.
- **FR-008**: The reference MUST hold one QUERY guide with a worked example, and every read endpoint page MUST carry one line saying QUERY is accepted, linking to that guide.
- **FR-009**: The OpenAPI document MUST declare a QUERY operation on every path that has a GET operation.
- **FR-010**: The API changelog MUST carry one "Added" entry for QUERY, marked as not breaking.
- **FR-011**: A read endpoint added after this feature MUST answer to QUERY, and the suite MUST fail when one does not.
- **FR-012**: Every answer from a path that accepts QUERY MUST carry `Accept-Query: application/json, application/x-www-form-urlencoded`, so a HEAD request tells a client which body formats it takes.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of the read endpoints in the JSON API answer to QUERY, checked by the suite against the route list.
- **SC-002**: For every read endpoint that takes parameters, a QUERY with the parameters in the body returns the same answer as the equivalent GET.
- **SC-003**: No existing GET request changes its answer, measured by the existing API tests passing unchanged.
- **SC-004**: A developer can find QUERY on any read endpoint's reference page without leaving it.

## Assumptions

- "All api" means the JSON API under `/api`. The web screens, the MCP server, CardDAV and the docs portal pages themselves are out of scope.
- QUERY is a safe, idempotent read with a body, as the HTTP working group defines it. Writes therefore keep their methods, and no write endpoint is given QUERY.
- API keys carry no read only scope, so QUERY adds no scope rule: it needs exactly what GET needs.
- QUERY ships to every account in one release, behind no flag, since no GET answer changes.
- The installed framework already routes the QUERY method, so no dependency changes.
- Several API controllers read their parameters from the address only. Making them read the body is part of this feature, not a separate one.
- List answers carry `meta.page`, `per_page` and `total` and no page links, so the next page is asked for by repeating QUERY with a new `page`, and no link can lose body parameters.
- Caching is for later, and for GET as much as QUERY: the API sends no cache headers today. Shared caches cannot store an answer to a request carrying `Authorization`, so the useful step is conditional requests (`ETag` and 304), which the framework already honours for QUERY.
