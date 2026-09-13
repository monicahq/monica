# Specification Quality Checklist: Family tree panel

**Purpose**: Validate specification completeness and quality before proceeding to planning

**Created**: 2026-09-13

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

Three decisions were put to the requester before the spec was written, and all three are recorded in
Assumptions rather than left as markers:

- The mock's "Open" control is dropped. A deeper tree on its own page is a separate feature.
- The Immediate family panel on the Overview page is untouched. The overlap is deliberate.
- No tier is capped. The panel keeps fixed outer dimensions and the drawing is reached by moving it
  in both directions. This is the one place the specification departs from the mock, which draws a
  fixed canvas and makes no provision for a crowded tier.

Two further departures from the mock are recorded in Assumptions and were not put to the requester,
because the mock is a single worked example rather than a rule: the parent tier and the partner tier
are specified to hold as many people as are recorded, where the mock draws one of each.

FR-020 and SC-004 state a cost, not a technique. They are here because the panel draws a set of
people whose size the reader controls, and a page that grows a question per person would fail
quietly rather than visibly. Reviewed and kept as a user-facing constraint.

Items marked incomplete require spec updates before `/speckit-clarify` or `/speckit-plan`. None are.

## Amended during planning (2026-09-13)

`/speckit-plan` found FR-019 ("children hang from the pair when there is one current partner",
taken from the mock) contradicting this specification's own edge case about a child whose other
parent is not the partner. The requirement was the wrong half: the records never say a partner is a
child's other parent. Children now hang from the contact, recorded as FR-014, with the argument in
[research.md](../research.md) D2. Requirements were renumbered when neighbouring ones merged to
bring the file under the constitution's 120-line cap; there are 20 now, and none was dropped.
