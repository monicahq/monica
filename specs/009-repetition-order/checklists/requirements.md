# Specification Quality Checklist: Which end a new repetition goes to

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-09-01
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

### Two findings from the code, to carry into research.md

The spec was written after reading the save path rather than from the complaint alone. Two facts
shaped requirements that would otherwise look like over-specification, and the plan should not soften
either of them.

- **FR-009 exists because a repetition that does not arrive in a save is understood as one somebody
  took away.** That is deliberate and documented where the saving happens: adding and removing
  repetitions is part of saving the block, which is what lets the block promise that nothing is
  written until it is saved. The consequence for this feature is sharp. If the form is rendered in
  one direction while the order underneath runs the other way, and the identity of each repetition
  does not survive the round trip, the save does not mis-order anything. It deletes. So FR-009 carries
  a test rather than a note.
- **FR-006 exists because of how a new repetition is placed today.** It is given the place after the
  last one, always. The obvious way to put a note first is to give it the first place and push
  everything down, which writes to repetitions nobody touched and breaks FR-006 and FR-007 at once.
  Declaring a direction and reading in it avoids that entirely.

### What the plan will find already built

Nothing is needed from storage. A repetition already carries the place it holds, the column that
holds it says in its own comment that it is the order the repetition appears in and that the user can
reorder it, there is an index covering it, and both paths that read repetitions already sort by it.
The work is a declaration on the repeater, two read paths honouring it, and the round trip test.

### One dependency worth stating twice

FR-003 and FR-004 name the repeaters in the template an account starts with. Notes became a repeater
in a separate change that is not yet on the default branch, so this feature cannot be finished before
that one lands. It can be planned and mostly built without it: the documents and photographs
repeaters are enough to prove a declared direction is honoured.
