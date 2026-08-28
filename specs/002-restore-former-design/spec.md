# Feature Specification: Restore the Former Design

**Feature Branch**: `2026-08-26-restore-former-design`

**Created**: 2026-08-26

**Status**: Draft

**Input**: User description: "i want to implement in a new pull request the former design we had before https://github.com/monicahq/erica/pull/50 which changes the deisgn completely, and adapt this new design to the new features made after this."

## User Scenarios & Testing *(mandatory)*

Pull request 50 replaced the design direction the product had until then. Its own description says
what it swapped: warm paper and terracotta instead of a neutral console, display type for page
titles, low warm shadows instead of borders alone, and a floating dock instead of a column of
navigation down the left. Everything merged after it was built in the replacement language, and
several of those screens (a contact and everything on it, sharing by link, the marketplace, the
admin space) never existed in the former one at all.

This feature puts the former direction back and makes it the design of the whole product again,
including the screens that were built after pull request 50 and therefore have no former version to
return to. Those screens are not reverted, they are dressed: the restored vocabulary is extended to
cover what they need.

The restored direction is defined by what pull request 50 took away:

- A near neutral palette. An off white page, white and barely tinted surfaces on it, near black
  text, grey secondary text, and one blue accent. Both a light and a dark theme, chosen by the
  reader and remembered between visits.
- One text family for the entire interface, at ordinary interface sizes. No display family, and no
  page title set in one.
- Separation carried by a hairline border and by space. Elevation is not a decoration: a surface
  that does not need to float does not float.
- Navigation as a column down the left of the screen, fixed on a wide window and sliding in over
  the page from a button in the bar on a narrow one. Inside a vault the column is that vault and
  the way across to another one. Inside the settings it is the settings and the way back out.
- A bar across the top that says where you are, plus who is signed in.

Two things are explicitly not reverted, because they are not visual:

- Every accessibility and correctness gain made after pull request 50 stays. State that was a
  coloured dot with a hidden word became a labelled badge, and it stays a labelled badge. Dialogs
  that claimed to be modal while the keyboard could walk out behind them gained a focus trap, and
  they keep it. Values that were shown as disabled boxes nobody could use became values, and they
  stay values.
- Every capability stays. No screen loses a control, a filter, a reorder, a live update or a route.
  This is a change of appearance and navigation, never of what the product can do.

There are two ways to sequence this and the first one is chosen, because it is how pull request 50
itself was rolled out and it is the only one that keeps the product usable at every commit. The
token layer and the shell land first, so every screen picks up the palette, the type and the
navigation at once and looks transitional until its turn comes. Each area is then migrated on its
own, and each migration is a slice that can be reviewed, tested and demonstrated by itself.

### User Story 1 - Sign in and find the navigation down the left again (Priority: P1)

Somebody signs in. The page is off white rather than warm, the text is set in the one interface
family, and there is a column down the left of the window carrying where they can go. The dock that
floated at the foot of the window is gone, and so is the menu behind it: switching vault, reaching
the settings and signing out are in the column. On a phone the column is not there until they press
the button in the bar, and then it slides in over the page and closes on escape, on a tap outside
it, or on following a link.

**Why this priority**: the palette, the type and the shell are the design. Nothing else can be
migrated before the vocabulary exists, and once this slice ships the whole product has already
changed direction even though individual screens are still wearing their old clothes. It is also
the only slice that is visible everywhere at once, so it is the one that has to be right.

**Independent Test**: sign in and reach every area of the product using the column alone, on a wide
window and on a narrow one, with the keyboard only, in both themes. The dock is nowhere. No screen
is unreachable and no screen is broken, even the ones not yet migrated.

**Acceptance Scenarios**:

1. **Given** a signed in user on a window wider than the large breakpoint, **When** they open any
   screen that belongs to a vault, **Then** a navigation column is fixed down the left carrying
   that vault's destinations, the vault switcher above them, and who is signed in at the foot as
   the way into their own settings.
2. **Given** a signed in user on a window narrower than the large breakpoint, **When** they press
   the menu button in the top bar, **Then** the column slides in over the page, the page behind it
   stops scrolling, focus moves into the column, and escape closes it and returns focus to the
   button.
