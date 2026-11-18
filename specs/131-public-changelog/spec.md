# Feature Specification: What’s new for signed out visitors, and its missing logo

**Feature Branch**: `2026-11-18-public-changelog`

**Created**: 2026-11-18

**Status**: Draft

**Input**: User description: "quand on est dans le changelog, le logo Monica disparait. remets le, je ne comprends pas pourquoi ca fait ca. aussi je veux que tu etudies ce que ca prend pour que le changelog soit accessible depuis un mode non loggé. il faut, dans ce cas, ne pas pouvoir voir de commentaires ou de boutons like etc.."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The logo stays on What’s new (Priority: P1)

A signed in person opens What’s new from the top bar. The top bar keeps the logo and the name of the application, which lead back to the list of vaults, exactly as on the list of vaults itself.

Today the logo is missing on this screen. The screen is built on the frame used inside a vault, which draws the logo only inside the navigation column of a vault. What’s new has no such column, so nothing draws the logo. Every other screen standing outside a vault uses the frame whose top bar carries the logo.

**Why this priority**: It is a visible defect on a shipped screen, and the person loses the usual way back to their vaults.

**Independent Test**: Sign in, open What’s new, and find the logo and the application name in the top bar, linking to the list of vaults.

**Acceptance Scenarios**:

1. **Given** a signed in person, **When** they open What’s new, **Then** the top bar shows the logo and the application name, linking to the list of vaults.
2. **Given** a signed in person on What’s new, **When** they look at the rest of the screen, **Then** the period choice, the figures, the entries, the votes and the comments behave as they do today.

---

### User Story 2 - A visitor reads What’s new without signing in (Priority: P2)

Somebody who is not signed in (a prospective customer, a person who signed out, a reader following a shared link) opens the What’s new address. They read every published change, grouped by day, with the period choice and the figures. They see no vote, no comment, no comment count and no control that only makes sense with an account. The top bar offers the way to sign in instead of the places of an account.

**Why this priority**: It turns the changelog into something that can be shared publicly, but the signed in version already serves existing customers, so it comes after the defect.

**Independent Test**: Sign out, open the What’s new address, read the entries, and find no vote, comment or comment count anywhere on the page.

**Acceptance Scenarios**:

1. **Given** What’s new is switched on for the instance and nobody is signed in, **When** a visitor opens its address, **Then** the screen shows the published entries of the last 30 days, grouped by day, with the figures, and is not sent to sign in.
2. **Given** a visitor on What’s new, **When** they read an entry or a fix, **Then** there is no like or dislike button, no comment button, no comment count and no comment.
3. **Given** a visitor on What’s new, **When** they choose the last three months, **Then** the screen shows that period, as it does for a signed in person.
4. **Given** a visitor, **When** they try to vote, to read the comments of an entry or to post one by calling those addresses directly, **Then** they are refused exactly as today: sent to sign in, never served a comment.
5. **Given** What’s new is switched off for the instance, **When** a visitor opens its address, **Then** the page does not exist, as it does not for a signed in person.
6. **Given** a visitor on What’s new, **When** they look at the top bar, **Then** they find the logo and a way to sign in, and no link to settings, the admin space or notifications.

---

### Edge Cases

- An entry carries a "Try it" link to a place inside the application. A visitor following it would only reach the sign in screen, so the link is not shown to a visitor.
- The "New" badges and the "Seen on your last visit" divider depend on what a person has already seen. A visitor has no such history, so neither is shown, and visiting the page records nothing.
- Dates are written in the person’s chosen date format. A visitor has none, so dates follow the format of the language the page is shown in.
- A signed in person whose account is deactivated is signed out and sent to sign in, as on every other screen. One whose address is not yet verified sees the signed in version, and voting or commenting sends them to verify it, as today.
- The instance does not allow search engines to index its pages. What’s new follows the same instance setting as the other public pages.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The What’s new screen MUST show the logo and the application name in its top bar, linking to the list of vaults, for a signed in person.
- **FR-002**: The What’s new address MUST answer a visitor who is not signed in, without sending them to sign in, whenever What’s new is switched on for the instance.
- **FR-003**: When What’s new is switched off for the instance, the address MUST NOT exist for anybody, signed in or not.
- **FR-004**: A visitor MUST see the same published entries, grouping by day, figures and period choice as a signed in person.
- **FR-005**: A visitor MUST NOT see any vote control, comment control, comment count, comment or reply.
- **FR-006**: A visitor MUST NOT see the "Try it" link, the "New" badge or the "Seen on your last visit" divider.
- **FR-007**: A visit by a visitor MUST NOT record anything against any account.
- **FR-008**: Voting, reading comments and posting a comment MUST remain reserved to signed in people, at every address that offers them (the screen and the API).
- **FR-009**: The visitor version MUST offer a way to sign in and MUST NOT offer the places of an account (settings, admin space, notifications, vaults).
- **FR-010**: The page MUST follow the instance setting on search engine indexing, as the other public pages do.
- **FR-011**: The signed in version MUST keep every behaviour it has today, apart from the restored logo.

### Key Entities

- **Changelog entry**: a published change, read from the application’s own files. Public by nature: it holds nothing about any account.
- **Vote, comment, reply, seen mark**: rows held against an entry and an account. None of them is ever shown to, or written for, a visitor.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: On What’s new, a signed in person finds the logo in the top bar on every visit, as on the list of vaults.
- **SC-002**: A visitor reaches the What’s new page in one step from its address, with no sign in.
- **SC-003**: Zero comments, comment counts or vote controls appear in the page a visitor receives, for every entry and fix in both periods.
- **SC-004**: Zero votes, comments or seen marks are recorded as a result of visits by people who are not signed in.

## Assumptions

- The visitor version lives at the same address as the signed in one. One link can be shared with anybody, and each reader sees the version that fits them.
- An instance that switches What’s new on makes it public. Today only the hosted instance switches it on, so no separate setting is added for self hosted instances.
- The link to What’s new in the top bar stays on signed in screens only. Making the page reachable from the marketing site or the documentation is out of scope.
- The way to sign in on the visitor version leads to the existing sign in screen; no sign up prompt is added.
- The user’s message contained an unfinished sentence ("aussi, je veux que"). Nothing in this specification depends on it.
