# Feature Specification: Copy a New API Key to the Clipboard

**Feature Branch**: `2026-09-02-apikey-clipboard`

**Created**: 2026-09-02

**Status**: Draft

**Input**: User description: "copy a new api key to the clipboard"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Take the key away in one gesture (Priority: P1)

Somebody has just made an API key. The screen prints it once, says it will never print it again, and
leaves them to select it by hand. A control beside the key puts the whole value on the clipboard in
one activation and says that it did, so the key reaches the tool it was made for without a selection
that can clip a character or miss the end of a wrapped line.

**Why this priority**: the value exists for one screen only. A copy that goes wrong costs a key,
because the key cannot be shown again and has to be made and named a second time.

**Independent Test**: make a key, activate the control, paste elsewhere, and confirm the pasted text
matches the printed value exactly. Valuable on its own, with the rest of the screen untouched.

**Acceptance Scenarios**:

1. **Given** a key has just been made and is on screen, **When** the copy control is activated,
   **Then** the clipboard holds the complete value and the screen confirms the copy.
2. **Given** a copy has just been confirmed, **When** a moment passes, **Then** the confirmation
   goes and the control offers to copy again, with the key still printed and still selectable.
3. **Given** the key is on screen, **When** it is clicked, **Then** the whole value is selected,
   ready to be taken by hand without dragging across it.

---

### User Story 2 - Be told when the clipboard was out of reach (Priority: P2)

A self hosted instance reached over plain http, or a browser that refuses the write, gives no
clipboard at all. The screen must say the copy did not happen rather than claim it did, because
somebody who trusts a false confirmation leaves the screen with nothing and loses the key.

**Why this priority**: it is the failure that costs the key, and the product already has copy
controls elsewhere that assume success. This one must not.

**Independent Test**: reach the screen over plain http, or deny the browser permission, activate the
control, and confirm the screen reports a failure and the key is still there to select by hand.

**Acceptance Scenarios**:

1. **Given** the browser exposes no clipboard, **When** the control is activated, **Then** the
   screen says the copy did not happen and tells somebody to copy the key by hand.
2. **Given** the browser refuses the write, **When** the control is activated, **Then** no
   confirmation is shown and the key stays printed and selectable.

---

### Edge Cases

- The panel exists only after a key is made. Reloading or leaving takes the key and the control.
- Naming a key badly returns a validation failure, with no key, no panel and no copy control.
- The clipboard write is attempted while the page does not have focus.
- The key wraps over several lines, and both a copy and a click still take the whole value.
- The key is copied twice in a row, and the second copy behaves exactly like the first.
- Reduced motion is requested, and the confirmation appears without animation.
- On a phone, or zoomed to 200%, the key and the control neither overlap nor overflow the panel.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A key just made MUST carry a control that copies its complete value in one activation.
- **FR-002**: The copied text MUST be exactly the value shown, with no label and no truncation.
- **FR-003**: The screen MUST confirm a copy that happened. The confirmation MUST be temporary and
  MUST NOT hide, replace or shorten the printed key.
- **FR-004**: The screen MUST report a copy that did not happen, MUST NOT confirm one instead, and
  MUST tell somebody to take the key by hand.
- **FR-005**: The control MUST work on an instance served over plain http, where the browser offers
  no clipboard interface of its own.
- **FR-006**: The key MUST be shown in a box of its own, set apart from the copy around it.
- **FR-007**: A click on the key MUST select the whole value, not the word under the pointer.
- **FR-008**: The printed key MUST stay visible and selectable before, during and after a copy.
- **FR-009**: The control MUST be reachable and operable with the keyboard alone, with visible focus.
- **FR-010**: Both outcomes MUST be announced to a screen reader, and neither carried by colour alone.
- **FR-011**: A copy MUST NOT send the key anywhere, log it, store it, or alter what was recorded.
- **FR-012**: Every string the control and its outcomes show MUST be translatable and translated in
  every configured locale, following the typography rules of each.
- **FR-013**: The panel MUST stay readable and usable on a phone, a tablet and a desktop, and
  zoomed to 200%, and MUST honour a request for reduced motion.
- **FR-014**: How a key is made, named, hashed and shown exactly once MUST NOT change.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody who has just made a key gets the whole value onto the clipboard with one
  activation and no manual selection.
- **SC-002**: Every copy either confirms or reports a failure. None reports success while the
  clipboard does not hold the value.
- **SC-003**: A pasted key matches the printed key character for character, however it was copied.
- **SC-004**: Naming a key and copying it can be completed with the keyboard alone.
- **SC-005**: Somebody using a screen reader can tell whether the copy happened without looking.
- **SC-006**: No key has to be made a second time because the first one was copied incompletely.

## Assumptions

- Copying is something somebody asks for, not something that happens by itself when the key
  appears. A browser only allows a clipboard write that follows a gesture on the page, and the key
  arrives on a fresh screen where there has been none, so an automatic copy would quietly do
  nothing and the confirmation would be a lie.
- Only a key that has just been made can be copied. An existing key is kept as a hash, so there is
  nothing to put on the clipboard and its row gets no copy control.
- The product already has one way of putting text on the clipboard that reports whether it managed
  and that works where the browser offers nothing. This reuses it rather than adding a second.
- The copy controls elsewhere, on a share link and on a template export, assume success and are
  left alone. They have the same weakness, and fixing them is its own change.
- The key stays on screen after being copied, because hiding it would strand somebody whose paste
  went wrong while the value still existed.
- The wording for copying and for confirming a copy already exists, translated in every locale, and
  is reused rather than invented again.
