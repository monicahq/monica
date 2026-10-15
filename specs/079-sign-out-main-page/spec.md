# Feature Specification: Sign out from the main page

**Feature Branch**: `2026-10-13-sign-out-main-page`

**Created**: 2026-10-13

**Status**: Draft

**Input**: User description: "logout from the main page"

## Context

The main page is the list of vaults. It is where somebody lands after signing in, and where the
root address sends anybody who is signed in and has been welcomed.

Today that page offers no way to sign out. Its top bar holds Docs, Marketplace (when the flag is on),
Account settings, Admin space (for an administrator), the notifications and the theme switch. Sign
out exists in two places only: the menu at the foot of the navigation column inside a vault, and
the settings screen. Somebody on the main page has to open a vault or the settings first, which is a
detour for the one thing they came to do, and somebody with no vault yet has only the settings left.

The marketplace and the admin space have their own top bars. Both show the avatar of whoever is
signed in and a way back to the app, and neither offers Sign out.

## Clarifications

### Session 2026-10-13

- Q: Should Sign out be added only to the shell of the vaults list, or also to the other signed in
  top bars that lack it (the marketplace, the admin space and the welcome screen)? → A: The shell of
  the vaults list, plus the marketplace and admin top bars. Not the welcome screen.

### Session 2026-10-14

- Q: How is Sign out offered in those bars? → A: Each bar shows the avatar of whoever is signed in,
  and the avatar opens a menu holding Account settings and Sign out. This reverses the earlier rule
  that the bar outside a vault names nobody.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Sign out from the list of vaults (Priority: P1)

Somebody signed in is on the list of vaults and wants to leave. They find Sign out in the top bar,
activate it, and are signed out and sent to the sign in screen.

**Why this priority**: it is the whole request. It removes the detour through a vault or the
settings.

**Independent Test**: sign in, open the list of vaults, activate Sign out in the top bar, and
confirm the session has ended and the sign in screen is shown.

**Acceptance Scenarios**:

1. **Given** Pam is signed in and on the list of vaults, **When** she activates Sign out in the top
   bar, **Then** her session ends and she sees the sign in screen.
2. **Given** Pam has just signed out from the list of vaults, **When** she goes back to the list's
   address, **Then** she is asked to sign in and sees none of her vaults.
3. **Given** Pam's account holds no vault yet, **When** she opens the list of vaults, **Then** Sign
   out is offered in the top bar exactly as it is when she has vaults.

---

### User Story 2 - Sign out with the keyboard or a screen reader (Priority: P1)

Somebody who uses only the keyboard, or a screen reader, reaches Sign out on the list of vaults and
uses it the same way as anybody else.

**Why this priority**: WCAG 2.2 AA is a delivery condition (constitution, principle IX), so this
ships with story 1 or story 1 does not ship.

**Independent Test**: on the list of vaults, tab to the avatar, open its menu with Enter, tab to
Sign out, press Enter, and confirm the session has ended.

**Acceptance Scenarios**:

1. **Given** Dwight is on the list of vaults using only the keyboard, **When** he tabs to the avatar
   and presses Enter, **Then** the menu opens, Escape closes it, and Sign out in it signs him out.
2. **Given** a screen reader is running, **When** focus reaches the avatar, **Then** it is announced
   as a menu button named "Account", and the two entries of the menu by their names.

---

### User Story 3 - Sign out from the other screens outside a vault (Priority: P3)

The form that creates a vault uses the same shell as the list of vaults, so it offers Sign out in the
same place.

**Why this priority**: it follows from story 1 at no extra cost, and it keeps every screen of that
shell the same.

**Independent Test**: open the form that creates a vault and confirm Sign out is in the top bar and
works.

**Acceptance Scenarios**:

1. **Given** Jim is on the form that creates a vault, **When** he activates Sign out, **Then** his
   session ends and he sees the sign in screen.

---

### User Story 4 - Sign out from the marketplace and the admin space (Priority: P2)

Somebody browsing the marketplace, or an administrator in the admin space, signs out from the top bar
of the screen they are on, instead of going back to the app first.

**Why this priority**: both bars already show who is signed in and lead back to the app, so the
detour is one screen rather than two. It matters less than the main page, but it leaves no signed in
top bar without a way out.