3. **Given** a signed in user anywhere in the settings, **When** they look at the column, **Then**
   it carries the settings destinations and the way back out of the settings above them, and the
   entry for the screen they are on is marked by more than its colour.
4. **Given** a user who has chosen the dark theme, **When** they open any screen, **Then** the
   restored dark palette is in force from the first paint, with no flash of the light one.
5. **Given** a user whose system asks for reduced motion, **When** the column opens or a
   confirmation appears, **Then** nothing animates and everything still works.
6. **Given** any screen not yet migrated, **When** it is opened after this slice ships, **Then** it
   reads in the restored palette and type, every control on it still works, and nothing on it is
   illegible or unreachable.
7. **Given** a signed in user on a screen that belongs to no vault, **When** they look at the top
   bar, **Then** they can reach the documentation, the marketplace, the theme switch and their own
   settings, and an administrator additionally sees the one way into the admin space while nobody
   else sees any hint of it.
8. **Given** an account that has made no vault at all, **When** the user signs in, **Then** the
   shell still offers the settings, the theme switch and signing out, and nothing in the navigation
   points at a vault that does not exist.

---

### User Story 2 - Work through a vault on the restored design (Priority: P2)

Somebody opens their vault list, opens a vault, reads its dashboard, browses its contacts, creates
one, and changes the vault's settings. Every one of those screens is drawn in the restored language:
rows separated by hairlines inside a bordered box, headings that carry weight rather than a display
face, and a form whose label, control, hint and error read as one thing.

**Why this priority**: this is the path every user walks every day, and these screens all had a
former version, so restoring them is the least uncertain work in the feature. Done on its own it
already makes the daily path consistent.

**Independent Test**: create a vault, open it, create a contact in it, sort and filter the contact
list, change the vault's settings, and delete the vault, without meeting a screen in the replaced
language and without losing any control that exists today.

**Acceptance Scenarios**:

1. **Given** the vault list, **When** it is opened, **Then** each vault is a row in the restored
   language carrying its name, its colour and the way in, and the screen offers the way to create
   another.
2. **Given** a vault with contacts, **When** the contact list is opened, **Then** the list keeps
   every sort, filter and page control it has today, keeps its live updating, and each row carries
   the contact's avatar, name and the fields the list shows today.
3. **Given** the contact form, **When** a required value is left out, **Then** the error is attached
   to the field it belongs to, is announced to a screen reader, and is not carried by colour alone.
4. **Given** a vault's settings, **When** a destructive action is chosen, **Then** the warning that
   exists today is shown first, its dialog keeps the keyboard inside it, and escape cancels.
5. **Given** any of these screens, **When** it is read at 200% zoom on a narrow window, **Then**
   nothing is clipped, nothing overlaps, and no horizontal scrolling is needed to read a sentence.

---

### User Story 3 - Read and edit a contact on the restored design (Priority: P2)

Somebody opens a contact. The pages of its template are a strip of tabs, its sections are boxes,
and the values in them are readable at a glance. They edit it, fill in a photo and a document, look
at what changed and when, apply a newer version of its template, and see what was set aside when a
field went away. None of this existed before pull request 50, so all of it is new work in the
restored language rather than a revert.

**Why this priority**: a contact is the reason the product exists, and it is the largest surface
built after pull request 50. It is second only to the daily path because it is bigger, riskier and
depends on the form and box vocabulary that story 2 settles.

**Independent Test**: open a contact, read every page of it, edit each type of field including a
photo and a document, apply a newer template version, read the version history, restore from it,
and view the set aside values, without meeting a screen in the replaced language.

**Acceptance Scenarios**:

1. **Given** a contact whose template has several pages, **When** it is opened, **Then** the pages
   are a strip of tabs operable with the arrow keys, the current one is marked by more than its
   colour, and the sections of that page are shown beneath it.
2. **Given** a field that holds several values, **When** the contact is edited, **Then** each
   repetition can be added, changed and removed, and each one is labelled so a screen reader says
   which repetition it is.
3. **Given** a field marked sensitive, **When** the contact is read, **Then** it is marked as
   sensitive by a label and not by its colour alone.
4. **Given** a contact and a newer version of its template, **When** the newer version is applied,
   **Then** the warning and the confirmation that exist today are shown, the previous state is
   snapshotted, and the screen says what was set aside.
