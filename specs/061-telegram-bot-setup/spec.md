# Feature Specification: Telegram Bot Setup From the Configuration Screen

**Feature Branch**: `2026-09-26-telegram-bot-setup`

**Created**: 2026-09-26

**Status**: Draft

**Input**: User description: "Setup telegram bot from admin config panel"

## Context

The configuration screen of the admin space (spec 036) already lets an administrator enter the three
Telegram values: the bot token, the public address of the bot, and the secret that proves a call came
from Telegram. Entering them is not enough. Telegram only sends the messages people write to the bot
once it has been told where to send them, and today that step exists only as a console command
(`monica:setup-telegram`). An administrator who configures everything from the screen ends up with a
Telegram channel that the account settings offer, that people try to connect, and that never
connects, because nothing ever told Telegram where this instance is.

This feature closes that gap. The administrator finishes the whole Telegram setup from the screen,
sees whether it is done, and is told when a later change has undone it.

## Clarifications

### Session 2026-09-26

- Q: When the administrator saves a new token or secret, should the instance register the bot with Telegram automatically, or only when the administrator presses a separate "Register" button? → A: Separate button only. Saving marks the registration out of date and warns first; nothing calls Telegram until the button is pressed.
- Q: Should the screen work out "registered / out of date" from the product's own record of its last registration, or ask Telegram for its current webhook settings each time the configuration screen opens? → A: Own record, plus a "Check with Telegram" button that asks Telegram only when pressed.
- Q: Should this feature include the two conveniences in User Story 4 (generating the secret on the screen, and filling in the bot address from Telegram after a registration), or leave them for later? → A: Keep both; they ship with this feature.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Register the bot from the screen (Priority: P1)

An administrator has entered the bot token and the secret in the Telegram group of the configuration
screen. The group now offers to connect the bot to this instance. The administrator asks for it,
confirms their identity the way every configuration change already requires, and the screen reports
either that Telegram accepted the registration, or the reason Telegram gave for refusing it.

**Why this priority**: without it, a Telegram channel configured from the screen never connects. It is
the whole reason for the feature, and on its own it removes the need for shell access.

**Independent Test**: configure a token and a secret from the screen, register, then open a
conversation with the bot from an account's notification settings and see the conversation connect.

**Acceptance Scenarios**:

1. **Given** a token and a secret are set and the Telegram integration is installed, **When** the
   administrator registers the bot, **Then** Telegram is told this instance's public address and the
   secret, and the screen says the registration succeeded.
2. **Given** Telegram refuses the registration (a token it does not recognise, an address it cannot
   reach), **When** the administrator registers the bot, **Then** the screen shows Telegram's reason
   in the administrator's language where the product knows it, and Telegram's own wording otherwise,
   and nothing is recorded as registered.
3. **Given** the token or the secret is missing, **When** the administrator views the Telegram group,
   **Then** the registration control is still shown, is not activable, and says which value is
   missing.
4. **Given** the Telegram integration is not installed on the server, **When** the administrator views
   the Telegram group, **Then** the registration control is not activable and says why, in the same
   way the three settings already do.
5. **Given** the administrator has not confirmed their identity recently, **When** they register the
   bot, **Then** they are asked to confirm it first, exactly as for changing a setting.
6. **Given** a registration succeeded, **When** the administrator reads the administrator log,
   **Then** it records who registered the bot, when, and for which address.

---

### User Story 2 - The instance answers Telegram whatever the value came from (Priority: P1)

A token entered on the screen must work exactly as a token supplied by the deployment. Today the
address Telegram calls exists only when a token was known at the moment the server prepared its
routes, so a token entered on the screen afterwards can leave Telegram calling an address that does
not answer.

**Why this priority**: User Story 1 is worthless if the address it registers does not answer. The two
ship together.

**Independent Test**: start from an instance with no Telegram token in its deployment, set one from
the screen, and send a well formed, correctly signed call to the registered address: it is accepted.

**Acceptance Scenarios**:

1. **Given** the token was set on the screen and not by the deployment, **When** Telegram calls the
   registered address with the right secret, **Then** the call is handled as it is today.
2. **Given** no token is configured from either source, **When** anything calls that address,
   **Then** it answers as not found, and no conversation is connected.
3. **Given** a call carries a wrong or missing secret, **When** it reaches the address, **Then** it is
   refused as today, whichever source the secret came from.