**Independent Test**: sign in, open the marketplace, activate Sign out in its top bar and confirm the
session has ended. Repeat as an administrator from a screen of the admin space.

**Acceptance Scenarios**:

1. **Given** Pam is signed in and browsing the marketplace, **When** she activates Sign out in its top
   bar, **Then** her session ends and she sees the sign in screen.
2. **Given** a visitor who is not signed in is browsing the marketplace, **When** the top bar is
   shown, **Then** it offers the way to sign in and to create an account, and no Sign out.
3. **Given** Michael is an administrator on a screen of the admin space, **When** he activates Sign
   out in its top bar, **Then** his session ends and he sees the sign in screen.
4. **Given** Michael has just signed out from the admin space, **When** he goes back to its address,
   **Then** he is not shown the admin space.

---

### Edge Cases

- **A screen inside a vault.** Its top bar does not gain a second Sign out. The menu at the foot of
  the navigation column already holds it, and two controls doing one thing in one screen is noise.
- **A narrow screen.** The avatar stays in the bar at every width, so the menu stays reachable.
- **The session has already expired.** Sign out answers exactly as the existing control does. The
  answer itself is not changed by this feature.
- **Text typed in the form that creates a vault.** Signing out discards it, as the existing Sign out
  at the foot of the navigation column already does. Signing out deletes nothing, so it asks no
  confirmation.
- **The admin top bar.** Its colours are inverted. The avatar keeps a visible focus against it, and
  the menu it opens is drawn like every other menu.
- **The welcome screen.** It keeps no Sign out. It is read once, right after an account is created,
  and leads to the list of vaults.
- **A right to left language.** Sign out sits in the bar in the mirrored order, like the other
  controls.
- **The wording.** "Sign out" is the term the application already uses in both existing places. No
  other word ("Log out", "Logout") is introduced.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The top bar of the list of vaults MUST show the avatar of whoever is signed in, whether
  or not the account holds a vault, and the avatar MUST open a menu holding Account settings and
  Sign out.
- **FR-002**: Activating Sign out MUST end the session in exactly the way the existing Sign out does,
  and MUST send the person to the same place the existing Sign out sends them.
- **FR-003**: Every screen that shares the shell of the list of vaults MUST offer the same control in
  the same place.
- **FR-004**: The top bar of a screen inside a vault MUST NOT gain a Sign out control.
- **FR-005**: The control MUST be labelled "Sign out", the wording already used by the two existing
  controls, translated in every configured locale.
- **FR-006**: The control MUST be operable with the keyboard alone, focused visibly, and announced by
  its name on every screen width, including where the bar shows icons without words.
- **FR-007**: The bar MUST NOT show the email address of whoever is signed in. Account settings
  leaves the bar's own links once the menu carries it.
- **FR-008**: Signing out MUST NOT ask for a confirmation, consistent with the two existing controls.
- **FR-009**: In the marketplace top bar, the avatar of whoever is signed in MUST open the same menu,
  and a visitor who is not signed in MUST NOT be offered it.
- **FR-010**: In the admin top bar, the avatar MUST open the same menu on every screen of the admin
  space.
- **FR-011**: FR-002, FR-005, FR-006 and FR-008 apply to the marketplace and admin controls as they
  apply to the list of vaults.
- **FR-012**: The welcome screen MUST NOT gain a Sign out control.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: From the list of vaults, somebody signs out in one activation, without opening any other
  screen first. Today it takes at least two.
- **SC-002**: Every screen that shares the shell of the list of vaults, every marketplace screen seen
  by somebody signed in, and every screen of the admin space offers Sign out. Today none does.
- **SC-003**: A keyboard user reaches Sign out from the start of the list of vaults by tabbing through
  the top bar alone, without entering the body of the page.
- **SC-004**: Every configured locale shows the control in its own language, and the translation
  check passes.

## Assumptions

- "The main page" is the list of vaults, since it is where the root address sends anybody who is
  signed in and welcomed. The welcome screen that comes before it is out of scope.
- The control is placed in the existing top bar rather than in a new menu. The bar already holds
  every other way out of the screen, so it is where somebody looks.
- The marketplace and admin top bars are in scope, as clarified on 2026-10-13.
- Signing out keeps its current behaviour and destination. This feature changes where the control is
  offered, not what it does.
