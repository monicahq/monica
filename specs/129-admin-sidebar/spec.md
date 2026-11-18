# Feature Specification: Admin space sidebar

**Feature Branch**: `2026-11-17-admin-sidebar`

**Created**: 2026-11-17

**Status**: Draft

**Input**: User description: "dans une nouvelle branche, remplace le menu de la partie d'admin par une sidebar. sois sur que ca marche bien sur mobile." Design: `Admin Changelog.dc.html`, design project `670b2827-68ce-4595-8d08-43fbe6fcd282`.

**Scope**: the navigation of the admin space only. The content of each admin screen, the Changelog screen included, is unchanged. The design file shows the Changelog screen because that is where the sidebar was drawn.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Move between admin sections from a sidebar (Priority: P1)

An administrator opens the admin space on a wide screen. The dark admin bar stays at the top, with its striped edge, the "Admin space" name, the "Instance" badge, the way out, the theme toggle and the account menu. The row of section links that sat under the bar is gone. In its place, a sidebar runs down the left side of the page, below the bar, listing the sections one per line, each with its icon and its label: Overview, Moderation, Support, Changelog, Accounts, Waiting list, Configuration, Search, Flags, Administrators, Record. The section the administrator is in reads as the current one. The warning that the administrator is looking at the instance, and the screen itself, sit to the right of the sidebar.

**Why this priority**: this is the change asked for. Eleven sections no longer fit in one row, and a vertical list reads faster and leaves room for more.

**Independent Test**: open any admin screen on a wide screen and reach every other section from the sidebar.

**Acceptance Scenarios**:

1. **Given** an administrator on the Accounts screen, **When** the page loads, **Then** the sidebar lists every available section and marks Accounts as the current page.
2. **Given** an administrator on one account's detail screen, **When** the page loads, **Then** Accounts is marked as the current section, though not as the current page.
3. **Given** a long admin screen, **When** the administrator scrolls, **Then** the sidebar stays in view, and scrolls on its own when it is taller than the window.
4. **Given** the support desk is turned off for the instance, **When** the sidebar renders, **Then** Support is absent, and the same holds for Changelog when the changelog is turned off.

---

### User Story 2 - Use the admin space on a phone (Priority: P1)

An administrator opens the admin space on a phone. The sidebar does not take up the screen. The admin bar shows a menu button. Pressing it opens the sidebar over the page, with the page dimmed behind it. Choosing a section goes there. Pressing the button again, pressing Escape, or tapping the dimmed page closes it without going anywhere. Nothing on the page scrolls sideways.

**Why this priority**: the request says it has to work well on mobile, and an administrator answering a support request or hiding a comment often does it from a phone.

**Independent Test**: at a phone width, open the menu, reach another section, and close the menu without choosing one.

**Acceptance Scenarios**:

1. **Given** a phone width, **When** an admin screen loads, **Then** the sidebar is out of sight, the content takes the full width, and a menu button is in the admin bar.
2. **Given** the menu is closed, **When** the administrator presses the menu button, **Then** the sidebar opens over the page, the page behind it is dimmed and does not scroll, and the button reports that the menu is open.
3. **Given** the menu is open, **When** the administrator presses Escape or taps outside it, **Then** it closes and focus returns to the menu button.
4. **Given** the menu is open, **When** the administrator chooses a section, **Then** that section opens.
5. **Given** a phone width, **When** any admin screen loads, **Then** the admin bar fits on one line, with the way out, the theme toggle, the account menu and the menu button all reachable.

---

### User Story 3 - See what is waiting at a glance (Priority: P2)

The Support and Changelog lines carry the number of items waiting for an administrator: support requests to handle and changelog comments to review. The number shows on the line in the sidebar, on wide screens and in the opened menu on a phone. A screen reader hears what the number counts.

**Why this priority**: the counts exist today in the top row and must not be lost in the move.

**Independent Test**: with three comments to review, the Changelog line shows 3 and announces "3 comments to review".

**Acceptance Scenarios**:

1. **Given** two support requests to handle, **When** the sidebar renders, **Then** the Support line shows 2 and announces it as requests to handle.
2. **Given** nothing waiting, **When** the sidebar renders, **Then** no number is shown on that line.
3. **Given** a phone width and items waiting, **When** the menu is closed, **Then** the menu button indicates that something is waiting, so the administrator knows to open it.

