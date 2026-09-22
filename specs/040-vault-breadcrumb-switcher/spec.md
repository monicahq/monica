# Feature Specification: Vault switcher in the breadcrumb

**Feature Branch**: `2026-breadcrimb`

**Created**: 2026-09-21

**Status**: Draft

**Input**: User description: "i want to design a new breadcrumb when inside a vault. specs are as follow: Use the claude_design MCP to import this project: https://claude.ai/design/p/760fa78b-56ec-471a-b447-a873048992af?file=Breadcrumb+vault+switcher.dc.html — Implement `Breadcrumb vault switcher.dc.html`. also, remove the vault seletor in the sidebar since we do not need it if we have this new breadcrumb."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Move to another vault from where you already are (Priority: P1)

Somebody is reading a contact inside their Personal vault and wants the same kind of screen in
their Work contacts vault. The first crumb of the trail at the top of the screen names the vault
they are in. They activate it, a short list of their vaults opens under it, they pick one, and they
land on that vault's dashboard.

**Why this priority**: this is the whole point of the change. The way into another vault moves from
the side column to the trail, where the reader is already looking to know where they are.

**Independent Test**: sign in with an account holding several vaults, open any screen inside one,
activate the first crumb, pick a different vault, and confirm the screen that loads belongs to the
vault that was picked.

**Acceptance Scenarios**:

1. **Given** an account with three vaults and a person on a screen inside one of them, **When** they
   activate the first crumb, **Then** a list of all three vaults opens, each named, and the one they
   are in is marked as current by a glyph as well as by its state.
2. **Given** that list is open, **When** they pick a vault other than the current one, **Then** they
   arrive on that vault's dashboard.
3. **Given** that list is open, **When** they pick the vault they are already in, **Then** the list
   closes and the screen does not change.
4. **Given** that list is open, **When** they press Escape or activate anything outside it, **Then**
   the list closes and focus returns to the crumb that opened it.
5. **Given** a person using the keyboard alone, **When** they reach the first crumb and activate it,
   **Then** they can walk the list, pick an entry and close it without a pointer.

---

### User Story 2 - Read where you are without the control getting in the way (Priority: P1)

The trail still has to say where the reader is. The vault crumb carries a colour tile with the
vault's initial and the vault's name; the crumbs after it stay plain, and the last one is the screen
they are on.

**Why this priority**: the trail is a wayfinding element before it is a control. If turning the
first crumb into a button makes the trail harder to read, the change is a loss.

**Independent Test**: open a screen several levels deep inside a vault and confirm the trail reads
from the vault to the current screen, that only the first crumb is activable as a control, and that
the current screen is announced as the current page.

**Acceptance Scenarios**:

1. **Given** a screen inside a vault, **When** it renders, **Then** the trail starts at the vault,
   and each following crumb links to the screen it names except the last, which is marked as the
   current page and does not link anywhere.
2. **Given** the trail, **When** a screen reader reads it, **Then** the first crumb is announced as a
   control that opens a menu, and the other crumbs are announced as links or as the current page.
3. **Given** a vault whose name is long, **When** the trail renders on a narrow screen, **Then** the
   trail stays on the screen and no part of it is cut off unreachably.

---

### User Story 3 - An account with a single vault is not offered a choice (Priority: P2)

Most accounts hold one vault. For them the first crumb is not a control: no chevron, no menu, no
hover fill. It is a plain link to the vault's dashboard, exactly like the other crumbs.

**Why this priority**: offering a menu that opens on a list of one is noise, and the design calls
this state out explicitly. It is separable from the switching itself, so it ships after it.

**Independent Test**: sign in with an account holding one vault, open a screen inside it, and
confirm the first crumb is a link with no chevron and nothing to open.

**Acceptance Scenarios**:

1. **Given** an account with exactly one vault, **When** a screen inside it renders, **Then** the
   first crumb shows the tile and the name, carries no chevron, and links to the vault dashboard.
2. **Given** that crumb, **When** somebody activates it, **Then** they arrive on the vault dashboard
   and nothing opens.

---

### User Story 4 - The side column loses the selector it no longer needs (Priority: P2)

The column beside a vault's screens holds a selector at its top showing the current vault, the list
of the others, and a way to the full vault list. Once the trail carries the switch, that block is
removed and the column starts at the list of what the vault holds.

**Why this priority**: two controls doing one job is the defect this change exists to remove, but
the trail has to work first.

**Independent Test**: open any screen inside a vault and confirm the side column no longer shows a
vault selector, while every destination it used to reach is still reachable.

**Acceptance Scenarios**:

1. **Given** a screen inside a vault, **When** the side column renders, **Then** it holds no vault
   selector and no list of other vaults.
2. **Given** the selector is gone, **When** somebody wants the full list of their vaults, **Then**
   they can still reach it from the product name at the top of the column.
3. **Given** the selector is gone, **When** somebody wants to create a vault, **Then** they can still
   reach that from the vault list and from the trail's own menu.

---

### Edge Cases

- **A vault name that does not fit.** The name is shortened in the crumb and in the menu rather than
  pushing the rest of the trail off the screen. The full name stays available to a screen reader.