5. **Given** a contact's version history, **When** it is read, **Then** each entry says who changed
   what and when, and restoring from an entry still asks for confirmation first.
6. **Given** a contact with no avatar, **When** it is shown anywhere, **Then** the initials stand in
   for it and read against the restored palette in both themes.

---

### User Story 4 - Change the settings on the restored design (Priority: P3)

Somebody opens the settings home, then their profile, their preferences, their security, the logs,
the default values, the entity types and the template builder. Every screen is in the restored
language. The settings home, which pull request 50 introduced and which had no former version,
stays: there are more than twenty settings screens and they still need something above them.

**Why this priority**: these screens are visited rarely compared with a vault, and they are the
largest count of screens in the product, so they are the biggest slice and the least urgent one.
The template builder inside them is the single hardest screen in the feature.

**Independent Test**: walk every screen under the settings, change something on each, and confirm
each change persists and is confirmed, without meeting a screen in the replaced language.

**Acceptance Scenarios**:

1. **Given** the settings home, **When** it is opened, **Then** it lists every settings destination
   grouped as it is today, in the restored language.
2. **Given** the preferences screen, **When** a value is picked, **Then** picking it is the save, as
   it is today, and the confirmation says so.
3. **Given** a menu opened from a row inside a bordered box, **When** it opens, **Then** it is not
   clipped by the box it sits in.
4. **Given** the default values screens, **When** a value is retired, **Then** it says so in a label
   as well as in its struck out text.
5. **Given** the template builder, **When** it is used with the keyboard alone, **Then** pages can
   be traversed, sections and fields selected and reordered, and the panel describing the selected
   field stays in view.
6. **Given** the entity types and default values lists, **When** rows are reordered by dragging,
   **Then** the reorder still works and there is a keyboard way to do the same thing.
7. **Given** a settled identifier or a field's type, **When** it is shown, **Then** it is shown as a
   value and not as a box that invites typing and submits nothing.

---

### User Story 5 - Share by link, on the restored design and on the screens a stranger sees (Priority: P3)

Somebody creates a share link for a contact or a vault, gives it an expiry and a password, sees the
links they have made, and revokes one. Somebody who is not signed in opens that link, types the
password, and reads the contact. The screens a stranger sees have no navigation column, because
they are not inside the product and there is nowhere for them to go.

**Why this priority**: the public screens are the only part of the product a person outside the
account ever sees, so a half migrated one is the most visible inconsistency in the feature. It sits
below the settings only because there are far fewer screens.

**Independent Test**: create a share link with a password and an expiry, open it in a signed out
browser, read the shared record, let it expire, and confirm the gone screen and the password screen
are both in the restored language.

**Acceptance Scenarios**:

1. **Given** the share links screen for a contact, **When** it is opened, **Then** each link says
   what it points at, when it expires, whether it has a password, and offers revoking it behind a
   warning.
2. **Given** a share link with a password, **When** a stranger opens it, **Then** they get the
   password screen in the restored language with no navigation column, and a wrong password is
   refused with an error attached to the field.
3. **Given** a revoked or expired share link, **When** it is opened, **Then** the gone screen
   explains it in the restored language, says nothing about what the link used to point at, and
   offers no way into the product.
4. **Given** a shared contact, **When** a stranger reads it, **Then** the excluded content stays
   excluded exactly as it is today, and nothing the restored design adds reveals anything more.

---

### User Story 6 - Browse the marketplace on the restored design (Priority: P4)

Somebody opens the marketplace, reads what the catalogue holds, searches it, and opens a listing.
The marketplace has a shell of its own, and it is restored to read as part of the same product
rather than as a second one.

**Why this priority**: it is one small area with few screens, and nothing else depends on it.

**Independent Test**: open the marketplace, read its statistics, search the catalogue, and confirm
every screen is in the restored language in both themes.

**Acceptance Scenarios**:

1. **Given** the marketplace home, **When** it is opened, **Then** the catalogue cards, the search
   and the statistics are all in the restored language.
2. **Given** a search that matches nothing, **When** it is run, **Then** the empty state says so in
   the restored language and offers the way back to the whole catalogue.

---

### User Story 7 - Run the instance on the restored design (Priority: P4)

