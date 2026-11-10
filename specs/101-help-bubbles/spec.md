# Feature Specification: Help bubbles

**Feature Branch**: `2026-10-27-help-bubbles`

**Created**: 2026-10-27

**Status**: Draft

**Input**: User description: "A help bubble based on design 3c (Help Bubble Options, claude.ai/design project 08d06050). One reusable component. A skill saying when a form or a non obvious concept gets a bubble: explain briefly how it works, then point at the dedicated help centre page, writing that page when it does not exist. Then add the bubbles the profile screen (settings/account/profile) needs."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Understand a setting without leaving the screen (Priority: P1)

Somebody on a screen meets a setting whose consequence is not obvious. A small circled question mark sits next to its label. Opening it shows a bubble: a title phrased as a question, two or three sentences on how it works, optionally a concrete example, and a footer with a button to the full guide in the help centre.

**Why this priority**: it is the whole value of the feature. The skill and the profile bubbles depend on it.

**Independent Test**: render the bubble with a title, an explanation, an example and a guide, and use it with a mouse, the keyboard alone and a screen reader.

**Acceptance Scenarios**:

1. **Given** a screen with a help bubble, **When** the reader points at the mark with a mouse, **Then** the bubble opens, and it stays open while the pointer travels from the mark into the bubble.
2. **Given** the mark has keyboard focus, **When** the reader presses Enter or Space, **Then** the bubble opens, and Tab reaches the guide links inside it.
3. **Given** an open bubble, **When** the reader presses Escape, **Then** it closes without the pointer or focus having to move, and focus returns to the mark if it was inside the bubble.
4. **Given** a screen reader, **When** the reader reaches the mark, **Then** it announces "Help about the email address, button, collapsed", and after activation "expanded", followed by the bubble's title, text and links in reading order.
5. **Given** a touch screen, **When** the reader taps the mark, **Then** the bubble opens fully readable on a narrow screen, and a second tap or a close control dismisses it.
6. **Given** the bubble names a guide, **When** the reader follows "Read the guide", **Then** the help centre page opens in the reader's language when it exists there, and in English otherwise, without losing what was typed in the form.

---

### User Story 2 - A maintainer knows when and how to add help (Priority: P2)

Somebody building a form or a concept in Monica follows a project skill that says when a bubble is due, how to write its text, which guide to point at, and what to do when the guide does not exist yet: write it in the help centre in the same change.

**Why this priority**: without the rule, bubbles appear on some screens and not others, and point at pages that do not exist.

**Independent Test**: give the skill alone to somebody adding a new setting, and check they produce a bubble with the right shape and an existing guide.

**Acceptance Scenarios**:

1. **Given** a bubble names a guide that does not exist in the help centre, **When** the test suite runs, **Then** it fails and names the missing guide.
2. **Given** the skill, **When** a maintainer reads it, **Then** it states what qualifies for a bubble and what does not (a label that explains itself, a validation message, a sentence the screen already prints).

---

### User Story 3 - The profile screen explains what is not obvious (Priority: P3)

On the profile screen, the reader learns why changing the email address asks for a credential and a confirmation, and what that address governs: every way back into the account.

**Why this priority**: the first real use, proving the component and the skill on a shipped screen.

**Independent Test**: open the profile screen and read each bubble, then follow its guide.

**Acceptance Scenarios**:

1. **Given** the profile screen, **When** the reader opens the bubble next to **Email address**, **Then** it explains that the address receives sign in links, password resets and warnings, that a new one must be confirmed from its inbox, and links to the guide on changing the profile.
2. **Given** the profile screen, **When** the reader looks at the photo, the name and the deletion sections, **Then** they carry no bubble, because the screen already says everything those need.

---

### Edge Cases