- **A vault whose name starts with a character that has no uppercase form**, or with an emoji: the
  tile shows the first character as it is written, and carries no meaning a screen reader needs, so
  it is hidden from one.
- **Many vaults.** The menu holds them all and scrolls rather than growing past the screen.
- **A narrow screen.** The trail shares the top bar with the menu button and the bar's own controls.
  The vault crumb stays reachable and operable; the crumbs between it and the current screen may be
  dropped before it is.
- **Somebody switches to a vault while a form on the current screen holds unsaved input.** Switching
  is a plain navigation, the same as any link in the trail: nothing is saved and nothing warns. This
  matches how the side column's selector behaved.
- **A vault is deleted in another tab and then picked from a stale menu.** The reader is told the
  vault is not there, the same as any link to a record that has gone.
- **Reduced motion.** The menu appears and disappears without animation when the system asks for it.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every screen inside a vault MUST show a trail whose first crumb names the vault the
  reader is in, with a colour tile carrying the vault's first character and the vault's name.
- **FR-002**: When the account holds more than one vault, the first crumb MUST be a control that
  opens a list of the account's vaults, and MUST show a chevron as the only sign that it opens
  something.
- **FR-003**: The list MUST show every vault of the account, each with its colour tile, its name and
  the number of people it holds.
- **FR-004**: The list MUST mark the vault the reader is in with a glyph as well as with its
  selected state, so the mark does not depend on colour alone.
- **FR-005**: Picking a vault other than the current one MUST take the reader to that vault's
  dashboard. Picking the current one MUST close the list and change nothing.
- **FR-006**: The list MUST offer a way to create a new vault, below the vaults and separated from
  them.
- **FR-007**: The list MUST close on Escape and on any activation outside it, and focus MUST return
  to the crumb that opened it.
- **FR-008**: The whole control MUST be operable with the keyboard alone and MUST be announced
  correctly by a screen reader: the crumb as a control that opens a menu and reports whether it is
  open, each entry as a choice within that menu reporting whether it is the current one.
- **FR-009**: When the account holds exactly one vault, the first crumb MUST be a plain link to that
  vault's dashboard, with no chevron, no menu and no hover fill.
- **FR-010**: The crumbs after the first MUST stay plain: each links to the screen it names, except
  the last, which MUST be marked as the current page and MUST NOT link anywhere.
- **FR-011**: The side column of a vault's screens MUST NOT show a vault selector, a list of other
  vaults, or a link to the full vault list from that block.
- **FR-012**: Every destination the removed selector reached MUST stay reachable: the vault list from
  the product name at the top of the column, a vault's dashboard from the trail's menu, and the
  creation of a vault from the trail's menu and from the vault list.
- **FR-013**: The trail MUST remain readable and operable down to a 400 pixel wide screen and when
  the page is zoomed to 200%, with no part of it cut off unreachably.
- **FR-014**: All copy the control introduces MUST be translatable and MUST be present in every
  configured locale.
- **FR-015**: The number of people a vault holds MUST be phrased so that each language chooses its
  own wording for the number.

### Key Entities

- **Vault**: a compartment an account is divided into. The trail reads its name, its colour and the
  number of people it holds. Nothing about a vault changes.
- **Account**: holds the vaults. Whether it holds one or several is what decides whether the first
  crumb is a control or a link.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: From any screen inside a vault, somebody can reach another vault in two activations:
  open the crumb, pick the vault.
- **SC-002**: There is exactly one way to switch vault from inside a vault, down from two.
- **SC-003**: Every screen inside a vault passes WCAG 2.2 AA: operable with the keyboard alone,
  announced correctly, contrast compliant, focused visibly, readable at 200% zoom, and still with no
  information carried by colour alone.
- **SC-004**: The side column of a vault screen starts at the list of what the vault holds, giving
  back the vertical space the selector took.
- **SC-005**: Rendering a vault screen costs no more database queries than it did before the change.
- **SC-006**: Every configured locale holds a translation for every string the change introduces, and
  the localization check reports nothing missing.

## Assumptions

- **Picking a vault lands on its dashboard.** The design's mock only redraws the trail, which is not
  a behaviour a real screen can have. Landing on the dashboard is what the side column's selector
  already did, and mapping the current screen onto the other vault is not generally possible.
- **The menu's footer offers creating a vault, and not the full vault list.** The design shows only
  "New vault". The full list stays reachable from the product name at the top of the side column,
  which already links there.
- **The count under each vault name is the number of people in it.** The design writes "18 people",
  and that is the figure the side column's selector already showed for the current vault.
- **Switching vault is a plain navigation.** No warning about unsaved input, matching the control it
  replaces.
- **The trail is unchanged outside vaults.** The settings screens and the account screens keep the
  trail they have; only the vault trail gains the switcher.
- **The vault's colour and first character come from what already exists.** No new field, no
  migration, no change to how a vault is stored.
- **The existing screens that show the trail keep showing it.** This change alters what the first
  crumb is, not which screens carry a trail.
