# Feature Specification: QUERY requests in the API reference

**Created**: 2026-11-22

**Status**: Draft

**Input**: User description: "Add api documentation for all QUERY requests"

## Clarifications

### Session 2026-11-22

- Q: In what form does the reference document each QUERY request: a QUERY form on the existing GET
  page, a reference entry of its own, or only the gaps? → A: A reference entry of its own for every
  QUERY request. This supersedes the 126 clarification of 2026-11-16 ("one guide page with a worked
  example, and one line on each read endpoint page"). Superseded on 2026-11-23, see below.
- Q: Does the Markdown copy of the whole reference print every QUERY entry in full? → A: Yes,
  every QUERY entry in full, like every other entry.

### Session 2026-11-23

- After reading the built reference, the requester replaced the separate entries with one page per
  read endpoint: "rassemble les en 1 seule page". The page describes GET and QUERY together, the
  sidebar shows the two methods one above the other next to the page name, and the samples show the
  GET request and then the QUERY request below it. The answer is the same for both, so it is shown
  once. Tried on "Get your profile" first, then confirmed for every read endpoint.
- The Markdown answer of 2026-11-22 still holds: the Markdown copy of every read endpoint carries
  its QUERY request in full, inside the same section.

## Context

Every read endpoint of the JSON API answers QUERY since `specs/126-api-query-method`. That feature
documented QUERY in four places:

| Surface | What it held about QUERY before this feature |
| --- | --- |
| The QUERY guide in "Getting started" | A worked request with a JSON body, the refusal of a parameter sent twice, paging, the `Accept-Query` header |
| Each read endpoint's reference page | One sentence saying the endpoint also answers QUERY, linking to the guide. The parameter table, the request samples (cURL, JavaScript, PHP) and the method badge show GET only |
| The Markdown copy of each reference page | The same one sentence |
| The machine readable description | A QUERY operation on every read path, with the parameters as body fields. The suite fails when one is missing |
| The API changelog | One "Added" entry for QUERY |

So a developer who wants to send QUERY to a given endpoint has to read the guide, then translate the
GET sample and the parameter table themselves. This feature describes the QUERY request on the page
of every read endpoint, beside its GET request.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read both requests of a read endpoint on one page (Priority: P1)

A developer who wants to search the contacts of a vault opens "List contacts" and finds both ways to
ask: the GET request with its parameters in the address, and the QUERY request with the same
parameters in the body, each ready to run in every sample language, above the one answer both give.

**Why this priority**: It is what the requester asked for, and it removes the translation of the GET
sample that the guide alone left to the reader.

**Independent Test**: Open the page of any read endpoint, copy its GET sample and its QUERY sample,
run both against a test account, and compare the answers.

**Acceptance Scenarios**:

1. **Given** the reference, **When** a developer opens the page of the vault contact list, **Then**
   the page shows both methods above the address, a sentence saying the two return the same answer
   and where each sends its parameters, a GET sample and below it a QUERY sample, and one answer.
2. **Given** the QUERY sample of that page run against a vault holding Michael, Dwight and Jim,
   **When** the answer comes back, **Then** it is identical to the answer of the GET sample.
3. **Given** the page of an endpoint with no parameters, such as "Get your profile", **When** a
   developer reads it, **Then** it says the endpoint takes no parameters, so both requests return the
   same answer, and its QUERY sample sends no body.
4. **Given** the page of any read endpoint, **When** a developer reads it, **Then** it links to the
   QUERY guide for how QUERY works.
5. **Given** the page of a read endpoint with parameters, **When** a developer reads its status
   codes, **Then** they include the 422 QUERY answers when a parameter is sent both in the address and
   in the body, and say it applies to QUERY.

---

### User Story 2 - See which requests answer QUERY in the navigation (Priority: P2)

A developer browsing a resource in the sidebar sees, for each read endpoint, GET and QUERY one above
the other on the left and the page name on the right.

**Why this priority**: It tells a reader at a glance which requests take QUERY, without doubling the
length of the sidebar.

**Independent Test**: Browse any resource with read endpoints in the sidebar.

**Acceptance Scenarios**:

1. **Given** the sidebar of the reference, **When** a developer opens the contacts resource, **Then**
   every read endpoint shows GET and QUERY stacked next to its name, and every other request shows
   its one method.
2. **Given** the sidebar filter, **When** a developer types "query", **Then** the read endpoints stay
   listed.

---

### User Story 3 - Read the QUERY request in the Markdown copy (Priority: P3)

A developer, or an assistant reading on their behalf, opens the Markdown copy of a read endpoint and
finds the same content as on the HTML page.

**Why this priority**: The Markdown copy exists for readers who never see the HTML page.

**Independent Test**: Open the Markdown copy of any read endpoint and compare it with its HTML page.

**Acceptance Scenarios**:

1. **Given** the Markdown copy of a read endpoint, **When** it is read, **Then** it names both
   methods on the address, holds the sentence about the two requests, a GET request, a request with
   QUERY, and the answer once.
2. **Given** the Markdown copy of the whole reference, **When** it is read, **Then** every read
   endpoint carries its QUERY request in full.

---

### Edge Cases

- A read endpoint behind a flag that is off for every account: its page is left out, and its QUERY
  request with it.
- A read endpoint whose example parameters are all empty: the QUERY sample sends no body.
- The QUERY guide in "Getting started" is a guide, not an endpoint, and gets no QUERY request.
- A path that answers only writes (POST, PUT, PATCH, DELETE) shows its one method.
- A read endpoint that returns a file rather than JSON (a support attachment's content): its QUERY
  request returns the same file.
- The changelog names QUERY requests by method and address. Each one links to the page of its read
  endpoint.
- A read endpoint added later: the suite fails when its QUERY request is not documented.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The page of every route of the JSON API that answers QUERY MUST describe its QUERY
  request beside its GET request. No QUERY request has a page of its own.
- **FR-002**: The page MUST show both methods above the address, and a sentence saying the two
  return the same answer. With parameters, it MUST say GET sends them in the address and QUERY in
  the request body. Without, it MUST say the endpoint takes no parameters.
- **FR-003**: The page MUST show the GET sample and, below it, the QUERY sample, in each sample
  language, and the answer once.
- **FR-004**: The QUERY sample MUST send the example parameters as a JSON body with the matching
  content type, or no body and no content type when there are no example parameters. The GET sample
  MUST carry its example parameters in the address and send no body.
- **FR-005**: The QUERY request's description, answer, status codes and rate limit MUST come from the
  GET request, so the two cannot drift. When the endpoint takes parameters, the status codes of the
  page MUST add the 422 QUERY answers for a parameter sent both in the address and in the body,
  saying it applies to QUERY.
- **FR-006**: The page MUST link to the QUERY guide, and MUST NOT repeat its explanation of how QUERY
  works.
- **FR-007**: The sidebar MUST show GET and QUERY one above the other, next to the page name, for
  every read endpoint, and the filter MUST match either method.
- **FR-008**: The Markdown copy of a read endpoint, alone and in the copy of the whole reference,
  MUST name both methods, hold the sentence of FR-002, a GET request and a request with QUERY.
- **FR-009**: Every existing address of a reference page MUST keep opening that page.
- **FR-010**: The QUERY request MUST follow the flags of its GET request.
- **FR-011**: The suite MUST fail when a route that answers QUERY has no documented QUERY request, or
  when a documented QUERY request names a route that does not answer QUERY.
- **FR-012**: The machine readable description MUST keep its QUERY operation on every read path,
  unchanged in what it declares.
- **FR-013**: The API changelog MUST carry one entry saying every QUERY request is now described on
  its GET page, marked as not breaking.

### Key Entities

- **Read endpoint**: an address of the JSON API that answers GET, and therefore QUERY. 123 routes
  today.
- **Reference page**: the public page documenting one endpoint, with its description, parameter
  table, request samples, response sample, status codes and rate limit, its place in the sidebar,
  and its Markdown copy.
- **QUERY request**: the QUERY form of a read endpoint, derived from its GET request and described on
  the same page.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of the routes that answer QUERY have their QUERY request described, and no
  documented QUERY request names a route that does not, checked by the suite against the route list.
- **SC-002**: On every read endpoint page, the QUERY sample run as copied returns the same answer as
  the GET sample.
- **SC-003**: The reference keeps the number of pages it had before this feature.
- **SC-004**: Every link to an existing reference page keeps opening the same page.

## Assumptions

- "All QUERY requests" means every route of the JSON API that answers QUERY, which is every route
  that answers GET. The web screens, the MCP server and CardDAV are out of scope.
- How QUERY behaves does not change. This feature documents it and changes no answer. The middleware
  puts the body into the parameters the controller reads, so a controller answers QUERY as it
  answers GET with the same parameters.
- The documentation ships to every reader at once, behind no flag, as 126 did.
- The QUERY guide, its worked example and the `Accept-Query` explanation stay as they are.
- The samples show a JSON body, the format the guide leads with. The form encoded body stays
  documented in the guide only.