---

### User Story 3 - Know whether the bot is still registered (Priority: P2)

The Telegram group shows the state of the setup in one line: not registered yet, registered on a given
date for a given address, or registered but out of date because something it depends on changed
since. A change to the token, the secret, or the public address of the instance makes the last
registration out of date, and the screen says so and offers to register again.

**Why this priority**: changing the secret without registering again breaks every future connection
silently, since Telegram keeps sending the old secret and the instance refuses it. The administrator
must learn this on the screen rather than from a user who cannot connect.

**Independent Test**: register, then change the secret; the group reports the registration as out of
date, and registering again returns it to registered.

**Acceptance Scenarios**:

1. **Given** the bot was never registered from the screen or the console, **When** the administrator
   views the group, **Then** it says the bot is not registered.
2. **Given** a registration succeeded and nothing changed since, **When** the administrator views the
   group, **Then** it says when the bot was registered and for which address.
3. **Given** the administrator is about to change the token or the secret while a registration is
   current, **When** they save, **Then** they are warned first that Telegram will stop reaching the
   instance until the bot is registered again, following the warning the screen already gives before
   a destructive change.
4. **Given** the token, the secret or the instance address changed after the last registration,
   **When** the administrator views the group, **Then** it says the registration is out of date and
   offers to register again.
5. **Given** a token is in effect, **When** the administrator asks the screen to check with Telegram,
   **Then** the screen shows the address Telegram currently sends to, whether it is this instance's
   address, and the last delivery error Telegram reports, if any. Nothing is registered or recorded by
   the check.
6. **Given** Telegram does not answer the check, **When** the bounded wait runs out, **Then** the
   screen says Telegram could not be reached and still shows the state derived from the product's own
   record.

---

### User Story 4 - Fewer values to find by hand (Priority: P3)

Two of the three values are tedious to obtain. The secret is today generated in a shell, and the bot
address has to be typed from the bot's username. The screen generates a strong secret on request, and
fills the bot address from what Telegram reports about the bot when a registration succeeds and no
address was set.

**Why this priority**: User Stories 1 to 3 work without it, but without secret generation an
administrator still needs a shell to make the secret, which SC-001 rules out. It ships with this
feature, after the P1 and P2 stories.

**Independent Test**: on an instance with a token and nothing else, generate a secret, register, and
see the bot address filled in with the bot's own address.

**Acceptance Scenarios**:

1. **Given** no secret is set, **When** the administrator asks the screen to generate one, **Then** a
   random secret of sufficient strength is stored as the Telegram secret and, as for every secret on
   the screen, it is never shown back.
2. **Given** a secret is already set, **When** the administrator asks for a new one, **Then** they are
   warned as in User Story 3, scenario 3, before it replaces the old one.
3. **Given** no bot address is set, **When** a registration succeeds, **Then** the bot address is set
   to the address Telegram reports for the bot, and the change appears in the administrator log.
4. **Given** a bot address is already set, **When** a registration succeeds, **Then** it is left as
   it is, even when it differs from what Telegram reports; the screen points out the difference.

---

### Edge Cases

- The instance is reachable only on a private network or over plain HTTP. Telegram refuses such an
  address, and the screen says so before calling Telegram when the address is obviously unusable
  (not HTTPS, or a local host name), rather than waiting for Telegram's refusal.
- Telegram does not answer, or answers after a long delay. The registration fails with a message
  saying Telegram could not be reached, within a bounded wait, and the screen stays usable.
- Two administrators register at the same time. The later registration is the one Telegram keeps, and
  both are logged.
- The deployment later supplies different values than the ones stored on the screen. The stored values
  keep winning, as spec 036 already decides, and the registration state reflects the values actually in
  use.
- The bot was registered with another instance or another address by hand. The screen cannot see
  registrations it did not make until the administrator checks with Telegram (FR-016); registering
  from the screen replaces whatever Telegram held.
- The console command is run instead of the screen. It performs the same registration, records it the
  same way, and the screen reflects it.
- The token is removed from the screen after a registration. Telegram keeps calling an address that
  now answers as not found; the group states that the bot is no longer configured.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The Telegram group of the configuration screen MUST offer an administrator a control to
  register the bot with Telegram, using the token and the secret currently in effect, whichever source
  supplies them.
