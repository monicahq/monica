# Specification Quality Checklist: Readable vault and contact addresses

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-09-12
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

Seven decisions are recorded in the Clarifications section, three from the specify pass and four from the clarify session of 2026-09-12.

Five consequences to carry into planning.

- FR-006 is the load bearing requirement. A readable address is deliberately impermanent while the unreadable one is permanent, so a readable name a record has released must never reach a different record, or a stale link opens the wrong person. A contact merged into another is the single stated exception, and it explains rather than redirects.
- Keeping a name in its own script settles the largest open question and creates the next one. Addresses now carry arbitrary user text, so FR-012 and FR-013 exist to stop two names that look alike from both existing in one scope, and to stop a name steering the direction an address reads in. Neither is an edge case; both are correctness.
- The preference of FR-014 widens the change past the URLs. It is a stored setting, a control on the preferences screen, strings in every configured locale, and a second path through every place the product builds an address. FR-003 is what keeps it safe: the preference governs what is built and never what is accepted, so turning it off breaks no saved link.
- Building a contact address from the whole displayed name puts prefix, suffix and maiden name into it. Addresses are longer than the Overview example suggests, and every part of the name is now a rename trigger.
- Accepting either form through the API reaches every method naming a vault or a contact, what those methods report back, and the published reference.

The spec is 147 lines against the 120 line cap the constitution sets for `spec.md`. The clarify session added a user story, four requirements, a success criterion, an entity and four clarification bullets, and the cap is exceeded rather than a decision dropped to meet it. The constitution permits this and requires the pull request to say so, which is what this paragraph is for.

One tension is stated in the spec rather than resolved by it. Putting a contact's name in the address exposes it to browser history, to any log recording a path and to anyone looking at the screen, in a product whose contents are private. FR-014 is the answer to it, and it is on by default, so the exposure is what an account gets unless its owner goes and changes it.
