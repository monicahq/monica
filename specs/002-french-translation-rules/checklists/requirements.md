# Specification Quality Checklist: French Translation Rules

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-08-26
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

- Items marked incomplete require spec updates before `/speckit-clarify` or `/speckit-plan`

### Validation run 1, 2026-08-26

Four [NEEDS CLARIFICATION] markers remained, three in the Clarifications section and one at the
enforcement requirement. "Scope is clearly bounded" failed on the first of them: the boundary within
French was stated, but whether the deliverable was French alone or a per-locale structure was open.

### Validation run 2, 2026-08-26, after `/speckit-clarify`

16/16 items passing. Five decisions were recorded, one supplied directly by the user and four asked:
the typographic apostrophe `’`, a per-locale structure with French filled in first, a check that
reports and fails without rewriting, U+202F and U+00A0 for the spacing, and correction of the whole
existing file so the check ships passing.

Both previously failing items now pass. No [NEEDS CLARIFICATION] marker remains, and the scope is
bounded on every side: one locale's rules written, eight sections left visibly empty, punctuation and
address covered, word choice and number formats excluded, no English string or key touched.

Three statements superseded by the answers were rewritten rather than left standing: the Context
table now names the typographic apostrophe and the two space characters, the assumption about
competing typographic variants records which one was picked, and the second acceptance scenario of
User Story 3 now has the breach caught before review rather than at it.

The counts in the Context table, in Story 2 and in SC-002 were measured against the shipped
`lang/fr_FR.json` on 2026-08-26 and each is verifiable by re-measuring.
