# Feature Specification: Remove a Telegram or text message setup, and ask for another code

**Feature Branch**: `083-notification-channel-removal`
**Created**: 2026-10-25
**Status**: Draft
**Input**: "Add the ability to delete settings about telegram or sms notifications", then "Ajoute également la possibilité de redemander un code sms"

## Context

Once Telegram or a text message number is set up, the settings screen offers no way to remove it: a person who changes phone can only switch the channel off, and the number or conversation stays stored.
A reset exists, offered only on a ready text message channel as "Cancel this connection" with no warning; no test covers it, the API has none, it leaves a started Telegram connection able to complete, and the journal records it as a switch off.
Once a text message code is sent, the screen shows only the field to type it back: an expired or overtried code says "Ask for another one" and offers no way to, and nothing enforces a wait between two sends.

**Out of scope**: the in app and email channels, which hold nothing to remove; the instance's bot and text message provider; notifications already delivered.

## Clarifications

### Session 2026-10-25

- Q: While text messages are withdrawn for an account, can the account still remove the kept number? → A: Yes, from the notice saying it is kept, behind the same warning. Every other operation on the channel stays not found.
- Q: How long must the account wait between two sends of a code? → A: One minute by default, set by the instance like the other code limits.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Remove a text message number (Priority: P1)

Michael changes phone. He removes the text message setup, confirms the warning, and his number is erased; the channel shows as not set up.
**Why this priority**: a stored number nobody can remove keeps reminders going to whoever holds it now.
**Independent Test**: prove a number, remove it, check the number is gone, the channel is off and not set up, and nothing more is sent to it.

1. **Given** a proven, switched on channel, **When** the account removes its setup and confirms, **Then** the number, proof and pending code are erased, the channel is off, and the screen shows it as not set up.
2. **Given** a code waiting to be typed back, **When** the setup is removed, **Then** that code is no longer accepted.
3. **Given** the warning is shown, **When** the account cancels, **Then** nothing changes.
4. **Given** a removed setup, **When** the account sets up text messages again, **Then** it types and proves a number from the start.

### User Story 2 - Disconnect Telegram (Priority: P1)

Pam removes the Telegram setup from her old phone; the conversation is forgotten and the connect button is back.
**Why this priority**: the same need for the other channel the account sets up itself.
**Independent Test**: connect, remove, check the conversation is forgotten, the channel is off, and nothing more reaches it.

1. **Given** a connected channel, **When** the setup is removed and confirmed, **Then** the conversation is forgotten, the channel is off, and the screen offers to connect again.
2. **Given** a connection started and not finished, **When** the setup is removed, **Then** a later message to the bot quoting it attaches nothing to the account.

### User Story 3 - Ask for another text message code (Priority: P1)

Jim's code expired. The screen offers to send another to the same number; he asks, types the new code, and the number is proven.
**Why this priority**: without it, a person with an expired or overtried code is stuck on the screen.
**Independent Test**: let a code expire, ask for another, check the new code proves the number and the old one is refused.

1. **Given** a waiting code and the wait passed, **When** the account asks for another, **Then** a new code goes to the same number and the screen says so.
2. **Given** a new code was sent, **When** the previous code is typed, **Then** it is refused.
3. **Given** the last send is within the wait, **When** the screen shows, **Then** the control is visible, not usable, and says how long is left.
4. **Given** every code allowed for the setup has been sent, **When** the screen shows, **Then** the control is visible, not usable, and says no more codes will be sent and that removing the setup lets a number be typed again.
5. **Given** an expired or overtried code, **When** another is sent, **Then** it has a full lifetime and a full set of tries.

### User Story 4 - Use both through the API (Priority: P2)

Dwight's native application removes a setup and asks for another code through the API, with the same outcome as the screen.
**Why this priority**: the constitution makes API coverage part of done; the screen is where people do this today.
**Independent Test**: call each operation through the API and compare with the screen.

