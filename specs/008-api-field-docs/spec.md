# Feature Specification: Response fields in the API reference

**Feature Branch**: `2026-09-01-api-field-docs`

**Created**: 2026-09-01

**Status**: Draft

**Input**: User description: "in the api, currently we only show the json that is returned after an api call, but we do not provide documentation for them. we need to have a proper documentation for each of these fields, based on this design" (design: `API portal v2.dc.html`, Monica v3 design project)

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Know what comes back before writing any code (Priority: P1)

The reference gives a caller one sentence about what an endpoint returns, and one example of a response. Everything else they have to infer. Is `updated_at` ever absent, or only sometimes? Is `colour` free text or one of a fixed set? Is `created_at` a date or a number? The example answers those questions for the one record that happens to be in it, and answers them wrongly for every record that differs. So a caller writes a client, runs it, gets a shape they did not expect, and goes back to guessing.

Every field a response carries should be named in the reference, with its type and a sentence saying what it is, including the fields inside a list row and inside the paging block. A reader should be able to write a client that handles every field correctly without ever calling the endpoint.

**Why this priority**: It is the whole of the feature and it stands alone. A caller who has only this can already build against the API without experimenting on it, which is the thing the example response cannot give them.

**Independent Test**: Open any endpoint in the reference and confirm that every field its example response carries is also described in a list above it, with a name, a type, and a sentence, and that a reader can tell a field inside a list row from a field at the top of the answer.

**Acceptance Scenarios**:

1. **Given** a reader on an endpoint that answers with one record, **When** they read the Returns section, **Then** the summary sentence is still there, and beneath it every field of that record is listed with its name, its type and a description.
2. **Given** an endpoint that answers with a page of records, **When** they read the Returns section, **Then** the fields of a row are shown as belonging to the list rather than as fields of the answer, and the paging block and its counters are shown the same way.
3. **Given** an endpoint that answers with nothing at all, **When** they read the Returns section, **Then** it says so in a sentence and lists no fields, rather than showing an empty list.
4. **Given** an endpoint whose answer carries something beside the record, such as a value shown only once, **When** they read the Returns section, **Then** that is listed too, at the level it actually appears.
5. **Given** a reader who prefers plain text, **When** they read the same endpoint as Markdown, **Then** the same fields are there, with the nesting still legible, because that rendering is what a program and a screen reader are given.
6. **Given** a reader using a screen reader, or a reader who cannot see the indentation, **When** they reach a field inside a list row, **Then** which parent it belongs to is announced or written rather than being carried by indentation alone.

---

### User Story 2 - Trust the field list, because it cannot go stale (Priority: P2)

A field list that has drifted from the truth is worse than no field list, because a reader has no way to tell. A field quietly added to a response is missing from the reference; a field renamed is documented under a name nobody will receive; a field removed is documented as though it still arrives. Each of those is a caller building on something untrue, and none of them is visible to the person who caused it.

The reference should be checked against what the endpoints actually answer, by calling them, so that a change to what a response carries and a change to the reference cannot happen apart from each other.

**Why this priority**: It is what makes the first story worth having a year from now. It is second because there is nothing to check until the fields are written.

**Independent Test**: Add a field to one endpoint's response without touching the reference, and confirm the test suite fails and names that field and that endpoint. Then remove a field from the reference without touching the response, and confirm it fails again.

**Acceptance Scenarios**:

1. **Given** every documented endpoint, **When** the suite runs, **Then** each one is actually called and the field names the reference claims are compared against the field names the answer carries.
2. **Given** a response that gains a field, **When** the suite runs, **Then** it fails, and says which endpoint and which field the reference does not mention.
3. **Given** the reference that names a field a response does not carry, **When** the suite runs, **Then** it fails, and says which endpoint and which field nothing answers with.
4. **Given** an endpoint that answers with no body, **When** the suite runs, **Then** it passes only if the reference documents no fields for it.
5. **Given** a field that one endpoint of a pair carries and the other does not, **When** the suite runs, **Then** each of the two is checked against its own answer rather than against a shape they are assumed to share.
6. **Given** a reader, **When** they rely on the field list, **Then** they are relying on something the suite proves, which is the same guarantee the reference already gives about which endpoints exist.

---

### User Story 3 - Know the limits without discovering them by trial (Priority: P3)

