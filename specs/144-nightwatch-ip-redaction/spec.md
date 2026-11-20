# Feature Specification: Withhold the visitor's network address from monitoring

**Feature Branch**: `2026-11-20-nightwatch-ip-redaction`

**Created**: 2026-11-20

**Status**: Draft

**Input**: User description: "for privacy reasons, add this: keep only the user ID (remove name and email) with `Nightwatch::user(fn ($user) => [])`, and hide the IP address on requests with `Nightwatch::redactRequests(function ($request) { $request->ip = null; })`. New branch." Follow-up: "apparently to put in AppServiceProvider::boot()".

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A request trace carries no network address (Priority: P1)

Somebody uses Monica. Every request they make is traced by the application monitoring service so the
team can find errors and slow screens. Today each trace carries the network address the request came
from. That address can locate a person and link their visits together, and the team has no use for
it when diagnosing a fault. After this change, a trace reaches the monitoring service with no network
address in it.

**Why this priority**: It is the only part of the request that is not already in place, and it is
personal data leaving the instance for a third party.

**Independent Test**: Build a request trace carrying an address, apply the application's redaction to
it, and confirm the address is empty while the rest of the trace is unchanged.

**Acceptance Scenarios**:

1. **Given** a signed in person makes a request from a known address, **When** the trace is prepared for the monitoring service, **Then** the trace holds no address.
2. **Given** a visitor who is not signed in makes a request, **When** the trace is prepared, **Then** the trace holds no address.
3. **Given** a request whose address is withheld, **When** the trace is prepared, **Then** the existing redactions of the URL (tokens, signatures, search terms) and of the Telegram secret header still apply.

---

### User Story 2 - A trace identifies a person by number only (Priority: P2)

When a request is made by somebody signed in, the trace names who made it so the team can tell how
many people an error reaches. It must name them by their internal number only, never by their name or
email address.

**Why this priority**: This is already the shipped behaviour. The story is kept so the requirement is
written down and stays proven, not because anything changes.

**Independent Test**: Resolve the user details the application hands the monitoring service for a
signed in person and confirm they hold the identifier and nothing else.

**Acceptance Scenarios**:

1. **Given** a signed in person with a name and an email address, **When** the trace names who made the request, **Then** it carries their identifier and neither their name nor their email address.

---

### Edge Cases

- A request with no address at all (a console or test request) is traced exactly as before, with an empty address.
- The address is withheld on every request, including the ones that are later sampled out or rejected; nothing depends on the address being present.
- A request that passed through a proxy or a CDN carries the address a second time, in the headers the proxy adds (`X-Forwarded-For`, `X-Real-IP`, `Forwarded`, `CF-Connecting-IP`, `True-Client-IP`). The trace sends request headers, so those MUST be removed as well, or the address leaves anyway.
- An exception, a query or a queued job traced inside a request carries no address of its own, so nothing else needs redacting for this change.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The application MUST withhold the network address from every request trace before it leaves for the monitoring service.
- **FR-001a**: The application MUST remove from the trace every request header that carries the client's address as set by a proxy or a CDN: `X-Forwarded-For`, `X-Real-IP`, `Forwarded`, `CF-Connecting-IP` and `True-Client-IP`.
- **FR-002**: Withholding the address MUST NOT fail the request or the trace: the withheld value MUST be one the monitoring service accepts.
- **FR-003**: The existing request redactions (URL tokens, signatures, search terms, the Telegram secret header) MUST continue to apply unchanged.
- **FR-004**: A trace MUST identify the person who made a request by their identifier only, with no name and no email address. This is already met and MUST stay met.
- **FR-005**: The configuration MUST be set where the application already configures the monitoring service at start up, so all of its privacy rules sit in one place.

### Key Entities

- **Request trace**: What the monitoring service receives about one request: the URL, the route, the duration, the status, the headers, the person's identifier, and today their network address, both in its own field and in any forwarding header a proxy added.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 0% of request traces sent to the monitoring service carry a network address, whether in the address field or in a forwarding header.
- **SC-002**: 0% of traces carry a person's name or email address.
- **SC-003**: No request fails, and no error is reported to the monitoring service, because of the redaction.
- **SC-004**: Every redaction that existed before the change still applies after it.

## Assumptions

- The snippet in the request is a description of intent rather than code to paste. Its user part is already in place: the application has sent only the identifier since it adopted the monitoring service, and the service always adds the identifier itself, so returning nothing beyond it and returning the identifier explicitly send the same data. No change is made there.
- Its address part cannot be used as written. The monitoring service requires the address to be text, so setting it to nothing raises an error instead of hiding it. The address is withheld by setting it to empty text, which is what the service itself records when a request has no address.
- "Put it in the start up of the application" is met by the existing method that configures the monitoring service, which the start up already calls. A second, separate place would split the privacy rules in two.
- The address is withheld outright, not truncated or hashed. Nobody has asked for a coarse location or a way to correlate visits, and an empty value is the simplest thing that keeps the data out.
- The request as written hides only the address field. The monitoring service also sends the request headers, and its default header redaction covers credentials only, so a deployment behind a proxy or a CDN would still send the address in a forwarding header. Removing those headers is included because it is the only way to meet the stated goal; it is the same mechanism the application already uses to remove the Telegram secret header. The five header names are the common ones; a proxy using an unusual header would need adding.
- No user facing copy, translation, API endpoint, MCP tool or product documentation is affected. A changelog entry is a judgement call for planning, since the change is invisible in the product but is a privacy improvement people may care about.