- **FR-002**: Registration MUST send Telegram this instance's public address for incoming Telegram
  calls and the secret, and MUST report the outcome on the screen: success, or the reason for refusal.
- **FR-003**: Registration MUST require the same identity confirmation the configuration screen already
  requires before any change.
- **FR-004**: When registration cannot be attempted (integration not installed, token missing, secret
  missing), the control MUST stay on the screen, be marked unavailable, and name the reason.
- **FR-005**: The address Telegram calls MUST answer whenever a token is in effect, whether that token
  came from the deployment or from the screen, and MUST answer as not found when none is.
- **FR-006**: The console command MUST perform the same registration as the screen, so both record the
  same outcome and report the same errors.
- **FR-007**: Every successful registration MUST be recorded in the administrator log with who
  performed it (or that it came from the console), when, and the address registered.
- **FR-008**: The product MUST keep enough about the last successful registration (when, which
  address, and a fingerprint of the token and the secret, never the values themselves) to tell whether
  it is still current.
- **FR-009**: The Telegram group MUST show one of three states: not registered, registered (with date
  and address), or out of date (with the reason: token, secret or instance address changed).
- **FR-010**: Before saving a new token or secret while a registration is current, the screen MUST warn
  the administrator that Telegram stops reaching the instance until the bot is registered again.
  Saving a setting MUST NOT call Telegram; only the registration control does.
- **FR-011**: The screen MUST let the administrator generate a random secret of at least 256 bits and
  store it as the Telegram secret, without ever displaying it.
- **FR-012**: When a registration succeeds and no bot address is set, the product MUST set the bot
  address from what Telegram reports about the bot, and record that change in the administrator log.
- **FR-013**: Before calling Telegram, the product MUST refuse, with a clear message, an instance
  address that is not HTTPS or that names a local host.
- **FR-014**: A call to Telegram MUST give up after a bounded wait and report that Telegram could not
  be reached.
- **FR-015**: All new copy MUST be translated in every configured locale, and every new control MUST
  meet the accessibility rules of the rest of the screen.
- **FR-016**: The Telegram group MUST offer a control to check with Telegram, available whenever a
  token is in effect. It MUST call Telegram only when pressed, MUST NOT change any setting or the
  registration record, and MUST show the address Telegram sends to, whether it matches this instance,
  and Telegram's last reported delivery error. Telegram does not reveal the secret it holds, so the
  check cannot confirm the secret; the screen says so.

### Key Entities

- **Telegram registration**: the record of the last successful registration of the bot for this
  instance. Holds when it happened, the address registered, who or what performed it, and a fingerprint
  of the token and the secret in effect at the time. There is at most one.
- **Telegram settings**: the three existing instance settings (token, bot address, secret) from spec
  036. Unchanged in nature; this feature adds actions around them.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An administrator with no shell access can take an instance from no Telegram values to a
  conversation connected from an account's notification settings, using only the screen, in under five
  minutes.
- **SC-002**: A registration attempt reports its outcome in under 15 seconds, including when Telegram
  does not answer.
- **SC-003**: In every state where the Telegram channel is offered to accounts but cannot connect
  because of the instance setup, the configuration screen names that state.
- **SC-004**: Zero secret or token values appear on the screen, in the administrator log, or in the
  record of the last registration.

## Assumptions

- Registration is an explicit action the administrator takes, not a side effect of saving a setting.
  Saving the token or the secret marks the registration out of date and warns, but does not call
  Telegram on its own. This keeps a save from depending on an outside service and makes the call
  Telegram receives something an administrator chose. Settled in the clarification session of
  2026-09-26.
- The screen does not ask Telegram for its current webhook state when it opens. The state shown is
  derived from the last registration the product made. A registration made by hand elsewhere is only
  visible through the check control of FR-016, pressed on demand.
- Removing the registration from Telegram (unregistering the bot) is out of scope. Clearing the token
  already stops the instance from accepting calls.
- The configuration screen has no JSON API; spec 036 removed it on the author's instruction.
  Registration follows the screen and the console command, and is not added to the API. This departs
  from principle X of the constitution in the same way spec 036 already does.
- Only an administrator of the instance can reach this, as for the rest of the admin space.
- The Telegram integration package remains an optional installation. When absent, everything in this
  feature is shown as unavailable with the reason, never hidden.
- Account holders connect their conversation exactly as today (spec 026); nothing changes on the
  account notification settings screen.