An administrator opens the admin space, reads the overview, looks up an account, moderates a
marketplace submission, reads the logs and manages the administrators. The admin space has a shell
of its own and it is restored too.

**Why this priority**: it is seen by very few people, and it is the newest area in the product, so
it has the least to unlearn. It is last because nothing else waits on it.

**Independent Test**: sign in as an administrator, walk every screen of the admin space, approve and
reject a submission, and confirm every screen is in the restored language while remaining invisible
to everybody else.

**Acceptance Scenarios**:

1. **Given** an administrator, **When** they open the admin space, **Then** its shell is in the
   restored language and it is plainly a different area from the ordinary application.
2. **Given** a regular user, a visitor, or a deactivated administrator, **When** they request any
   admin address, **Then** they are refused exactly as they are today, and the restored design
   changes nothing about that.

---

### User Story 8 - Sign in and register on the restored design (Priority: P5)

Somebody who is not signed in registers, signs in, asks for a magic link, resets a password, answers
a two factor challenge, or verifies an email address. These screens sit beside the panel carrying
the quote, and they go back to the restored language.

**Why this priority**: they are the first screens anybody sees, but they are few, they are simple,
and they change least between the two directions. They are last because the risk is lowest.

**Independent Test**: complete registration, sign out, sign in, ask for a magic link, reset a
password, and answer a two factor challenge, on a phone and on a wide window, in both themes.

**Acceptance Scenarios**:

1. **Given** any credentials screen, **When** it is opened, **Then** it is in the restored language,
   the panel beside it reads correctly in both themes, and it can be completed with the keyboard
   alone.
2. **Given** a credentials screen on a phone, **When** it is opened, **Then** the panel beside it
   gives way to the form rather than pushing it off the screen.

---

### Edge Cases

- A screen has not been migrated yet while the restored palette, type and shell are already global.
  It must stay legible, usable, contrast compliant and keyboard operable. Transitional is allowed;
  broken is not.
- A colour is the only thing carrying a meaning. The replaced direction introduced a vocabulary of
  object colours, and the restored one is near neutral. Wherever colour carries a meaning today
  (which entity type something is, which vault a row belongs to, whether a delivery succeeded, what
  state a template is in) the meaning keeps a word as well, and the colour is what is restrained,
  never the label.
- Both directions ship a light and a dark theme. Every restored surface has both, and the theme is
  applied before the first paint so no screen flashes the wrong one.
- The reader asks for reduced motion. The drawer, the confirmation toast and any transition the
  restored shell brings back must be still and still work.
- The reader zooms to 200%, or reads on a phone, or reads in a right to left language. The column,
  the bar, the tab strips and the two column screens all have to hold.
- A page is navigated without a full reload. State that belongs to the shell (whether the drawer is
  open) must survive the way the product navigates today, and must be declared where it is walked
  again after such a navigation.
- A dialog is opened. Every dialog that traps focus today still traps it after the restoration. This
  is the regression most likely to be reintroduced by going back to older components.
- A menu is opened from inside a bordered box. It must not be clipped by that box.
- A screen belongs to no vault (the vault list, the settings, the marketplace, the admin space). The
  navigation column has nothing to be about, so those screens use a shell without one, and reach
  the settings, the theme switch and signing out from the bar instead.
- An account has one vault, or none. The switcher and the navigation must both read correctly.
- Navigation entries point at screens that do not exist yet (the journal, tasks, reminders, files,
  gifts, the documentation). They stay drawn and not offered, as they are today. A link that leads
  nowhere is worse than a word that does not move.
- A contact has no avatar, a vault has no contacts, a list has no rows, a search matches nothing.
  Every empty state is part of the restoration.
- The interface is read in each of the nine configured locales. Any string the restoration adds or
  changes exists in all of them, and no wording that varies with a number is assembled by comparing
  a count to one.

## Requirements *(mandatory)*

### Functional Requirements

#### The restored vocabulary

- **FR-001**: The product MUST present a single visual language across every screen. No screen may
  show the replaced language and the restored one at the same time, and no two screens reachable
  from each other may be in different languages once their area has been migrated.
- **FR-002**: The palette MUST be the near neutral one of the former direction: an off white page,
  white and barely tinted surfaces, near black primary text, grey secondary text, and one blue
  accent, with a dark counterpart for every one of them.