A name and a type leave out most of what a caller needs. That `name` is a string does not say it stops at 255 characters. That `colour` is a string does not say it is one of eight values. That `description` is a string does not say it comes back as nothing when the vault has none. That `created_at` is a timestamp does not say it is a count of seconds rather than a written date. A caller who is not told finds out by sending something and reading a refusal, or worse, by writing a client that breaks on the first record that differs.

Each description should say what the name and the type do not imply: how long a value can be, which set it is drawn from, when it is empty, what unit it is in, and where it comes from when it is worked out rather than stored.

**Why this priority**: It is the difference between a field list that saves a reader one guess and one that saves them all of them. It is last because it is an improvement to descriptions the first story will have already written, so it can land after them without any of them being wrong in the meantime.

**Independent Test**: Take the fields whose type alone is ambiguous, a bounded string, a value from a fixed set, a field that is sometimes empty, and a time, and confirm each description states the bound, the set, the emptiness and the unit.

**Acceptance Scenarios**:

1. **Given** a field holding text with a maximum length, **When** a reader reads its description, **Then** the maximum is stated, in characters.
2. **Given** a field whose value is one of a fixed set, **When** a reader reads its description, **Then** either the set is listed or the reader is told exactly where to find it.
3. **Given** a field that is sometimes absent or empty, **When** a reader reads its description, **Then** it says under what circumstances, rather than leaving them to discover it.
4. **Given** a field carrying a moment in time, **When** a reader reads its description, **Then** the form it arrives in is unambiguous.
5. **Given** a field that is derived rather than stored, or that means something other than its name suggests, **When** a reader reads its description, **Then** it says what it is really telling them.
6. **Given** a field whose name and type say everything about it already, **When** a reader reads its description, **Then** it is a plain sentence and does not invent a rule to look thorough.

---

### Edge Cases

- An endpoint answers with nothing. The Returns section is a sentence and no rows, and the check expects exactly that. Three endpoints are in this state today.
- An endpoint answers with a field its sibling does not. Reading one email carries the copy that was sent and listing them does not, so the two are described separately and each is checked against its own answer.
- An endpoint answers with something beside the record. Creating a credential carries the readable value once, next to the object rather than inside it, and it has to be documented where it actually appears or a reader will look for it in the wrong place.
- A field is a list of objects. The row's fields belong to the list, not to the answer, and a reader who cannot see the indentation still has to be able to tell.
- A field arrives as empty. Whether that means "not set", "not applicable" or "not yet known" is the description's job, since all three arrive looking identical.
- The paging block is the same three counters on every list. Repeating them per endpoint is right for a reader, who reads one page and not the whole reference.
- A field's value depends on the state of the record rather than on the endpoint. Its description says the whole set, not the one value the example happens to show.
- A response is shaped by who is asking, or by what the account holds. The check has to arrange a state it controls, or it will pass and fail depending on the database it runs against.
- A field is added to a response and the reference is updated in the same change. Nothing fails, which is the point.
- A field is added and the reference is not. The suite fails and names it.
- An endpoint is added with no field documentation at all. The suite fails, in the same breath as it already fails for an endpoint added without a reference entry.
- A description grows long enough to bury the rule it states. Length is not the measure; a reader finding the bound is.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every endpoint in the reference MUST document each field its answer carries, with the field's name as a caller receives it, its type, and a description.
- **FR-002**: The one sentence summary of what an endpoint returns MUST remain, above the field list, because it answers a different question from the fields.
- **FR-003**: A field that sits inside another, a row of a list or a counter of the paging block, MUST be shown as belonging to its parent rather than as a field of the answer.
- **FR-004**: The parent a nested field belongs to MUST be conveyed by something other than indentation alone, so that a reader who cannot see the indentation, or who is being read the page, still knows.
- **FR-005**: An endpoint that answers with no body MUST document no fields, and its summary MUST say there is nothing to expect.
- **FR-006**: A value that arrives beside the record rather than inside it MUST be documented at the level it arrives.
- **FR-007**: Types MUST be drawn from one small vocabulary used consistently across the reference, so that the same kind of value is never described two ways in two places.
- **FR-008**: A description MUST state what the field's name and type do not imply. In particular: the maximum length of a bounded piece of text, the set a value is drawn from or where that set can be read, the circumstances under which the field is empty, the form a moment in time arrives in, and what a derived field is actually telling the reader.
- **FR-009**: A description MUST NOT invent a rule in order to look complete. A field whose name and type already say everything gets a plain sentence.
- **FR-010**: The field list MUST appear both on the reference page and in the plain text rendering of the same endpoint, since the plain text rendering is what a program, and anybody reading with assistance, is handed.
- **FR-011**: The suite MUST call every documented endpoint and compare the field names the reference claims against the field names the answer actually carries, failing when either side names something the other does not, and naming the endpoint and the field when it fails.
- **FR-012**: That check MUST arrange the state it needs itself, so its result depends on the reference and the code and on nothing else.
- **FR-013**: The check MUST cover each endpoint separately, never one endpoint standing in for another that is assumed to answer with the same shape.
- **FR-014**: An endpoint added to the API without field documentation MUST fail the suite, the way an endpoint added without a reference entry already does.
- **FR-015**: Field names, types and descriptions MUST NOT be translated. They are part of the reference, which is written in English on a surface that is not translated, and a field name is a JSON key rather than copy.
- **FR-016**: Nothing about an existing endpoint's behaviour, address, or answer may change. This feature describes what the API already does and MUST NOT alter it.