1. **Given** a set up Telegram or text message channel, **When** the API removes its setup, **Then** it answers with the channel not set up.
2. **Given** an in app or email channel, **When** the API is asked to remove its setup, **Then** it refuses, saying the channel has nothing to remove.

### User Story 5 - Remove a number kept while text messages are withdrawn (Priority: P3)

Angela's account no longer offers text messages and the screen says her number is kept. She removes it from that notice.
**Why this priority**: few accounts are in this state, but in it the number cannot be reached at all.
**Independent Test**: withdraw text messages from an account holding a number, remove it from the notice, check it is erased and the notice is gone.

1. **Given** text messages withdrawn and a number kept, **When** the account removes it and confirms, **Then** the number is erased and the notice disappears.
2. **Given** text messages withdrawn, **When** any other operation on the channel is attempted, **Then** it is not found, as today.

### Edge Cases

- A channel never set up offers no removal; a request to remove it changes nothing and writes no journal entry.
- A channel the product switched off after repeated failures can be removed; its reason is cleared with it.
- A notification written but not yet delivered when the setup is removed is never delivered, and not moved to another channel.
- A channel not available on this server can still have a kept setup removed.
- Removal sends nothing to the old destination.
- Removing the setup and typing the same number again restarts the count of codes, as changing the number already does.
- Two requests for another code within the wait (two tabs, a double click) send at most one.
- A request for another code with none waiting, or on a proven number, is refused and sends nothing.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The screen MUST offer a removal on the Telegram and text message channels whenever one holds a destination, a code in progress or a connection in progress.
- **FR-002**: The removal MUST warn first, naming the channel and, for a text message, the number, and saying it cannot be undone.
- **FR-003**: Removing MUST erase the destination, any code and its counters, any connection in progress, the proof date, the failure count and the disabled reason, and switch the channel off.
- **FR-004**: Removing MUST keep the channel listed and able to be set up from the start.
- **FR-005**: After a removal, a code or connection in progress MUST no longer complete, and nothing MUST be delivered to the erased destination.
- **FR-006**: In app and email MUST NOT offer a removal, and a request to remove theirs MUST be refused with "nothing to remove".
- **FR-007**: A removal MUST be journalled as the setup of the named channel being removed, distinct from a switch off, without the erased value.
- **FR-008**: While text messages are withdrawn, the notice saying the number is kept MUST offer its removal; every other operation stays not found.
- **FR-009**: While a code is waiting, the screen MUST offer to send another code to the same number, next to the code field.
- **FR-010**: Another code MUST replace the previous one and start with a full lifetime and a full set of tries.
- **FR-011**: Two sends to one setup MUST be at least the instance's wait apart, one minute by default; a request inside it is refused and sends nothing.
- **FR-012**: Every send MUST count towards the existing limit of codes per setup.
- **FR-013**: When another code cannot be sent, the control MUST stay visible, not usable, with its reason: the time left, or that no more codes will be sent.
- **FR-014**: Removing a setup and asking for another code MUST be available through the API with the same outcomes and refusals, and documented.
- **FR-015**: Every new phrase MUST be translated in every configured locale.

## Success Criteria *(mandatory)*

- **SC-001**: A setup is removed from the screen in two actions: the removal and its confirmation.
- **SC-002**: After a removal, no stored data holds the removed number or conversation.
- **SC-003**: After a removal, zero messages reach the removed destination, including those waiting.
- **SC-004**: An expired or overtried code is replaced and the number proven in two actions, without retyping the number.
- **SC-005**: No setup receives more codes than the instance allows, nor two within the wait.
- **SC-006**: Every operation on the screen gives the same result through the API, and no journal entry reveals a number or a code.

## Assumptions

- Every account holds one entry per channel and the screen lists only channels with an entry, so removing a setup clears what the account supplied and keeps the channel.
- Delivered notifications and existing journal entries hold no destination, so nothing already written needs erasing.
- The existing code limits stay: 10 minutes per code, 5 tries per code, 3 codes per setup. The last send time is already stored and unread.
- Asking for another code concerns text messages only; Telegram proves itself by working.