- **FR-003**: The interface MUST be set in one text family at interface sizes. No display family may
  be used, and no page title may be set in one.
- **FR-004**: Separation between surfaces MUST be carried by a hairline border and by space. A
  surface may be raised only where raising it carries meaning, and never as decoration.
- **FR-005**: Machine values (identifiers, keys, settled slugs, keyboard shortcuts) MUST remain
  visually distinguishable from prose, and MUST never be shown as a control that cannot be used.
- **FR-006**: Colour MUST NOT be the only carrier of any meaning on any screen. Every state, tone,
  category and current position MUST also be carried by a word, a shape or an announced attribute.
- **FR-007**: Both a light and a dark theme MUST be available on every screen, selectable by the
  reader, remembered between visits, and applied before the first paint.

#### The shell and the navigation

- **FR-008**: A signed in screen that belongs to a vault MUST offer a navigation column down the
  left carrying that vault's destinations, the way across to another vault above them, and the way
  into the signed in user's own settings at its foot.
- **FR-009**: A signed in screen under the settings MUST offer a navigation column carrying the
  settings destinations and the way out of the settings above them.
- **FR-010**: Below the large breakpoint the navigation column MUST become a drawer over the page,
  opened from a button in the top bar, closed by escape, by a press outside it and by following a
  link, with focus moved into it while it is open and returned to the button when it closes, and
  with the page behind it prevented from scrolling.
- **FR-011**: The floating dock, the menu behind it and the launcher MUST be removed, and every
  destination they carried MUST be reachable from the restored navigation or the top bar.
- **FR-012**: Every signed in screen MUST carry a top bar saying where the reader is, plus who is
  signed in, the theme switch, the documentation, the marketplace, and for an administrator only,
  the one way into the admin space.
- **FR-013**: A signed in screen that belongs to no vault MUST use a shell without a navigation
  column and MUST still offer the settings, the theme switch and signing out from the bar.
- **FR-014**: A screen whose content is wider than the reading column MUST be able to ask for the
  wider column, and a screen whose actions must stay reachable through a long form MUST be able to
  ask for a bar at the foot of the window.
- **FR-015**: The screens a private link opens MUST use a shell with no navigation, no way into the
  product and nothing that identifies the account.
- **FR-016**: The marketplace and the admin space MUST each keep a shell of their own, in the
  restored language, plainly part of the same product.
- **FR-017**: The current position MUST be announced to a screen reader on every navigation
  component, not merely styled.

#### Coverage

- **FR-018**: Every signed in screen MUST be migrated: the vault list and form, the vault dashboard
  and settings, the contact list, the contact form, the contact detail with its pages, sections,
  field values, groups, set aside values and version history, the contact edit and delete flows,
  the share link screens, the settings home and every screen under the settings including the
  template builder, the field type picker, the template and section share, export and import
  screens, the marketplace, the admin space and the getting started screen.
- **FR-019**: Every screen shown to somebody who is not signed in MUST be migrated: registration,
  sign in, the magic link request and confirmation, the password reset, the two factor challenge,
  the email verification, and the screens a private link opens including the password screen and
  the gone screen.
- **FR-020**: Every shared interface element MUST exist in the restored language: buttons in all
  their variants, form fields with their label, hint and error, boxes and their rows, badges, chips,
  tabs, menus, dialogs, notices, empty states, breadcrumbs, avatars, contact avatars, entity icons,
  the confirmation toast, the language picker and the theme switch.
- **FR-021**: Elements introduced after pull request 50 that have no former version MUST be given a
  restored form consistent with the vocabulary in FR-002 to FR-006, rather than being left as they
  are or dropped.

#### What must not change

- **FR-022**: No capability may be lost. Every control, filter, sort, page control, reorder, live
  update, keyboard shortcut, route and confirmation that exists today MUST still exist afterwards.
- **FR-023**: Every accessibility gain made after pull request 50 MUST be preserved, specifically:
  state shown as a labelled badge rather than a coloured dot, dialogs that keep the keyboard inside
  them, and settled values shown as values rather than as disabled controls.
- **FR-024**: Every destructive action MUST still warn before it is performed, and the warning MUST
  keep the keyboard inside it and be cancellable by escape.
