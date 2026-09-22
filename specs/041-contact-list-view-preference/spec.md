# Feature Specification: Contact list view preference

**Feature Branch**: `2026-09-22-contact-list-view-preference`

**Created**: 2026-09-22

**Status**: Draft

**Input**: User description: "i want to save at the user level, these choices [the filter chips and the sort controls on the People screen]. That means: 1. everybody, favorites etc... filters should be urls and not query params. 2. filters used and sort order should be saved at user level, so when we land on the people page again, we find the same view we used. later we'll add an option in the settings to define this default view too."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The People list opens where I left it (Priority: P1)

Somebody who works mostly from their Favourites, ordered by who they spoke to least recently, sets
that up once. Every later visit to the People list of any vault opens on that same view. They never
save it, and they never set it up again.

**Why this priority**: this is the whole point of the request. Reconstructing the same view on every
visit is the repeated cost the feature removes.

**Independent Test**: choose a grouping, a sort field and a direction, leave the screen, come back to
the People list of the same vault in a new session, and find the three choices as they were left.

**Acceptance Scenarios**:

1. **Given** somebody viewing Favourites sorted by Last spoke, newest first, **When** they leave the
   People list and return to it later in a new session, **Then** the list shows Favourites sorted by
   Last spoke, newest first.
2. **Given** somebody who has never chosen a view, **When** they open the People list, **Then** the
   list shows Everybody sorted by Name, A to Z.
3. **Given** somebody viewing Archived in one vault, **When** they open the People list of another of
   their vaults, **Then** that list also shows Archived.
4. **Given** somebody viewing Favourites, **When** they change the sort to Nickname, **Then** the
   grouping stays Favourites and the new sort is what the next visit shows.

---

### User Story 2 - Every grouping has an address of its own (Priority: P2)

Each of the four groupings can be linked to, bookmarked and shared, and following that link lands on
that grouping. Today three of the four chips work; the Names only chip leads to a page that cannot be
found, because no address is declared for it.

**Why this priority**: it repairs a control that is on the screen and does not work, and it is what
lets the remembered view be expressed as an address rather than hidden state.

**Independent Test**: open each of the four addresses directly, with no prior view recorded, and see
the matching grouping rendered.

**Acceptance Scenarios**:

1. **Given** a vault holding people written down as first names only, **When** somebody follows the
   Names only chip, **Then** the list of those people is shown rather than a page that cannot be
   found.
2. **Given** any of the four groupings on screen, **When** somebody copies the address and opens it in
   another browser signed in as the same person, **Then** the same grouping is shown.
3. **Given** a contact whose name reduces to the spelling a grouping uses, **When** that contact is
   written down, **Then** it is given a different address so that both stay reachable.

---

### User Story 3 - A stated address wins, and becomes what is remembered (Priority: P3)

Somebody follows a link to Archived while their remembered view is Favourites. They see Archived,
not Favourites, and Archived is what their next visit opens on.

**Why this priority**: without it, the remembered view fights every link in the product, including the
Names only link the relationship panel already writes. It is small, but the feature is wrong without
it.

**Independent Test**: record one grouping, then open the address of a different grouping, and check
both that it is rendered and that a later bare visit opens on it.

**Acceptance Scenarios**:

1. **Given** Favourites is the remembered grouping, **When** somebody opens the Archived address,
   **Then** Archived is shown.
2. **Given** the step above, **When** they later open the People list without stating a grouping,
   **Then** Archived is shown.
3. **Given** somebody opens the People list without stating a grouping, **When** the remembered view
   is Favourites sorted by Last spoke, **Then** the address they end up on states Favourites, Last
   spoke and the direction, so that it can be copied.

---

### Edge Cases

- **A recorded choice the product no longer recognises.** A grouping, sort field or direction that has
  since been withdrawn falls back to the default for that one choice, and the other two are kept. The
  screen renders; it never fails.
- **Somebody who has recorded nothing.** Every existing user is in this state on the day the feature
  ships, and sees the default view.
- **A search is running.** The search text narrows the list for that visit only. Returning later shows
  the remembered grouping, sort and direction with nothing searched.
- **Two tabs disagree.** The view rendered last is the one remembered. Neither tab is disturbed while
  it is open.
