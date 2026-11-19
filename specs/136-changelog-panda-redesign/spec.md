# Feature Specification: What’s new with the panda

**Feature Branch**: `2026-11-19-changelog-panda-redesign`

**Created**: 2026-11-19

**Status**: Draft

**Input**: User description: "Modifiez la page changelog avec le design du 6 juin. Je vous mets aussi le panda que je veux que vous mettiez. Changez l'image en WebP et n'oubliez pas le Dark Mode." The design is variant 5a, "What’s new with panda", of `Changelog Date Navigation.dc.html` in the claude.ai design project `e80a62cb-60bb-4e94-a26f-ed7cac97c519`. The illustration is `Pastel Panda Update Planner.png` (1254 by 1254, transparent background), supplied by the user.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The page greets the reader with the panda (Priority: P1)

Somebody opens What’s new. Beside the title and the line under it stands the panda writing in its “What’s new” notebook. The illustration reads cleanly whether the person uses the light theme or the dark theme.

**Why this priority**: It is the visible change the request is about, and it carries the dark mode requirement.

**Independent Test**: Open What’s new in the light theme and in the dark theme, and check that the panda stands to the right of the title in both, without a halo, a box or a fringe around it.

**Acceptance Scenarios**:

1. **Given** a signed in person on a wide screen, **When** they open What’s new, **Then** the title and its description stand on the left and the panda stands on the right, vertically centred against them.
2. **Given** the dark theme, **When** the page renders, **Then** the panda shows without a light rectangle, a coloured fringe or a halo around its outline.
3. **Given** a phone width screen, **When** the page renders, **Then** the panda does not push the title off screen and the page does not scroll sideways.
4. **Given** a screen reader, **When** it reaches the header, **Then** the panda is treated as decoration and is not announced.

---

### User Story 2 - Feedback on one line, without the prompt (Priority: P2)

Under each change, the way to try it stands on the left. On the right, on one line, stand a comment button drawn as an icon, a thin separator, and the two vote buttons. The sentence “Do you like it?” is gone.

**Why this priority**: It is the second half of the design and changes how every entry reads, but the page works without it.

**Independent Test**: Open an entry and check that the comment button is an icon next to the votes, that it still opens and closes the comments, and that no “Do you like it?” text appears.

**Acceptance Scenarios**:

1. **Given** an entry with a link, **When** the page renders, **Then** “Try it” stands on the left and the comment, separator and vote buttons stand together on the right.
2. **Given** an entry without a link, **When** the page renders, **Then** the comment and vote buttons still stand on the right.
3. **Given** the comment icon button, **When** a person activates it, **Then** the comments open below the entry exactly as they do today, and activating it again closes them.
4. **Given** an entry with comments, **When** a screen reader reaches the comment button, **Then** its name says how many comments there are, as the visible label does today.
5. **Given** a person who votes, **When** the vote is recorded, **Then** the pressed button shows its pressed state and the confirmation that the vote was noted is still announced to assistive technology, even though no sentence is visible.
6. **Given** a fix in the compact list of fixes, **When** it is expanded, **Then** its feedback row uses the same layout.

---

### Edge Cases

- Feedback is switched off for the reader: the row shows only “Try it”, or nothing when the entry has no link, as today.
- The browser cannot load the WebP file: the header still shows the title and description, and nothing breaks around the missing picture.
- A long translated title: the title wraps rather than overlapping the panda.
- A period without any change: the panda and the header still render above the “Nothing changed in this period.” line.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: What’s new MUST show the supplied panda illustration beside the page title and description, to the right on wide screens.
- **FR-002**: The illustration MUST be served in WebP format, converted from the supplied PNG, and the PNG MUST NOT be shipped.
- **FR-003**: The illustration MUST render correctly in the light theme and in the dark theme. If the transparent edges of the image show a fringe on the dark background, a dark theme version of the image MUST be provided and shown in the dark theme only, the way the other illustrations of the application do it.
- **FR-004**: The illustration MUST be decorative for assistive technology, and MUST declare its size so the header does not move when it loads.
- **FR-005**: On narrow screens the illustration MUST shrink or step aside so that the title, the description and the period selector stay readable without sideways scrolling.
- **FR-006**: The comment control of each change MUST become an icon button placed immediately before the vote buttons, separated from them by a thin vertical rule.
- **FR-007**: The comment icon button MUST keep the behaviour of today’s comment button: it opens and closes the comments, reports whether they are open, and its accessible name carries the comment count.
- **FR-008**: The visible sentence “Do you like it?” MUST be removed. The confirmation that a vote was noted MUST still reach assistive technology.
- **FR-009**: The period selector, the four figures, the day column, the entries, the fixes list and the “Seen on your last visit” divider MUST keep their current behaviour and content.
- **FR-010**: Every visible or announced wording MUST be translated in every shipped locale.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: In both themes, a reviewer comparing the page with design 5a finds the panda header and the feedback row laid out as drawn.
- **SC-002**: The panda image weighs less than a quarter of the supplied PNG.
- **SC-003**: At 375 pixels of width, the page shows no sideways scroll in either theme.
- **SC-004**: A keyboard user reaches “Try it”, the comment button and both votes of an entry in that order, and each announces a name.
- **SC-005**: No existing behaviour of What’s new (period switch, votes, comments, fixes) regresses.

## Assumptions

- “Le design du 6 juin” is read as variant 5a of the design file, the only variant holding the panda. The other variants of that file (month arrows, daily rhythm, month statistics, date navigation) are explorations and are out of scope.
- The rest of the page already matches design 5a (period selector, figures, day column, entries); only the header and the feedback row change. Small spacing differences between the design and the current page (a 200 pixel day column against 180) are not part of the request.
- The illustration is shown at about 180 by 180 pixels, as drawn, and the WebP is sized for that display at high density rather than kept at 1254 pixels.
- No data, no route and no permission changes. Who may comment or vote is decided exactly as today.
- The changelog entry rule (the `changelog` skill) applies: this is a visible change to a screen, and it gets a What’s new entry.
