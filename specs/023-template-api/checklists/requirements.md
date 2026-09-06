# Specification Quality Checklist: Template endpoints

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-09-05
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

Two things a reviewer should know rather than discover.

The API is the subject of this feature, not an implementation choice, so naming endpoints, addresses
and pagination is describing what is being built rather than leaking how. This matches how the vault,
contacts and entity type specs are written. The spec still names no route, controller, action or
column.

`spec.md` is 132 lines against the 120 line cap in Principle XI of the constitution, an overrun of 12.
The reason is that the feature was deliberately scoped as five phases rather than one, at the request
that produced it, so the spec carries five features' worth of stories under one set of requirements.
Splitting it into five specs would restate the immutability rule, the account scoping rule and the
ordering rule five times, which Principle XI forbids more strongly than it forbids the overrun. The
pull request must name this cap and this reason.

Scope was settled before writing rather than left as a marker: the whole builder is planned, phased
P1 to P5, and import, export and sharing are excluded.