- **Loading more of a long list.** Asking for the next page keeps the grouping, the sort, the direction
  and any running search.
- **A vault holding nobody.** The remembered grouping is still applied, and the empty state is shown
  for it.

## Requirements *(mandatory)*

### Functional Requirements

**Addressing**

- **FR-001**: Each of the four groupings (Everybody, Favourites, Archived, Names only) MUST have its
  own address, stated in the path rather than as a query parameter.
- **FR-002**: Following the address of any grouping MUST render that grouping. No grouping may lead to
  a page that cannot be found.
- **FR-003**: A contact MUST NOT be reachable at a spelling any grouping uses, so that neither the
  grouping nor the contact becomes unreachable.
- **FR-004**: The sort field and the sort direction MUST stay stated as query parameters on those
  addresses.
- **FR-005**: After somebody opens the People list without stating a grouping, the address they end up
  on MUST state the grouping, sort field and direction being shown, so that it can be copied and
  shared.

**Remembering**

- **FR-006**: The system MUST record, for each user, the grouping, the sort field and the sort
  direction of the People list they last viewed.
- **FR-007**: The recording MUST happen without any explicit save action, control or confirmation.
- **FR-008**: Opening the People list without stating a grouping MUST show the recorded view.
- **FR-009**: Opening an address that states a grouping MUST show that grouping whatever is recorded,
  and MUST make it the recorded grouping.
- **FR-010**: A user who has recorded nothing MUST see Everybody, sorted by Name, A to Z.
- **FR-011**: The recorded view MUST apply to the People list of every vault the user reaches.
- **FR-012**: A recorded value the system no longer recognises MUST fall back to the default for that
  choice, leaving the other two choices intact.
- **FR-013**: The search text MUST NOT be recorded.
- **FR-014**: The recorded view MUST belong to the user and MUST NOT be visible to, or altered by,
  anybody else.

**Reach**

- **FR-015**: The recorded view MUST be readable and writable through the API, as every capability in
  the interface is.
- **FR-016**: Changing the recorded view MUST NOT alter anything else the user has already fixed.

### Key Entities

- **Contact list view preference**: the grouping, sort field and sort direction one user last viewed
  the People list with. One per user, independent of vault. Absent until the user first views the
  list.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody who last viewed Favourites sorted by Last spoke, newest first, finds exactly
  that view on their next visit to the People list, in a new session and in any of their vaults.
- **SC-002**: All four groupings render their own list when their address is opened directly. None
  fails.
- **SC-003**: The address on screen after landing on the People list reproduces the same grouping,
  sort field and direction when opened elsewhere by the same person.
- **SC-004**: Setting up a view takes zero extra steps: there is no save control, and no confirmation
  is shown.
- **SC-005**: Somebody who has never chosen a view sees Everybody, sorted by Name, A to Z, on their
  first visit.
- **SC-006**: Everything the interface can do to the recorded view can also be done through the API.

## Assumptions

- **One view per user, not one per vault.** The request says "at user level", and the follow-up it
  names is a single default in settings. Somebody with a family vault and a work vault gets the same
  grouping in both. Making it per vault is a deliberate non-goal here and can be revisited.
- **Everybody gains an address of its own.** Today Everybody is the address with no grouping stated.
  That spelling is now needed to mean "open my remembered view", otherwise the Everybody chip leads
  back to whatever was remembered and the grouping can never be left.
- **The bare People address resolves to the remembered view's address.** The person ends up on an
  address that states what they are looking at, which is what FR-005 asks for and what makes the view
  shareable.
- **Search is a narrowing, not a view.** It is one of the four controls on that bar, and it is the one
  not recorded: a saved search would hide people on every later visit without the person having asked
  for it.
- **Sort and direction stay query parameters.** The request asks for the groupings to be addresses. A
  grouping is one of four named views; a sort is a modifier of whichever view is open, and putting it
  in the path would give one screen sixteen addresses for no gain.
- **The recorded view is written when the list is rendered**, which is the only moment the product
  knows what somebody is looking at.

## Out of Scope

- The settings screen that pins a default view, named in the request as a later step. It changes what
  an absent recording falls back to, and nothing in this feature forecloses it.
- Remembering a view on any other list in the product.
- Remembering the search text, the page reached, or the width of the window.
