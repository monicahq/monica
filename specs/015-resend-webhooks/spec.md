# Feature Specification: Resend delivery webhooks

**Feature Branch**: `20260904-resend-webhook`

**Created**: 2026-09-04

**Status**: Draft

**Input**: User description: "implement resend webhooks"

## Clarifications

### Session 2026-09-04

- Q: On a bounce or a complaint, does this feature also stop sending to that address, or only record it? → A: Record only. Suppression is its own feature.
- Q: How should a report that the provider never managed to send the email appear? → A: As a bounce. It writes the same instant.
- Q: Does a temporary bounce, such as a full mailbox, mark the email as bounced too? → A: Yes. Both bounce types are recorded alike.
- Q: Do we build the receiving endpoint, or consume the one the integration package ships? → A: Consume the package's. Build nothing.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Knowing whether an email arrived (Priority: P1)

Somebody opens Emails sent in their settings. Every row says "On its way", and always will, whatever became of the email. The provider knew an hour ago that the sign in link was delivered, or that the address rejected it, and nothing ever asked. This story fills that outcome in, so the badge on the screen and the same field in the API say what actually happened.

**Why this priority**: the screen, the API field and the three timestamps behind them already exist. Nothing writes two of the three, so the product currently states something it has not checked.

**Independent Test**: record an email as sent, hand the endpoint the provider's report for it, and read the outcome back off the screen and off the API.

**Acceptance Scenarios**:

1. **Given** an email in the journal, **When** the provider reports it delivered, **Then** the record carries the instant of delivery and reads as delivered everywhere.
2. **Given** an email in the journal, **When** the provider reports it bounced, **Then** the record carries the instant of the bounce and reads as bounced.
3. **Given** an email in the journal, **When** the recipient marks it as spam, **Then** the record carries the instant of the complaint and reads as a state of its own, not as a bounce.
4. **Given** an email already reported delivered, **When** the same report arrives a second time, **Then** nothing on the record changes.
5. **Given** a delivery report and a bounce report for one email, **When** they arrive in either order, **Then** the record reads as bounced.

---

### User Story 2 - Nothing unproven reaches the journal (Priority: P2)

The endpoint that receives these reports is public, carries none of the protections a signed in screen does, and stands open on every instance whether or not that instance sends mail through the provider. Only a report proven to come from the provider may change anybody's journal. Where no shared secret is configured nothing can be proven, so nothing is acted on, which is the state every instance sending mail some other way is in.

**Why this priority**: this is the condition on shipping story 1, not a later increment. It is second because it is testable on its own, not because it can wait.

**Independent Test**: post a well formed report with no signature, with a wrong signature, and with none configured, and confirm the journal is untouched in all three cases.

**Acceptance Scenarios**:

1. **Given** a configured secret, **When** a report arrives without a valid signature, **Then** it is refused and nothing changes.
2. **Given** no configured secret, **When** any report arrives, however well formed, **Then** nothing changes, whatever answer the endpoint gives it.

---

### User Story 3 - Everything else the provider says is heard and dropped (Priority: P3)

The provider sends far more than three kinds of report: opens, clicks, delays, suppressions, and news about contacts and domains that concern us not at all. None of them may make the endpoint fail, because a failure is retried, and a retry that keeps failing becomes noise somebody has to read. Opens and clicks are dropped deliberately and not for now: recording when somebody read their own security email is surveillance, and this product does not do it.

**Why this priority**: nothing here is visible. It is what keeps the first two stories quiet.

**Independent Test**: post one report of every other kind the provider sends and confirm each is acknowledged with no record changed.

**Acceptance Scenarios**:

1. **Given** a signed report of an open or a click, **When** it arrives, **Then** it is acknowledged and no record changes.
2. **Given** a signed report about something other than an email we sent, **When** it arrives, **Then** it is acknowledged and no record changes.
3. **Given** a report naming an email the journal does not hold, **When** it arrives, **Then** it is acknowledged and no record changes.

---

### Edge Cases

