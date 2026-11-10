# Feature Specification: Admin space redesign

**Feature Branch**: `2026-11-09-admin-redesign`

**Created**: 2026-11-09

**Status**: Draft

**Input**: User description: "implement new design for the admin panel based on this design" (Claude Design project `b78dc791-8645-424e-b24c-635ab7aea4f3`, file `Admin Overview.dc.html`, built on the Monica design system).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - An administrator always knows they are acting on the whole instance (Priority: P1)

An administrator opens any screen of the admin space. Before they read anything else, the screen tells them they have left their own account: a striped band runs across the very top, the bar carries an "Instance" badge next to "Admin space", and a warning strip under the bar says, in a full sentence, that they are looking at the instance and that anything they change applies to every account on it. The sections of the admin space are listed under the bar, each with its icon, and the one they are on is marked as current. The way back to their own account sits at the right of the bar, next to the theme switch and their account menu.

**Why this priority**: The admin space is the one place where a click affects every account. Today the warning is a small grey line in the bar that disappears on narrow screens. Making the shift unmistakable on every admin screen is the main safety gain of the redesign, and it covers all seven screens at once.

**Independent Test**: Sign in as an administrator, open each of the seven admin screens, and check that each shows the badge, the warning sentence, the section list with exactly one current section, and the way back out.

**Acceptance Scenarios**:

1. **Given** an administrator on any admin screen, **When** the screen renders, **Then** it shows the striped band, the "Admin space" title with the "Instance" badge, the warning "You are looking at the instance, not at your own account. Anything you change here applies to every account on it.", and the seven sections: Overview, Moderation, Accounts, Configuration, Flags, Administrators, Record.
2. **Given** an administrator on the Accounts screen, **When** they look at the section list, **Then** Accounts is marked as the current section both visually and for assistive technology, and no other section is.
3. **Given** an administrator using a screen 320 pixels wide, **When** the screen renders, **Then** the warning sentence is still shown in full, every section can still be reached, and nothing overflows the page horizontally.
4. **Given** an administrator who has chosen the dark theme, **When** any admin screen renders, **Then** the bar stays dark and the warning strip uses the dark theme's warning colours.

---

### User Story 2 - An administrator reads where the instance stands at a glance (Priority: P2)

An administrator opens the Overview. They see when the figures were counted, a way to count again, a single strip of six all time totals (accounts, people, vaults, contacts, storage held, emails sent), and two panels side by side: one saying what is waiting for their decision in moderation, with a way to open it, and one saying how many people signed in over the last 7 days and how many accounts were created over the last 30 days.

**Why this priority**: The Overview is the first screen of the admin space. Grouping the figures by what they mean (totals, what needs a decision, recent activity) replaces nine look-alike tiles and makes the one figure that asks for action, the moderation queue, stand on its own.

**Independent Test**: With a known set of accounts, contacts, emails and submissions, open the Overview and compare every figure to the expected count.

**Acceptance Scenarios**:

1. **Given** an instance holding 3 accounts, 3 people, 4 vaults and 25 contacts, **When** the administrator opens the Overview, **Then** the totals strip shows those four figures, the storage held in a readable unit, and the number of emails sent, followed by the line "All time, since the instance was installed."
2. **Given** the Overview was opened at 14:32 in the administrator's time zone, **When** it renders, **Then** it reads "Counted at 14:32" in the administrator's chosen time format.
3. **Given** a submission arrives after the Overview was opened, **When** the administrator activates "Count again", **Then** the screen is counted afresh and the time shown is updated.
4. **Given** nothing is waiting in moderation, **When** the Overview renders, **Then** the "Waiting for you" panel shows 0 and "Nothing in the moderation queue right now.", with a link to open moderation.
5. **Given** 2 submissions are waiting, **When** the Overview renders, **Then** the panel shows 2 and a sentence saying how many submissions wait for a decision, worded correctly for that number in every language, with a link to open moderation.

---

### User Story 3 - An administrator sees what happened to the emails the instance sent (Priority: P3)

On the Overview, a panel titled "Where the emails got to" shows every email the instance has sent, divided by what the mail provider reported back: delivered, bounced, marked as spam, or still on its way. A single horizontal bar shows the share of each, and under it a legend names each status with its count.

**Why this priority**: The figures exist today as four separate tiles. Showing them as parts of one whole makes a delivery problem (a large bounced or spam share) visible without arithmetic. It is useful but less urgent than the safety and the main figures.

**Independent Test**: Seed emails in each of the four states, open the Overview, and check the four counts, the bar's proportions and its text alternative.

**Acceptance Scenarios**:

1. **Given** 6 delivered, 2 bounced, 1 marked as spam and 1 on its way, **When** the Overview renders, **Then** the legend shows 6, 2, 1 and 1, and the bar is divided 60%, 20%, 10% and 10% in that order.
2. **Given** the same emails, **When** a screen reader reaches the bar, **Then** it announces a single sentence giving all four counts out of the total, so no figure is carried by colour alone.
3. **Given** the instance has sent no email, **When** the Overview renders, **Then** the bar is drawn empty, the legend shows four zeros, and nothing divides by zero.

