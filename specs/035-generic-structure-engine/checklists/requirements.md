# Specification Quality Checklist: Generic structure engine

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-09-13
**Updated**: 2026-09-13, after planning
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
- [~] Success criteria are technology-agnostic (no implementation details)
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

The database shape the author asked for moved out of `spec.md` into
[data-model.md](../data-model.md), and the contradictions into [research.md](../research.md), so the
specification itself is now free of implementation detail. It is 200 lines against a 120 line cap,
declared in the plan's Complexity Tracking.

SC-003 and SC-004 name the repository and the test suite. That is deliberate: the observable outcome
of a rename is that the old name is gone and the suite still passes, and there is no user-facing
metric for it.

**The three invariants are settled.** The author answered Q1, Q2 and Q3 on 2026-09-13; they are
recorded as D1, D2 and D3 in `research.md`.

- D1: the structure points at its owner, polymorphically.
- D2: `applies_to` and `entity_type_id` move onto the structure.
- D3: the marketplace publishes the owner, so `installable_type` gains an ActivityType kind.

D1 was taken against the recommendation. It buys a one-query path from a version to its owner, which
the record screen needs, at the price of the owner cascade and the referential check, both of which
move into code. `plan.md` records that under principle VIII and `data-model.md` names the two rules.