- A report nobody can match or read: the account has since been deleted, or the email left before this feature or through another provider so no identifier was stored, or the body is not the shape the provider documents. Never backfilled.
- A temporary bounce followed by a successful delivery on the provider's retry. The row still reads as bounced, because FR-006 makes a bounce outrank a delivery. That is the accepted cost of recording both bounce types alike, not an oversight.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST record, against the email it already journalled, the instant the provider reports that email delivered.
- **FR-002**: The system MUST record the instant the provider reports that email bounced, and MUST record it in the same place when the provider reports it never managed to send the email at all, because both mean the email reached nobody.
- **FR-003**: The system MUST record the instant the recipient marked that email as spam, and MUST hold it as a state distinct from a bounce.
- **FR-004**: The single word describing where an email got to MUST be produced by one rule, read identically by the screen and by the API, and MUST be able to say that the recipient marked it as spam.
- **FR-005**: Recording an outcome the record already carries MUST change nothing.
- **FR-006**: The order in which reports arrive MUST NOT change what the record ends up saying.
- **FR-007**: A report naming an email the journal does not hold MUST be acknowledged and MUST change nothing.
- **FR-008**: The system MUST act on a report only once that report has been proven to come from the provider.
- **FR-009**: The system MUST act on no report at all while no shared secret is configured, since nothing can be proven then.
- **FR-010**: The system MUST NOT store when an email was opened, nor which of its links were followed. Permanently, on privacy grounds, and not a deferral.
- **FR-011**: Every other kind of report the provider sends MUST be acknowledged without effect, so that nothing is retried indefinitely.
- **FR-012**: An instance not sending mail through the provider MUST require no configuration and MUST behave as it does today.
- **FR-013**: A report the system declines to act on, whether unmatched or arriving while no secret is configured, MUST leave a trace the instance owner can find, and one it acts on MUST leave none, so the trace is a signal rather than a stream.
- **FR-014**: Every word this feature puts on a screen MUST be translatable and MUST be present in each configured locale.

### Key Entities

- **Email sent**: the journal row for one email. It already holds when it was sent, when it was delivered and when it bounced. It gains when the recipient complained.
- **Delivery outcome**: the one word read off those instants, today sent, delivered or bounced, gaining a fourth value for a complaint. Not stored.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An email's outcome appears on the screen as soon as the provider reports it, with no polling, no scheduled job and no action by the user beyond loading the page.
- **SC-002**: No email stays at "On its way" once the provider has reported an outcome for it.
- **SC-003**: Somebody asking whether a sign in email reached them can answer it from the screen alone, with no access to provider logs.

## Assumptions

- The receiving endpoint and its signature check already exist, shipped by the provider's own integration package, and this feature consumes what they hand over rather than building a second one. What that endpoint answers is therefore not ours to choose: with no secret configured it accepts anything and says so, and the protection is that nothing is acted on.
- Every email sent through the provider already carries the provider's identifier in the journal, written when the email went out. Reports are matched on it and on nothing else.
- The instant an outcome happened is taken from the report, not from when we received it, and is stored as reported even where it precedes our own record of sending because two clocks disagree. The two are never reconciled.
- A permanent bounce and a temporary one are both recorded as a bounce. Nothing in the product reads the difference yet, so it is not stored.

## Out of Scope

- Suppressing further sending to an address that bounced or complained, and telling its owner. [Model 005](../../spec/models/005-email-sent-in-progress.md) requires it, and it is deferred, owing a decision of its own on where suppression is held and how somebody gets back out of it under Principle VIII.
- The rename of the provider identifier column and the `locale` column that [Model 005](../../spec/models/005-email-sent-in-progress.md) also asks for. Unrelated to whether reports are believed.
- Outbound webhooks, meaning Monica telling other tools what happened ([spec section 14](../../spec/spec.md)). They share the word and nothing else.
- A receiving endpoint of our own. The provider's integration package ships one, and reimplementing it would mean owning a public route, a signature check and their upgrades for no gain.
