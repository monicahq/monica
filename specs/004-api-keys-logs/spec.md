# Feature Specification: API keys and logs endpoints

**Feature Branch**: `2026-08-28-api`

**Created**: 2026-08-28

**Status**: Draft

**Input**: User description: "I want to implement the new api methods for the following: API keys (list keys, get a key, create a key, delete a key) and Logs (list logs, get a log), based on how we did it in the beaver codebase. You need to implement api methods, routes, and documentation."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Manage API keys without opening the app (Priority: P1)

Somebody building on top of their own data holds one API key, created by hand in the security screen. From a script, they want to see which keys exist, read what one key is (its name, when it was made, when it was last used), mint a new key for a new integration, and revoke a key that leaked or is no longer used. Today all four of those force them into the browser.

**Why this priority**: It is the largest of the two groups, it is the only one with write operations, and revoking a leaked key from a script is the one thing here somebody may need urgently. It also closes the loop on the product promise that everything is reachable through the API, since the credential for the API was the one thing the API could not talk about.

**Independent Test**: Sign in for a token, then use only that token to list keys, read one, create a second, and revoke it. Nothing in the browser is needed, and the security screen afterwards shows exactly what the calls did.

**Acceptance Scenarios**:

1. **Given** a caller holding a valid key and two keys on the account, **When** they ask for the list of keys, **Then** they get both keys, newest first, with no secret value in the response.
2. **Given** a caller holding a valid key, **When** they ask for one of their keys by its identifier, **Then** they get that key's name, creation time and last used time.
3. **Given** a caller holding a valid key, **When** they create a key with a name, **Then** the response carries the plain text key exactly once, and the account journal records that a key was created.
4. **Given** a caller holding a valid key, **When** they create a key with no name, or a name shorter than three characters, **Then** the request is refused as invalid and no key is minted.
5. **Given** a caller holding a valid key, **When** they revoke one of their keys, **Then** the response is empty, the key stops being accepted immediately, and the account journal records the revocation with the name the key had.
6. **Given** a caller holding a valid key, **When** they ask for or revoke a key belonging to somebody else, **Then** the answer is not found, and the other person's key is untouched.

---

### User Story 2 - Read the account journal from a script (Priority: P2)

The account already keeps a journal of what happened in it, and the settings screen shows it a few entries at a time. Somebody wants to pull that journal into their own tooling: a weekly digest, an archive, or a check that nothing was touched while they were away.

**Why this priority**: It is read only, so nothing depends on it and nothing breaks without it, but it is the natural companion of the keys: the journal is where the answer to "who used which key, and when" is written.

**Independent Test**: Create some activity in an account, then read the journal through the API and confirm the same entries the settings screen shows come back, in the same order, with a readable sentence for each.

**Acceptance Scenarios**:

1. **Given** an account with more entries than one page holds, **When** the caller asks for the list of logs, **Then** they get one page of entries, newest first, together with the means to ask for the next page.
2. **Given** an account with entries, **When** the caller asks for one entry by its identifier, **Then** they get who performed the action, what the action was, its parameters, a readable sentence describing it, and when it happened.
3. **Given** an entry the application wrote on its own, with nobody behind it, **When** the caller reads the journal, **Then** that entry is present and its author reads as the system rather than as a person.
4. **Given** a log entry belonging to another account, **When** the caller asks for it by its identifier, **Then** the answer is not found.
5. **Given** a caller who tries to create, change or remove a log entry, **Then** no such operation exists, because the journal is written by the application and never edited.

---

### Edge Cases

- A caller revokes the very key they are calling with. The call succeeds, and every later call with that key is refused as unauthenticated.
- A caller creates a key while holding a key. This is allowed, and it is how a key is rotated without touching the browser. Every creation is written to the journal, so a key minting other keys is visible after the fact.
- A key is never shown again after creation. A caller who loses the value has to create another one and revoke the old.
- The name given to a key is not required to be unique. Two keys may carry the same name, and the identifier is what tells them apart.
- A log entry names an object that has since been deleted, or a person who has since been removed. The entry still reads, falling back to the address recorded at the time.
- The journal is asked for a page size that is absurdly large or zero or negative. The page size is brought back into the allowed range rather than the request being refused.
- The caller has no keys at all, which cannot happen while they are calling with one, or no journal entries yet. The list is empty rather than an error.
- A log entry describes a change to a value its author marked sensitive. The entry says the value changed and not what it changed to, exactly as the settings screen shows it.

## Requirements *(mandatory)*

### Functional Requirements

#### API keys

- **FR-001**: The API MUST offer a way to list the keys of the caller, newest first, with each key carrying its identifier, its name, when it was created, when it was last used (empty when never), and a link to itself.
- **FR-002**: The API MUST offer a way to read one key of the caller by its identifier.
- **FR-003**: The API MUST offer a way to create a key from a name, and MUST return the plain text key in that one response and never again.
- **FR-004**: The API MUST refuse a creation whose name is missing, shorter than three characters, or longer than two hundred and fifty five, and MUST say which field is at fault.
- **FR-005**: The API MUST offer a way to revoke a key by its identifier, after which that key MUST NOT authenticate any further request.
- **FR-006**: A key MUST NOT appear in any response other than the one that created it. No listing and no read may return the secret value or its hash.
- **FR-007**: Reading, revoking or otherwise naming a key that belongs to somebody else MUST answer not found, and MUST leave that key working.
- **FR-008**: Creating and revoking a key MUST each write an entry to the account journal, naming the key, exactly as the equivalent screens already do.