### Key Entities

- **Response field**: One thing a caller receives. It carries the name it arrives under, its type, what it belongs to when it is not at the top of the answer, and a description that says what it is and what its limits are.
- **The type vocabulary**: The small closed set of words the reference uses for what kind of value a field holds. Its purpose is that two fields of the same kind read the same way in two different places.
- **The field list of an endpoint**: All of the above for one endpoint, in the order a reader meets them in the answer, which is what the check compares against reality.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody can write a working client for any endpoint from the reference alone, handling every field it returns, without calling the endpoint first to find out what arrives.
- **SC-002**: Every field that every documented endpoint returns is described in the reference, and the reference describes no field that no endpoint returns. Both directions are proven by the suite rather than by review.
- **SC-003**: A reader can answer, from the reference alone and for any field: how long it can be when it is text, which values it can take when it is drawn from a set, when it arrives empty, and what form it arrives in when it is a time.
- **SC-004**: A change to what an endpoint returns cannot reach the default branch without the reference changing with it.
- **SC-005**: A reader using a screen reader, or reading the plain text rendering, gets the same field information as a reader looking at the page, including which parent each nested field belongs to.
- **SC-006**: The reference stays readable: a reader looking for one field finds it without reading the whole page, and the summary still tells somebody who wants the gist what they get.

## Assumptions

- **The scope is the Returns block of the reference, and its content.** The design also shows a breadcrumb, previous and next links at the foot of an endpoint, a keyboard shortcut that focuses the filter, and its own light only palette with restyled chips. None of that is in scope here: it is a separate change, and the portal keeps the tokens and the dark mode it already has. The design was read for the shape of the field list, which is the part the description asked for.
- **Descriptions carry the rules, and this was asked for explicitly.** The maximum length of a string, and anything else a name and a type do not imply, belongs in the description so that a reader knows what they will receive. This is FR-008 and it is the reason User Story 3 exists as its own slice rather than as a footnote to the first.
- **Correctness is proven by calling the endpoints, which was chosen over the cheaper option.** Comparing the reference against the example response already in each reference file would have been much less work, and would have caught the reference disagreeing with itself while missing the case that matters most: a response that changes and a reference that does not. The check calls the real thing.
- **Nesting goes three deep, not one.** Written before the answers were read, this said one level was enough. It is not: every record the API hands out is wrapped, so a value on a listed record sits inside the record, inside the list. The field list therefore has to carry a field at any depth rather than a parent and its children, and the way a field names its parent has to work at three levels as readily as at one.
- **The reference keeps its present structure**, one entry per group of endpoints, so a field list is written where the endpoint is already described rather than in a new place that could disagree with it.
- **Nineteen endpoints are in scope**, which is every endpoint the API answers today. Three of them answer with no body, so sixteen have fields to describe. The five pages of the getting started guide are prose rather than endpoints and have no field lists.
- **The example responses stay.** A field list and an example answer different questions: the list says what can arrive, the example shows one thing that did. Neither replaces the other.
- **No behaviour changes.** This is documentation about code that already exists and already has tests. If writing a description reveals that an endpoint does something surprising, that is a finding to raise, not a thing to quietly fix here.
