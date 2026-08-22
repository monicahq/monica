# Specification Quality Checklist: Correct Plural Phrasing in Interface Copy

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-08-21
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

### Validation record

Two passes were run.

Pass 1 found three problems, all fixed before this checklist was marked.

1. Three counts asserted in the spec were not supported by the codebase. "Fifteen" empty state
   wordings came from a search for keys beginning with "No ", which is not the same set. "46 counted
   phrases" was the number of catalogue entries containing a count, not the number whose wording
   depends on it. "Thirteen wrong readings" undercounted the phrases confirmed to read wrongly at
   one. All three were corrected against the working tree, and FR-001 now says explicitly that the
   46 are classified rather than assumed.
2. FR-004 and SC-002 described where a decision lives in code, which fails the
   technology-agnostic item. Both were rewritten to describe when the decision is made relative to
   consulting the reader's language.
3. SC-005 named the routine that checks the translation files. Rewritten as an outcome, that an
   incomplete phrase is reported before it ships.

Pass 2 found no failures.

### Clarification session, 2026-08-21

Four questions were asked and answered after this checklist was first written. All 16 items still
pass, and two of them pass more strongly than before, because two claims that could not have been
verified against anything in the repository were removed:

- SC-004 asked for verification by translating a phrase into a language with four plural categories.
  Checked against the plural rules the framework actually applies to the nine shipped locales, seven
  need two wordings, French needs two with zero grouped under the singular, and Turkish needs exactly
  one. Nothing shipped has more than two, so the criterion was unverifiable. It now names the two
  locales that do disagree with English, and the spec states plainly that three or more wordings is
  not proven here.
- Turkish needing a single wording was missing from the spec entirely, and is a live trap: a check
  demanding two wordings everywhere would be wrong for Turkish forever. FR-009 and FR-016 now make the
  expected number of wordings a property of the locale.

The four answers added FR-015 through FR-019 and SC-008 through SC-010, covering the automatic check,
the written rule, and how a phrase declares that its wording does not depend on its number.

### Deliberate judgement calls, recorded rather than asked

Three further points were settled without asking, each recorded in Assumptions:

- **Scope.** "Missusage" was read as covering the pattern and not only the visible defects, since
  fixing the readings alone leaves the next phrase and the next language in the same position. The
  spec keeps the two separable: story P1 is the visible defects and is a viable slice on its own,
  P2 is the pattern.
- **Zero.** No new empty state copy is invented, and no existing empty state copy is removed. Zero
  falls to the language's own rules, which is what lets French read it as a singular.
- **The nine locales.** They are re-translated in the same change rather than allowed to fall back to
  English, because the project's localization principle requires every shipped string translated in
  every configured locale, and a phrase whose shape changes takes its old translations with it.

### Related defect found while writing this spec, left out of scope

A count is written exactly as it is stored, so a French reader sees "1234" where their language
writes "1 234". The account preference for number format never reaches these phrases. It sits in the
same family as this feature and is named in the same principle, but it touches every number the
product shows and not only the ones beside a noun, so it belongs in its own change.