---

### Edge Cases

- A section turned off for the instance (Support, Changelog) is absent from the sidebar, not disabled.
- The window is resized across the narrow width while the menu is open: the menu closes and the page scrolls again, with no dimmed layer left behind.
- A long translated label (German, Finnish) wraps or is cut cleanly inside the sidebar's width, and its count stays visible.
- Printing an admin screen leaves out the sidebar and the menu button, as the current row of links is left out.
- Keyboard only: every line in the sidebar is reachable with Tab and shows a visible focus ring, on the light sidebar and in the dark admin bar.
- Reduced motion: the menu opens and closes without a sliding animation.
- The sidebar is taller than a short laptop window: it scrolls on its own and the page does not move.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The admin space MUST replace the horizontal row of section links under the admin bar with a vertical sidebar on the left side of every admin screen.
- **FR-002**: The sidebar MUST list the same sections, in the same order, with the same icons and labels, under the same conditions of availability as the current row.
- **FR-003**: The sidebar MUST mark the exact current screen as the current page, and a screen inside a section as belonging to the current section, as the current row does.
- **FR-004**: The sidebar MUST be a navigation landmark named "Admin sections".
- **FR-005**: On a wide screen, the sidebar MUST stay visible while the screen scrolls, start below the admin bar, and scroll on its own when its content is taller than the window.
- **FR-006**: The admin bar MUST keep its striped edge, name, Instance badge, way out, theme toggle and account menu.
- **FR-007**: The warning that the administrator is looking at the instance MUST stay above each screen's content, inside the area to the right of the sidebar.
- **FR-008**: Below the width at which the rest of the application folds its sidebar away, the admin sidebar MUST be hidden and opened from a menu button in the admin bar.
- **FR-009**: The opened menu MUST lie over the page, dim the page behind it, stop the page from scrolling, and close on Escape, on a tap outside it, and on the menu button.
- **FR-010**: The menu button MUST be named for screen readers and report whether the menu is open, and focus MUST return to it when the menu closes.
- **FR-011**: The Support and Changelog lines MUST show their waiting count when it is above zero, with a screen reader wording saying what is counted.
- **FR-012**: On a narrow screen, the menu button MUST show that something is waiting when either count is above zero.
- **FR-013**: An admin screen MUST NOT scroll sideways at a phone width (375 points) because of the navigation.
- **FR-014**: The sidebar and the menu button MUST be left out when an admin screen is printed.
- **FR-015**: The sidebar and the menu MUST meet the project's accessibility bar (WCAG 2.2 AA): visible focus, sufficient contrast in light and dark themes, a keyboard route in and out, and no motion when the reader asks for reduced motion.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: From any admin screen, an administrator reaches any other section in one action on a wide screen, and in two on a phone.
- **SC-002**: At 375 points wide, every admin screen shows no horizontal scrolling, and every section is reachable.
- **SC-003**: Every section reachable today is reachable after the change, and every waiting count shown today is still shown.
- **SC-004**: A keyboard user opens the menu, reaches a section and closes the menu without a pointer, and a screen reader announces the menu's state and each count.
- **SC-005**: An accessibility review of the changed layout reports no WCAG 2.2 AA failure.

## Assumptions

- The narrow width is the one the rest of the application already uses to fold its sidebar into a menu, so the admin space behaves like the vault and settings screens.
- The opened menu follows the drawer the main application shell already uses (dimmed page, page locked, closed by Escape or a tap outside), so administrators meet one behaviour, not two.
- The sidebar uses the light card surface of the design, with the current section drawn as a raised line, while the admin bar stays dark. The design is the reference for the look; it is not copied value for value.
- The sidebar is 248 points wide, the width of the main application's sidebar and of the design.
- The waiting counts are the ones the admin space already computes; no new count is added.
- No section is added, removed, renamed or reordered.
- A test covers the navigation contract (sections listed, current marking, counts and their wording, menu button naming and state). The look is reviewed by looking at it, as the testing rules require.
- This is a visible change to the admin space only. Whether it earns a changelog entry is decided when the change is done: only administrators see it.