#### Logs

- **FR-009**: The API MUST offer a way to list the journal of the account the caller belongs to, newest first, one page at a time, with the means to ask for the next page.
- **FR-010**: The list MUST accept a page size, MUST clamp it to a sane range, and MUST apply a default when none is given.
- **FR-011**: The API MUST offer a way to read one journal entry by its identifier.
- **FR-012**: A journal entry MUST carry its identifier, who performed the action, the action's name, its parameters, a readable sentence describing it, and when it happened.
- **FR-013**: The journal MUST be scoped to the account rather than to the person, so that entries the application wrote on its own are visible rather than hidden.
- **FR-014**: Asking for a journal entry from another account MUST answer not found.
- **FR-015**: The API MUST NOT offer any way to create, change or delete a journal entry.

#### Across both

- **FR-016**: Every endpoint MUST require a valid API key, and MUST answer unauthenticated to a caller without one.
- **FR-017**: Every endpoint MUST be rate limited on the same terms as the rest of the API.
- **FR-018**: Every response MUST use the envelope the API already uses: a type, a string identifier, the attributes, and a link to the resource itself, with times given as Unix integers.
- **FR-019**: Every endpoint MUST live under the existing version prefix, so that a caller who writes against it today keeps working when a later version lands beside it.
- **FR-020**: Every endpoint MUST appear in the public API reference, under an "API keys" group and a "Logs" group, in the order shown to the reader: list, read one, create, delete.
- **FR-021**: The reference MUST document, per endpoint, the path, the verb, the parameters, whether authentication is needed, the rate limit, what comes back, and a sample response whose shape matches what the endpoint actually returns.
- **FR-022**: The reference and the routes MUST stay in step in both directions: a route with no documentation and documentation with no route MUST both be caught by the test suite.

### Key Entities

- **API key**: a credential that acts as exactly one person. Carries a name given by its holder, a creation time, a last used time and an identifier. Its secret value exists in readable form only in the response that created it; only a hash of it is kept.
- **Log entry**: one action performed in an account, written once and never changed. Carries the account, the person behind it (or nothing when the application acted on its own), the address that person had at the time, the action, its parameters, what changed, the interface it came through and when it happened.
- **Account**: what both are scoped to. It holds exactly one person, so a caller's keys and their account's journal are what a token can reach, and nothing else.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody can mint a key, use it, and revoke it without opening the application in a browser, in a single script and under a minute.
- **SC-002**: All six operations shown in the reference sidebar (four for keys, two for logs) answer correctly, and each one is covered by an automated test for its success case, its refusal case and its cross account case.
- **SC-003**: A revoked key stops being accepted on the very next request, with no delay and no cache to wait out.
- **SC-004**: A secret key value appears in exactly one response body across the whole surface, and nowhere else, verified by a test that reads every other response.
- **SC-005**: The reader of the reference can copy a sample for any of the six operations and have it work against a live instance with only the key and the host changed.
- **SC-006**: The test that compares documented endpoints against registered routes passes in both directions, so the reference cannot drift from the code without the suite failing.
- **SC-007**: A journal of ten thousand entries is readable page by page, at the size one account's journal actually reaches, with every page answered from an indexed read. This was originally written as a promise that the last page costs no more than the first, which is a promise only cursor paging can keep. It was amended once the reference was found to have already published a page based convention, and keeping that convention was chosen over keeping this wording.

## Assumptions

- **The paths follow this application, not beaver.** Beaver groups both under `administration`, which this application has no equivalent of: keys live under settings security and logs under settings account. The endpoints are therefore assumed to be plural kebab case at the top of the version prefix (`api-keys`, `logs`), matching the sidebar groups in the reference.
- **Keys stay unscoped for now.** The keys this application mints grant everything the person can do. The constitution's Principle X calls for keys whose reach can be limited, and its own follow up records that as existing debt. Adding scopes would change what a key is, and it is assumed out of scope here. The endpoints are assumed to be shaped so that adding an abilities attribute later is additive rather than breaking.
- **A key can mint and revoke keys.** There is no separate, stronger credential to require, and beaver behaves this way. The journal is what makes it accountable.
- **The journal is not filterable in this version.** No filtering by action, by date range or by person. Just the page, newest first. Filtering is assumed to be a later addition.
- **Emails sent are a different thing.** The settings screen shows them next to the journal and beaver exposes them as a third group. They are assumed out of scope here, since the reference sidebar being mirrored names only API keys and Logs.
- **The administrator journal is a different thing.** Instance wide administrator activity is separate from the account journal and is assumed out of scope.
- **Existing behaviour is reused rather than reimplemented.** Key creation and revocation already exist as actions used by the web screens, including the journal entries they write. The API is assumed to call the same ones, so the two interfaces cannot drift.
- **The reader of the reference is not signed in.** The API reference is public, so the samples are assumed to use placeholder credentials and a placeholder host.
