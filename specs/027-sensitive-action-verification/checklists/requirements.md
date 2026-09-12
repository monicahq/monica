# Specification Quality Checklist: Prove a credential before a sensitive action

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-09-11
**Feature**: [spec.md](../spec.md)

## Content Quality

- [x] No implementation details (languages, frameworks, APIs)
- [x] Focused on user value and business needs
- [x] Written for non-technical stakeholders
- [x] All mandatory sections completed

## Requirement Completeness

- [x] No [NEEDS CLARIFICATION] markers remain
- [x] Requirements are testable and unambiguous
- [x] Success criteria are measurable
- [x] Success criteria are technology-agnostic (no implementation details)
- [x] All acceptance scenarios are defined
- [x] Edge cases are identified
- [x] Scope is clearly bounded
- [x] Dependencies and assumptions identified

## Feature Readiness

- [x] All functional requirements have clear acceptance criteria
- [x] User scenarios cover primary flows
- [x] Feature meets measurable outcomes defined in Success Criteria
- [x] No implementation details leak into specification

## Notes

Three markers were opened on the first pass and answered on 2026-09-11. The answers are in the spec's Clarifications section and written into FR-002, FR-006 and FR-010.

| Question | Answer | Landed in |
| --- | --- | --- |
| Which actions are sensitive | The security screen, changing the email address, deleting the account. Not vault or record deletion | FR-002 |
| How long a proof lasts | Fifteen minutes, covering every sensitive action in that time | FR-006 |
| Whether the API is covered | The sensitive actions stay available over the API, where the token is the proof | FR-010 |

The third answer was reversed on 2026-09-11. The first reading refused the sensitive actions over the API, which withdrew `POST /v1/api-keys` and `DELETE /v1/api-keys/{apiKey}` and narrowed `PUT /v1/me`, against constitution Principle X. FR-010 now leaves the API exactly as it is, and the residual risk is stated in the spec instead of designed away.

A fourth question was answered on 2026-09-11, raised by `/speckit-analyze` rather than by this checklist: a taken over session could read the recovery codes off the security screen without giving a credential, which the gate does not cover because reading is not one of the actions FR-002 lists. The codes are now shown once, when they are created, and FR-013 says so.

Two more findings from that same analysis were settled on 2026-09-11. FR-006 said the proof ended with the credential it was given with, which the session cannot know, since it holds a timestamp and nothing else; it now ends when any credential of the account changes or is removed, which clears more often and never less. And FR-009's resumption, which lives in the browser, is checked by hand against quickstart step 1, because the suite holds no browser test; the pull request has to say so, since principle III otherwise requires one.

Two things the planning phase has to carry, rather than discover:

- The step is a property of the interactive session, so nothing in it may sit where an API request passes through. A token holder is not asked for a proof and must not be.
- Deleting a passkey goes through a route the passkey package registers, not one this project owns. The step has to reach it all the same.

The constitution caps `spec.md` at 120 lines (Principle XI). The spec sits at 120.
