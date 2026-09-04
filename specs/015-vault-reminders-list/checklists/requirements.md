# Specification Quality Checklist: Vault reminders list

**Purpose**: Validate specification completeness and quality before planning
**Created**: 2026-09-04 | **Updated**: 2026-09-04 after the spec was rewritten to the Principle XI cap
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
- [ ] Success criteria are technology-agnostic (no implementation details)
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

One item is unticked. SC-008 names `php artisan monica:localize --check` because `CLAUDE.md` makes that command the definition of "translated", and stating it abstractly would remove the only thing that makes it verifiable.

The first three unticked items from the earlier draft are now resolved. The backend gap analysis that carried file names, actions and columns moved out of the spec into [research.md](../research.md) and [plan.md](../plan.md), where implementation detail belongs. The spec now names no class outside its entity table's type names.

The three clarifications were asked and answered on 2026-09-04 and are recorded with their rationale and rejected alternatives in [research.md](../research.md):

1. The Active tab covers the existing 90 day window; the generation window is not widened.
2. The Sent tab's outcome names only dismissed, postponed and still open.
3. The header's add action leaves this feature.

One consequence to carry into implementation. Because the window stays at 90 days and the commonest reminder here is a yearly birthday, the Active tab will often be short. FR-004 is what stops that reading as data loss: the horizon copy and the two-way empty state are load-bearing.

Two constitution deviations are recorded in the plan's Complexity Tracking and must be named in the pull request: no API coverage for reminders (Principle X, pre-existing since 012 and 013), and a query string for the grouping toggle.

Validation ran three times: on the first draft, after the clarifications, and after the rewrite to the line cap.
