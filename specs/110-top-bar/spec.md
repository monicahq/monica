# Feature Specification: One top bar, outside and inside a vault

**Feature Branch**: `2026-11-10-vault-header`

**Created**: 2026-11-10

**Status**: Draft

**Input**: User description: "i want a new header on https://erica.test/vaults which would transfer well into when we are in a vault. design is 1a" (Claude Design project `b78dc791-8645-424e-b24c-635ab7aea4f3`, `Header.dc.html`, option 1a)

## Context

One signed in person sees three different top bars today: the vault list, a vault screen (where the
logo and theme switch sit at the head of the vault column and the account menu at its foot), and
settings, which shares the vault shell and gets the same bar. Design 1a gives the bar one order and one weight per kind of control. The bar on the vault
list is the bar a person keeps when they enter a vault: the right side stays where it was, and only
the left side changes from the product name to where they are.

## Clarifications

### Session 2026-11-10

- Q: Inside a vault, do the theme switch and the account menu stay in the column as well as in the bar? → A: No. They leave the vault and settings columns and exist once, in the bar.
- Q: Do the marketplace and administration screens adopt the new bar here? → A: No. They follow in a separate specification.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The vault list shows the new bar (Priority: P1)

Somebody lands on their vaults and sees the product on the left and, on the right, the places they
can go, the administration if they hold it, and their own utilities, as design 1a draws them.

**Why this priority**: It is the screen the request names, and the other stories reuse it.

**Independent Test**: Open the vault list as a regular person and as an administrator.

**Acceptance Scenarios**:

1. **Given** a person who is not an administrator, **When** they open the vault list, **Then** the bar shows the product link, Docs, Marketplace, the divider, Notifications, the theme switch and the account button, in that order.
2. **Given** an administrator, **When** they open the vault list, **Then** Admin space appears before the divider, as the only bordered button.
3. **Given** three unread notifications, **When** the person looks at the bar, **Then** the count 3 shows on the button and a screen reader hears "Notifications, 3 unread".
4. **Given** the bar, **When** the person activates the product link, **Then** they arrive on the vault list.
### User Story 2 - The bar carries over into a vault (Priority: P1)

The person opens a vault. The right side of the bar is unchanged; the left side shows where they are.

**Why this priority**: It is the second half of the request, and without it the redesign adds a
fourth bar instead of replacing three.

**Independent Test**: Go from the vault list to any vault screen and compare the right of the bar.

**Acceptance Scenarios**:

1. **Given** the vault list, **When** the person opens a vault, **Then** every control on the right keeps its order, look and position.
2. **Given** a vault screen, **When** the person looks at the left of the bar, **Then** they see the breadcrumb starting at the vault.
3. **Given** a vault screen on a wide screen, **When** they look at the bar, **Then** the vault search and the screen's actions stay, left of the shared controls.
4. **Given** a vault screen, **When** they open the account menu or switch the theme from the bar, **Then** it behaves exactly as on the vault list.
5. **Given** a vault screen, **When** they look at the vault column, **Then** it shows no theme switch and no account block.
### User Story 3 - The bar on a narrow screen (Priority: P2)

On a phone every control stays reachable without sideways scrolling, and the vault menu still opens.

**Why this priority**: The responsive application is a release condition, but the design is desktop.

**Independent Test**: Use every control of the bar at a phone width, outside and inside a vault.

**Acceptance Scenarios**:

1. **Given** a phone width, **When** the person opens the vault list or a vault screen, **Then** the bar fits, and Docs, Marketplace and Admin space show their glyph alone, still named for a screen reader.
2. **Given** a phone width inside a vault, **When** they look at the left of the bar, **Then** the button opening the vault menu is there.
### Edge Cases

- The marketplace is off for the account: Marketplace is absent, not disabled, and nothing else moves.
- More than nine unread notifications: the button reads "9+", the spoken label gives the real number.
- No unread notifications: the bell shows with no count.
- A long vault name or deep breadcrumb: the breadcrumb shortens; the controls on the right never shrink or wrap.
- A deactivated administrator sees no Admin space.
- The bar does not print.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The vault list bar MUST show, in order: the product link, Docs, Marketplace, Admin space, a divider, Notifications, the theme switch, the account button.
- **FR-002**: Docs and Marketplace MUST be quiet buttons with a glyph and a label where labels fit, and the glyph alone with an accessible name below that width.
- **FR-003**: Admin space MUST be the only bordered control in the bar, follow FR-002 for its label, and appear only for an active administrator.
- **FR-004**: Notifications MUST be an icon button carrying the unread count in the primary colour, "9+" above nine, with a spoken label giving the exact number, opening today's notification panel.
- **FR-005**: The theme switch MUST be an icon button and keep its current behaviour.
- **FR-006**: The account button MUST show the avatar and a chevron, be named "Your account", and open a menu holding account settings and signing out.
- **FR-007**: Every control in the bar MUST be 32 pixels high, on a 56 pixel bar.
- **FR-008**: The bar MUST use the 1a glyphs for docs, marketplace, admin space and notifications, from the application's one glyph catalogue.
- **FR-009**: Every vault screen and every settings screen MUST show the same right side of the bar as the vault list.
- **FR-010**: Inside a vault, the left of the bar MUST show the breadcrumb, and keep the button opening the menu on a narrow screen.
- **FR-011**: Inside a vault, the vault search and the screen's actions MUST stay in the bar, between the breadcrumb and the shared controls.
- **FR-012**: The theme switch and the account block MUST leave the vault column and the settings column, so each control exists once on a screen.
- **FR-013**: The bar MUST meet WCAG 2.2 AA: keyboard reachable in visual order, visible focus, accessible names, contrast in both themes, menus closing on Escape.
- **FR-014**: The bar MUST fit a 375 pixel wide screen without sideways scrolling.
- **FR-015**: New copy MUST be translated in every configured locale.
- **FR-016**: The marketplace, administration and documentation bars MUST keep their own layout in this feature.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: From the vault list into any vault screen, at the same width, no control on the right of the bar moves.
- **SC-002**: The vault list, every vault screen and every settings screen show the same controls on the right, with no difference in order, size or look.
- **SC-003**: Every control in the bar is reached in at most eight presses of Tab from the start of the page.
- **SC-004**: At 375 pixels every control of the bar is visible and usable with no sideways scroll.
- **SC-005**: An automated accessibility check of the vault list and a vault screen reports no violation in the bar, light and dark.

## Assumptions

- The product name is the configured application name, not the literal "Monica" of the design.
- Labels appear from the width the bar uses today; the design shows 1280 pixels only.
- The notification panel, the account menu entries and the theme switch keep their behaviour.
- The vault list's heading and New vault button are outside this feature; 1a draws them for context.
- The design tokens 1a uses already exist in the application theme under the same names.
