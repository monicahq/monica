# Specification Quality Checklist: Section Icons and Colours

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-09-04
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

- Both markers from the first pass are resolved by the design. Where the mark lives is settled by the
  design asking for the glyph and the colour beside the section's own name, which makes it a section
  property (the existing `icon` column). How the two colours are chosen is settled by the design
  deriving both from one hue, so a wrong pair cannot be made.
- The set is closed and named in FR-001 and FR-002: 35 glyphs (22 existing, 13 new) and 9 colours
  (`neutral` plus the 8 vault hues).
- FR-004 is now measured rather than open. The design's formula mixes the hue with literal white and
  `.dark` never redefines those hues, so the tile fails to invert with the theme; its glyph contrast
  is nonetheless fine (3.79:1 to 7.77:1). Compositing over the dark page instead fixes the surface and
  then fails AA on three hues (Rust 2.79:1, Violet 2.93:1, Slate 2.15:1), because the foreground has
  to be lifted per theme. `resources/views/components/avatar-initials.blade.php` already does both for
  these eight hues, and measures 4.54:1 at worst. See research.md.
- Constitution Principle XI caps `spec.md` at 120 lines. The file is at 120.