---

### Edge Cases

- **A very large count** (for example a million contacts): the figure stays on one line within its cell of the totals strip at every width, and the strip reflows from six columns to three, then to two, rather than shrinking the type.
- **A section label that runs long in another language** (German, Finnish): the section list scrolls horizontally rather than wrapping or clipping, and the current section stays reachable with the keyboard.
- **Print**: the bar, the band and the warning strip are left out of a printed page, as the bar is today.

## Requirements *(mandatory)*

### Functional Requirements

**Shell of the admin space (every admin screen)**

- **FR-001**: Every admin screen MUST show, at the top, a striped band that marks the admin space. The band is decorative and MUST be hidden from assistive technology.
- **FR-002**: The bar MUST show the product mark, the title "Admin space" and a badge reading "Instance". The bar MUST stay dark in both the light and the dark theme.
- **FR-003**: The bar MUST offer "Leave the admin space", the theme switch and the administrator's account menu, as it does today.
- **FR-004**: Under the title row, the bar MUST list the seven sections (Overview, Moderation, Accounts, Configuration, Flags, Administrators, Record), each with its icon, in that order, as a labelled navigation landmark. The current section MUST be marked visually by more than colour and announced as the current page.
- **FR-005**: Under the bar, every admin screen MUST show a warning strip with the sentence "You are looking at the instance, not at your own account. Anything you change here applies to every account on it." The sentence MUST be shown in full at every width, including 320 pixels.
- **FR-006**: The warning strip MUST replace the current one line warning inside the bar; the warning MUST NOT be shown twice.

**Overview screen**

- **FR-007**: The Overview MUST show its title (with no line under it, at the user's request), the time the figures were counted in the administrator's own time zone and time format, and a "Count again" control that counts the figures afresh.
- **FR-008**: The Overview MUST show a section titled "What the instance holds" containing, in one strip, six all time totals with their icons: Accounts, People, Vaults, Contacts, Storage held (in a readable unit) and Emails sent, followed by "All time, since the instance was installed."
- **FR-009**: The totals strip MUST show six columns on wide screens, three on medium screens and two on narrow ones.
- **FR-010**: The Overview MUST show a panel "Waiting for you" (subtitle: "Submissions people sent in that need a decision") with the number of marketplace submissions waiting for a decision, a sentence describing that number, and a link "Open moderation" to the moderation queue. When the number is 0, the sentence MUST be "Nothing in the moderation queue right now."
- **FR-011**: The Overview MUST show a panel "Coming and going" (subtitle: "Who used the instance lately") with the number of people who signed in over the last 7 days and the number of accounts created over the last 30 days. Each label names its period and reads correctly whatever the figure (wording in research R7).
- **FR-012**: The Overview MUST show a panel "Where the emails got to" (subtitle: "Every email the instance has sent, by what the mail provider reported back") with a bar divided by the share of each delivery status and a legend naming Delivered, Bounced, Marked as spam and On its way with their counts.
- **FR-013**: The emails bar MUST carry a text alternative stating every status count and the total. Each status MUST be distinguishable in the legend by its name, never by colour alone.
- **FR-014**: The two panels "Waiting for you" and "Coming and going" MUST sit side by side when there is room and stack when there is not.
- **FR-015**: The separate tiles for "Submissions waiting", "Signed in" and "New accounts", and the warning notice about waiting submissions, MUST be removed, since the panels now carry those figures.
- **FR-016**: The notice shown on an instance with no account MUST stay, though the design (drawn on a populated instance) does not show it.
- **FR-017**: Every figure on the Overview MUST keep the meaning it has today; the redesign MUST NOT change how anything is counted.

**Across the admin space**

- **FR-018**: Every new or changed wording MUST be translated into every configured language, and every wording that contains a number MUST be worded for that number.
- **FR-019**: Every admin screen MUST meet WCAG 2.2 AA, in both themes, at 200% zoom and at 320 pixels wide.
- **FR-020**: Who may reach the admin space MUST NOT change: a visitor, a regular user and a deactivated administrator still get not found.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: On all seven admin screens, the instance warning is visible without scrolling, at every width from 320 pixels up, in both themes.
- **SC-002**: An administrator can tell how many submissions wait for a decision, and reach the queue, in one action from the Overview.
- **SC-003**: Every figure on the Overview matches the figure the current Overview shows for the same data.
- **SC-004**: The Overview and the shell pass an automated and a manual WCAG 2.2 AA check with no violation, in both themes.
- **SC-005**: No admin screen other than the Overview changes below the warning strip.
- **SC-006**: Every new wording exists in every configured language, and the localisation check passes.

## Assumptions

- **The design covers the shell and one screen.** It draws the Overview only. The shell (band, bar, section list, warning strip) is shared by all seven admin screens, so it changes on all of them; the body of the six other screens is left as it is and is out of scope.
- **The striped band and the amber badge are deliberate.** The Monica design system forbids patterns and accent colours. The admin space is the one place where the product wants to look different from an account, so this design departs from that rule on purpose, and only inside the admin space. The design shows the band switched on by default; it ships always on, with no setting to turn it off.
