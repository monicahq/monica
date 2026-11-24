# Feature Specification: Error 500 page

**Feature Branch**: `2026-11-24-error-500-page`

**Created**: 2026-11-24

**Status**: Draft

**Input**: User description: "branche depuis monica main. fais en sorte qu'on ait une nouvelle page d'erreur 500 basée sur le design suivant [two mockups: a light one, pandas in hard hats repairing a stone 500 in a sunny meadow; a dark one, the same crew at night with cones and a barrier] dark et light mode"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Somebody hits a server failure and sees a page that belongs to Monica (Priority: P1)

A person is using Monica, signed in or not, and the instance fails while answering their request. Today
they get the framework's bare error screen, which looks nothing like the rest of the product and
offers no way on. They should instead see the same kind of page the not found, not allowed, expired
and too many attempts failures already show: the instance name at the top, an illustration of the
panda crew repairing a stone "500", a sentence saying the fault is on the instance's side, a way on,
and the footer.

**Why this priority**: This is the whole feature. Every other story refines it.

**Independent Test**: Make any request fail with a server error and check the answer carries status
500, the heading, the line under it and the way on.

**Acceptance Scenarios**:

1. **Given** a request fails with a server error, **When** the answer reaches the browser, **Then** its
   status is 500 and the page shows the instance name, the illustration, the heading, the line under
   it, the actions and the footer, laid out like the other error pages.
2. **Given** the page is shown, **When** somebody reads it, **Then** the copy says the problem is on
   the instance's side and that trying again later may work, and promises nothing the instance cannot
   keep (it does not claim somebody is already fixing it).
3. **Given** the page is shown in French or any other shipped language, **When** the reader's locale
   can be resolved, **Then** every word of the page is in that language.

---

### User Story 2 - The illustration follows the reader's colour scheme (Priority: P1)

The light mockup (daylight meadow, sun, daisies) is shown to a reader whose system asks for a light
scheme, and the dark mockup (night, moon, cones, barrier) to one whose system asks for a dark scheme,
exactly as the not found page swaps its two illustrations today.

**Why this priority**: The request names both modes explicitly; a page that only works in one of them
is not done.

**Independent Test**: Render the page and confirm it offers both illustrations, one for each scheme,
and that the surrounding text and button stay legible in both.

**Acceptance Scenarios**:

1. **Given** a light colour scheme, **When** the page is shown, **Then** only the daylight illustration
   is visible and the text and controls meet contrast requirements on the light background.
2. **Given** a dark colour scheme, **When** the page is shown, **Then** only the night illustration is
   visible and the text and controls meet contrast requirements on the dark background.

---

### User Story 3 - The reader can try again or leave (Priority: P2)

The two mockups disagree on the action: the light one offers "Return home", the dark one "Try again"
with a reload icon. The page offers both. "Try again" is the main action and asks for the same address
once more; "Return home" is the secondary one and leads to the home screen.

**Why this priority**: A transient failure is often fixed by a second attempt, so trying again is the
most useful thing to offer, but a reader stuck on a failing screen needs a way out too.

**Independent Test**: Trigger a failure on a known address and check that "Try again" points at that
address and "Return home" at the home screen.

**Acceptance Scenarios**:

1. **Given** a read request failed, **When** the reader activates "Try again", **Then** the browser asks
   for the same address again.
2. **Given** a form submission failed, **When** the reader activates "Try again", **Then** the form is
   not submitted a second time without their knowledge; they are taken back to the page they came
   from, where they can submit again themselves.
3. **Given** the page is shown, **When** the reader activates "Return home", **Then** they reach the
   home screen.

---

### Edge Cases

- **The database or the session store is down.** This is the most common cause of a 500. The page
  must still render: nothing on it may depend on reading the database, the signed in person or the
  account's settings. When the reader's locale cannot be resolved, the page falls back to the
  instance's default language.
- **The page itself fails while rendering.** The reader must still receive a 500 with a minimal
  answer, never a blank screen or a loop.
- **The instance runs in debug mode.** A developer keeps seeing the detailed exception screen; the
  page is what everybody sees when debug mode is off.
- **A request that expects JSON** (the API, an Alpine Ajax request) keeps receiving a JSON error, not
  this page.
- **A failure inside a partial page update.** No fragment of the error page is merged into the
  screen the reader was on; the screen stays as it was. Showing the error page itself after a failed
  partial update is out of scope, as it is today for every other error status.
- **No technical detail leaks.** The page never shows the exception message, a stack trace, a file
  path or a query, whatever the failure was.
- **Narrow screens.** At phone width the illustration scales down, nothing scrolls sideways, and the
  actions stay reachable without scrolling past the illustration more than once.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every request that fails with a server error (status 500) and expects a page MUST be
  answered with the new error page, keeping status 500.
- **FR-002**: The page MUST use the same structure as the existing error pages: the instance name
  linking home at the top, an illustration, a heading, one line under it, the actions, and the shared
  footer.
- **FR-003**: The heading MUST read "We’re sorry, something went wrong on our end." and the line under
  it "Please try again in a moment." The line MUST NOT promise that somebody is working on a fix,
  because a self hosted instance notifies nobody.
- **FR-004**: The page MUST show the daylight illustration under a light colour scheme and the night
  illustration under a dark colour scheme, and never both at once.
- **FR-005**: The illustrations MUST be decorative to assistive technology (they carry no information
  the text does not already give).
- **FR-006**: The page MUST offer "Try again" as its main action and "Return home" as its secondary
  action, with the behaviour described in User Story 3.
- **FR-007**: The page MUST render without reading the database, the signed in person or any account
  setting.
- **FR-008**: The page MUST NOT reveal the exception, its message, a stack trace or any server detail.
- **FR-009**: Every string on the page MUST be translatable and translated into every shipped locale.
- **FR-010**: The page MUST meet WCAG 2.2 AA in both colour schemes: contrast, a single main heading,
  visible focus on every control, and a usable keyboard route through the actions.
- **FR-011**: Requests expecting JSON MUST keep receiving the JSON error they receive today.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of server failures on page requests answer with the Monica error page and status
  500, in place of the framework's generic screen.
- **SC-002**: The page renders when the database is unreachable.
- **SC-003**: The page reads correctly in every shipped locale, with no untranslated string.
- **SC-004**: An accessibility review finds no WCAG 2.2 AA failure on the page, in light or dark.
- **SC-005**: At a 375 pixel wide screen the page shows no horizontal scrolling, and both actions are
  visible after at most one scroll.

## Assumptions

- The page reuses the existing error page shell rather than introducing a second one. Its footer keeps
  the two links every error page has today (Docs, Legal). The mockups also show Status and Help; the
  instance has no status page or help address to point at, and adding them would change the four
  existing error pages too, so they are out of scope.
- The two mockups use different copy. One wording is used in both schemes: the light mockup's heading,
  with a line that drops the promise of a fix (see FR-003). The "(500)" from the dark mockup is not
  repeated in the copy; the illustration already shows the number.
- The illustrations are the scene alone, cropped from the two supplied mockups without their text,
  logo, button or footer, and stored the same way as the not found illustrations. Producing those two
  crops is a dependency of this feature.
- The maintenance page (503) and other server statuses are out of scope.
