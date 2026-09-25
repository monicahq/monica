# Feature Specification: Welcome screen

**Feature Branch**: `2026-09-24-welcome-screen` | **Created**: 2026-09-24 | **Status**: Draft

**Input**: "A welcome screen at `welcome`, shown once after the account is created, still open when
typed again. **Let me in** flags the user so they are not sent there again. Design:
`Welcome options.dc.html`."

## Context

Every way into the application (password, passkey, magic link, SSO, two factor, confirming the email
address) ends on the home address, which sends the user on to their vaults or to getting started.
The welcome screen sits in front of that decision, once.

The design file holds three directions. Direction **1a** ("Turn 1", drawn in `Welcome.dc.html`) is
built: a centred letter on the page ground, the logo, the caption "Welcome to Monica", the greeting,
a panel holding the letter signed with the two founders’ portraits, and a primary button **Let me in**. 2a and
2b are not built.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Be welcomed once (Priority: P1)

Pam has just created her account and confirmed her email address. Before getting started, she reads
a short letter from the people behind Monica, greeting her by her first name. She presses **Let me
in** and goes on. The next time she signs in, she lands where she would have landed before.

**Independent Test**: create an account, reach the home address, check the welcome screen opens;
press Let me in, check the user is marked and sent on; reach the home address again, check it does
not open.

**Acceptance Scenarios**:

1. **Given** a user who has never pressed Let me in, **When** they reach the home address, **Then**
   they are sent to `welcome`.
2. **Given** the welcome screen, **Then** it shows the caption "Welcome to Monica", the greeting "Hi
   Pam, we’re glad you’re here.", the line "A place to remember the little things about the people
   who matter to you.", the three paragraphs of the letter, the signature with "The people behind
   Monica", and the button **Let me in**.
3. **Given** the welcome screen, **When** Let me in is pressed, **Then** the user is marked as
   welcomed and sent to the home address, which sends them to their vaults or to getting started.
4. **Given** a user who has pressed Let me in, **When** they reach the home address by any means,
   **Then** they are not sent to `welcome`.
5. **Given** Let me in is pressed a second time (a second tab, the back button), **Then** nothing
   fails and the user is sent on the same way.

### User Story 2 - Read it again (Priority: P2)

Jim pressed Let me in a month ago. He types the `welcome` address and the screen opens as the first
time. Pressing Let me in sends him on.

**Independent Test**: as a user already welcomed, open `welcome`, check it renders; press Let me in,
check the user is sent on and the mark is unchanged.

**Acceptance Scenarios**:

1. **Given** a user already welcomed, **When** they open `welcome` directly, **Then** it renders.
2. **Given** that user presses Let me in, **Then** they are sent on and the mark is unchanged.

### User Story 3 - Dismiss it from another application (Priority: P3)

A native application signs Pam in through the API. It reads whether she has been welcomed, shows its
own welcome if not, and marks her as welcomed, so the web application does not greet her a second
time.

**Independent Test**: read the profile through the API, mark the user as welcomed, read it again.

**Acceptance Scenarios**:

1. **Given** the profile read through the API, **Then** it says when the user was welcomed, or that
   they have not been.
2. **Given** the API marks the user as welcomed, **Then** the web home address no longer sends them
   to `welcome`, and marking again changes nothing.

### Edge Cases

- A user who leaves without pressing Let me in is not marked, and meets the screen again at their
  next visit to the home address.
- Other addresses (a bookmarked vault, a link from an email) are not intercepted.
- Nobody signed in, a deactivated user and an unconfirmed email address are all handled as on every
  other screen: sign in, refusal, confirmation notice.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST record for each user when they were welcomed, empty when the user is
  created.
- **FR-002**: The home address MUST send a signed-in user who has not been welcomed to `welcome`,
  before deciding between vaults and getting started.
- **FR-003**: The screen at `welcome` MUST render for every signed-in user, welcomed or not.
- **FR-004**: The screen MUST greet the user by first name and carry the copy of User Story 1,
  scenario 2, translated in every configured locale.
- **FR-005**: Let me in MUST mark the user and send them to the home address; when the user is
  already marked, it MUST succeed and keep the first moment.
- **FR-006**: The screen MUST be reachable only by a signed-in, active user with a confirmed email
  address.
- **FR-007**: The screen MUST meet WCAG 2.2 AA: one main heading (the greeting), Let me in operable
  by keyboard with a visible focus ring, readable at 200 per cent zoom and at a phone’s width.
- **FR-008**: The API MUST expose when the user was welcomed on the profile, and MUST let the user
  be marked as welcomed with the rule of FR-005.

### Key Entities

- **User**: gains one fact, the moment they were welcomed, empty until then.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A newly created account sees the welcome screen on its first visit to the home address,
  whichever way it signed in.
- **SC-002**: After being welcomed, a user is never sent to the screen again without asking for it.
- **SC-003**: A user can reread the screen at any time, and leaves it in one action.

## Assumptions

- "First connection" is read as the first visit to the home address, where every sign-in path ends.
- The letter and the signatories come from the design, written as it stands and translated.
- The screen stands outside any vault, like getting started. The mark is not shown in settings.
