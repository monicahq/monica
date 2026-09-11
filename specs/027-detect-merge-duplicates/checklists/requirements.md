# Specification Quality Checklist: Detect and merge duplicate contacts

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-09-10
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

- The one clarification, on the signals that make two contacts a candidate duplicate, was answered on 2026-09-10: name similarity plus an exact match in a field the user marks as identifying. FR-001 through FR-004 and SC-005 carry the answer. Everything else was decided and recorded under Assumptions.
- FR-003 introduces a mark on a field, which lands inside the frozen structure and therefore inside Principle VI. Planning has to say where the mark is stored and what changing it on a published section version does. FR-004 exists so the screen is not empty on a fresh vault.
- The spec is 120 lines, which is the cap the constitution sets for `spec.md`.
- Scope was narrowed against the product specification's model 034 on two points, both recorded as assumptions rather than left implicit. Relationships, group memberships and activity participations are named in FR-009 but do not exist in the codebase yet, so FR-009 states the rule for when they arrive. Webhook emission is specified by the product but has no machinery, so it is out.
- Two open questions the product specification lists for this model are answered here: entities are out of scope, and an undo stays available while the absorbed contact is retained. The constitution requires those answers to land in `spec/` before code depends on them, so `spec/models/034-contact-merge-done.md` needs updating during planning.
- Items marked incomplete require spec updates before `/speckit-clarify` or `/speckit-plan`