- **FR-025**: Compartmentalisation MUST be untouched. A record reached through another account, or
  through the wrong vault of the same account, still comes back as not found, and the admin space
  stays invisible to everybody who is not an administrator.
- **FR-026**: What a share excludes MUST stay excluded. Nothing the restoration adds to a public
  screen may reveal content that is outside a share today.

#### Quality

- **FR-027**: Every migrated screen MUST be operable with the keyboard alone, announced correctly by
  a screen reader, focused visibly at all times, contrast compliant, readable zoomed to 200%, free
  of information carried by colour alone, free of animation when reduced motion is asked for, and
  usable on desktop, tablet and phone.
- **FR-028**: Every user facing string the restoration adds or changes MUST be translatable and MUST
  be translated in every configured locale, and any wording that varies with a number MUST be asked
  for in the way that lets a locale choose it rather than by comparing a count to one.
- **FR-029**: Every shared interface element MUST have a test that renders it and asserts the copy
  and the markup that carry its meaning, and every migrated screen MUST be covered by a test that
  asserts what the screen says rather than how it is styled.
- **FR-030**: The design language MUST remain expressed as utility classes on the markup, with the
  configuration file holding only the tokens and the palette. No second styling mechanism may be
  introduced alongside it.
- **FR-031**: Exactly one design language MUST remain in the codebase when the feature is done. The
  replaced tokens, components and shells MUST be removed rather than left beside the restored ones.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A reader can walk every screen of the product, signed in and signed out, without
  meeting two visual languages, and a reviewer looking at any two screens cannot tell which was
  migrated first.
- **SC-002**: Every screen is available in both the light and the dark theme, and no screen shows
  the wrong theme at any point during loading.
- **SC-003**: Every screen can be completed with the keyboard alone, including the navigation
  drawer, every dialog, every tab strip and every reorderable list.
- **SC-004**: No screen carries any meaning by colour alone, verified state by state on every screen
  that has states.
- **SC-005**: Every screen stays readable and usable at 200% zoom and on a 375 pixel wide window,
  with no clipped content and no horizontal scrolling to read a sentence.
- **SC-006**: Every capability that exists before the change still exists after it, verified by the
  full test suite passing with no test removed and no assertion weakened.
- **SC-007**: Every user facing string is present in all nine configured locales, verified by the
  localisation check reporting nothing missing.
- **SC-008**: Every shared interface element and every migrated screen has a test, and the suite
  passes on all three supported database engines.
- **SC-009**: When the feature is done, none of the replaced direction's tokens, components or
  shells remains in the codebase.
- **SC-010**: Each priority slice can be merged on its own with the product left fully usable, and
  no slice leaves a screen unreachable or a control missing.

## Assumptions

- "The former design" means the direction pull request 50 replaced, as that pull request itself
  describes it: neutral palette, one interface text family, hairline borders rather than warm
  shadows, and a navigation column rather than a floating dock. It is restored as the product's
  design going forward, not preserved as a museum copy of the old markup.
- Screens built after pull request 50 have no former version. They are given a form in the restored
  vocabulary rather than reverted, and where the vocabulary is missing something they need (a tab
  strip, a badge, a chip, a card for a catalogue listing) it is added to the restored vocabulary
  rather than borrowed from the replaced one.
- The replaced direction is removed, not offered as an alternative theme. Two design languages
  maintained in parallel would double the work of every future screen, and the light and dark themes
  are already the choice the reader gets.
- The settings home screen, which pull request 50 introduced and which had no former version, is
  kept. More than twenty settings screens still need something above them.
- Where colour carries a meaning today, the meaning is kept and the colour is restrained to the
  restored palette. Vault colours and entity type colours are content chosen by the user, so they
  stay as they are, and only the interface around them becomes neutral.
- This lands as one branch and one pull request, as asked. The priority slices are the order of work
  within it, and each one is a commit that leaves the product usable, so the pull request can be
  reviewed slice by slice.
- Email templates are outside the scope of this feature. They are read in a mail client rather than
  in the product, they do not use the interface shell, and nothing in the restoration changes them.
- The written documentation under `docs/` and `spec/` is outside the scope of this feature. It
  describes what the product does, not how it looks.