- The bubble near the right edge of the screen opens towards the left so it is never cut off.
- An explanation translated into a longer language wraps inside the bubble rather than widening it past the screen.
- A screen with several bubbles shows one at a time: opening one closes the other.
- The help centre has no page in the reader's language: the link goes to the English page.
- A bubble with no example drops that block without leaving an empty frame.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The application MUST offer one reusable help bubble, drawn as design 3c: a 16 px circled question mark, and a bubble holding a question title, an explanation, an optional "For example" block, and a footer holding only a right aligned "Read the guide" button.
- **FR-002**: The bubble MUST name its guide by the stable help centre page identifier, never by a written out address, and MAY add the section of that page that answers the question; the link then opens at that section.
- **FR-003**: The guide link MUST open the page in the reader's language when the help centre has it, and in English otherwise.
- **FR-004**: The guide link MUST open in a new tab, so a half filled form survives.
- **FR-005**: The bubble MUST open on pointer hover, on keyboard activation (Enter, Space) and on tap, and MUST NOT open on focus alone, so tabbing through a form does not pop bubbles open.
- **FR-006**: The bubble MUST close on Escape, on an outside click or tap, and when the pointer leaves both mark and bubble after a short delay; it MUST stay open while the pointer is over it or focus is inside it (WCAG 1.4.13).

#### Accessibility (WCAG 2.2 AA, constitution principle IX)

- **FR-A01**: The mark MUST be a real button whose accessible name names its subject ("Help about the email address"), never a bare "?" or "Help".
- **FR-A02**: The mark MUST expose whether the bubble is open and which content it controls.
- **FR-A03**: Because the bubble holds links, it MUST NOT be exposed as a tooltip; it MUST be a disclosed region named by its title, placed right after the mark in reading and focus order.
- **FR-A04**: Escape MUST return focus to the mark when focus was inside the bubble; focus MUST never be trapped in it or lost when it closes.
- **FR-A05**: The mark's clickable area MUST be at least 24 by 24 CSS pixels, even though it is drawn at 16 (2.5.8).
- **FR-A06**: The mark's outline and glyph MUST reach 3:1 against the page and the bubble's text 4.5:1, in light and dark themes (1.4.3, 1.4.11).
- **FR-A07**: Focus on the mark and on each link MUST be visible and never hidden behind the bubble or another element (2.4.7, 2.4.11).
- **FR-A08**: The bubble MUST reflow at 320 CSS pixels wide and at 200 % zoom without horizontal scrolling or clipped text (1.4.10).
- **FR-A09**: The bubble MUST appear without animation when the system asks for reduced motion.
- **FR-A10**: A link opening a new tab MUST say so in its accessible name, and the example block's caption MUST be text, not conveyed by styling alone.
- **FR-007**: The test suite MUST fail when a bubble names a help centre page, or a section of it, that does not exist.
- **FR-008**: Every word in the bubble MUST be translatable and translated in every configured locale.
- **FR-009**: The existing inline help (the one bubble on the API key form) MUST move to the new component, so the application keeps one help bubble, not two.
- **FR-010**: A project skill MUST state when a bubble is due, how its text is written (a question title, at most three sentences, an example in the reader's own world), and that a missing guide is written in the help centre in the same change.
- **FR-011**: The profile screen MUST carry a bubble on **Email address** pointing at the profile guide, and no other.

### Key Entities

- **Help bubble**: a question, an explanation, an optional example and the guide it points at.
- **Help centre page**: an existing page of the documentation portal, identified by a stable identifier that survives a rename or a move.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every bubble in the application points at a help centre page and section that exist, enforced on every change.
- **SC-002**: A reader can open a bubble, read it and reach its guide with the keyboard alone, in under 5 key presses from the mark.
- **SC-003**: The bubble meets every FR-A requirement, checked with the keyboard alone, with VoiceOver on macOS and iOS, at 320 px wide, at 200 % zoom and in both themes.
- **SC-004**: The application contains exactly one help bubble component after the change.

## Assumptions

- "Help centre" is the documentation portal under `/docs`, which today exists in English only. Each page there already carries a stable `id` (`account.profile`, `account.delete`).
- Design 3c is taken over as drawn, including its "For example" block from 2a; variants 1b, 1c, 2b, 2c, 3a and 3b are out of scope. Where the mockup disagrees with FR-A (it marks the bubble as a tooltip, opens it on focus and draws a 16 px target), FR-A wins and the look stays.
- A section is named by the anchor of a second level heading of the English page (`account.profile#your-email-address`); a translated page whose heading differs opens at its top.
- The new component replaces `x-help` rather than sitting beside it, because the rules on components forbid two of the same thing; that leaves one call site to migrate.
- The profile guide (`account.profile`) and the deletion guide (`account.delete`) already exist, so no help centre page is written for the profile screen. The deletion section gets no bubble because its panel already states what is lost.
- No new routes, data or settings are needed.
