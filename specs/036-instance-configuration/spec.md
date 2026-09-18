# Feature Specification: Instance Configuration

**Feature Branch**: `036-instance-configuration` | **Created**: 2026-09-17 | **Status**: Draft

**Note**: the JSON API surface and the outbound service tests this specification once described were removed on the author's instruction after implementation. What follows is what the screen does.

**Input**: User description: "Configure the application from administration space"

## Clarifications

### Session 2026-09-17

- Q: When a setting holds both an environment value and one saved from the screen, which governs? → A: The stored one. The environment supplies the value until somebody changes it, reverting hands authority back, and the screen says so wherever the two disagree.
- Q: Are the instance's own outbound service credentials configurable here? → A: All of them, stored encrypted and never shown back. (Answered before the payment provider's credentials and the pre-save service tests were later removed on the author's instruction.)
- Q: How quickly must a saved change reach processes other than the one that saved it? → A: Any web request after the save obeys it; long-lived processes (queue workers, the scheduler) within one minute.
- Q: Must saving a change prove a credential again? → A: Yes, every write on this screen, reusing the existing sensitive action step and the window its proof already lasts.

## User Scenarios & Testing *(mandatory)*

What governs the instance (what a free account gets, whether a link can sign somebody in, which outside providers it offers, how mail goes out) is fixed at deployment and changeable only by editing a file on the server and restarting it. This gives the administrator a screen for those decisions and a record of who changed what. It configures the instance, never an account's data, and reaches no contact, field value or file.

### User Story 1 - Change a setting from the screen (Priority: P1)

An administrator opens Configuration in the admin space, sees the settings grouped by what they govern, changes one, saves, and the instance behaves differently from the next request onward without anybody restarting anything.

**Why this priority**: it is the whole feature, and on its own it removes the need for shell access to run the instance.

**Independent Test**: lower the free contact limit from the screen, then try to exceed it as an ordinary user and confirm the new limit is the one enforced.

**Acceptance Scenarios**:

1. **Given** an administrator on the screen, **When** they change a value and save, **Then** the new value is confirmed on screen and governs the next request.
2. **Given** a value the setting cannot take (a negative count, text where a number belongs, an address that is not one), **When** they save, **Then** it is refused with the reason beside the control and nothing is stored.
3. **Given** a saved change, **When** an administrator opens the administrator record, **Then** it is listed with who made it, which setting, the value before and the value after.
4. **Given** a visitor, a regular user or a deactivated administrator, **When** they open the screen or submit to it, **Then** they are answered as if it did not exist.
5. **Given** an administrator who has not proved a credential recently, **When** they save, **Then** they are asked for their password or passkey first, and a second save inside the window asks nothing.

---

### User Story 2 - See where a value comes from and put it back (Priority: P2)

Each setting shows its value, one sentence on what it governs, and whether it still holds the value the product ships with. A setting somebody changed can be put back to that value.

**Why this priority**: without it nobody can tell a deliberate decision from a default, and a wrong change has no way back short of the terminal this feature exists to avoid.

**Independent Test**: change a setting, confirm the screen marks it as changed and names the shipped value, revert it, and confirm the mark and the behaviour both return.

**Acceptance Scenarios**:

1. **Given** a setting nobody has changed, **When** an administrator reads it, **Then** it is shown as holding the shipped value and offers nothing to revert.
2. **Given** a changed setting, **When** an administrator reverts it, **Then** the shipped value governs again and the reversion is recorded like any other change.
3. **Given** a setting whose stored value differs from the environment's, **When** an administrator reads it, **Then** the screen says the environment value is no longer the one obeyed and names both.

### Edge Cases

- A limit is lowered below what existing accounts already hold: nothing granted is taken back, and the new limit governs what is created afterwards.
- Two administrators save the same setting at once: the last write stands and both appear in the record, so the sequence is readable.
- A request already in flight finishes under the value it started with.
- A capability is switched off while something it issued is outstanding (an unexpired sign in link): that thing stops working and says the instance no longer offers it, rather than failing silently.
- A setting governs a channel whose supporting package is absent: it is shown, marked unavailable on this server, and cannot be switched on.
- A redeployment changes an environment variable for a setting somebody had already changed from the screen: the stored value still governs, and the screen says the new environment value is being ignored.
- A release drops a setting somebody had changed: the stored value is kept and shown as no longer applying, with the screen saying the product no longer carries it.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The admin space MUST offer a configuration screen, reachable only by an administrator, listing every setting an operator may change, grouped by what it governs.
- **FR-002**: Each setting MUST show its current value, one sentence saying what it governs, the value the product ships with, and whether the two differ.
- **FR-003**: A change MUST govern every web request after it is saved, and MUST reach long-lived processes (queue workers, the scheduler) within one minute, without a restart, a deployment or a command.
- **FR-004**: A value the setting cannot take MUST be refused with its reason beside the control, and MUST leave the stored value untouched.
- **FR-005**: An administrator MUST be able to revert a setting to the value the product ships with.
- **FR-006**: Every change and every reversion MUST be recorded in the administrator record with the administrator, the setting, the value before, the value after and the moment. For a secret the record MUST hold that it changed and never what it changed to.
- **FR-007**: A setting the product declares as fixed at deployment (whether the instance is self hosted) MUST be shown read only, naming where its value comes from.
- **FR-008**: A change MUST NOT retroactively alter anything already fixed under the previous value.
- **FR-009**: Infrastructure choices (database, queue, cache, storage driver, application key) MUST NOT appear on this screen. The instance's own outbound service credentials (mail transport, text message provider, chat provider) and those of each outside sign in provider MUST, and are the only secrets that may.
- **FR-010**: A secret MUST be stored encrypted, MUST NOT be shown back on the screen, and MUST be reported only as set or unset. Replacing one MUST NOT require reading it.
- **FR-011**: Every save and every reversion MUST require the credential proof the product already asks before a sensitive action, one proof covering the whole window it lasts. Three changes MUST also warn before they are saved, naming what they affect: switching a capability off while something it issued is outstanding, shortening how long data is kept, and clearing a secret, which cannot be read back to restore it.
- **FR-012**: A stored value for a setting the product no longer carries MUST be kept and shown as no longer applying, never deleted. A setting the product renames MUST carry its stored value across.
- **FR-013**: Where a setting holds both an environment value and a stored one, the stored one MUST govern, the screen MUST say the environment value is not the one obeyed, and reverting MUST return authority to the environment.
- **FR-014**: A provider whose supporting package is absent MUST be listed as unavailable on this server, MUST refuse to be switched on, and MUST NOT be named as a delivery driver.

### Key Entities

- **Instance setting**: one operator decision, with a stable name, a current value, a kind saying what values it takes (a secret being one such kind, stored encrypted and never read back), the value the product ships with, and the group it belongs to.
- **Administrator record entry**: the existing record of what an administrator did. It gains a kind of entry naming the setting changed, the value before and the value after. Append only, like every other entry.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An administrator changes any listed setting and sees it take effect on the next page they load, without shell access, a restart or a command, and background work beginning more than a minute later obeys it too.
- **SC-002**: Every setting an operator may change is reachable from one screen, and each names the value the product ships with.
- **SC-003**: 100% of configuration changes are attributable to a named administrator and a moment, verified by a test per setting.
- **SC-004**: A refused value leaves the instance behaving exactly as it did before the attempt, in every case listed under Edge Cases.

## Assumptions

- In scope are the operator decisions the product already names: the account limits (storage, vaults, contacts), the legal page addresses, how long a remembered session lasts, whether a sign in link is offered and for how long, the grace period for a detached file, how far ahead reminder deadlines are generated, the notification attempt, retention and failure numbers, the largest single upload, the API page sizes, and the outbound service configuration of FR-009. Infrastructure is excluded.
- The administrator capacity is the existing instance wide one, guarding the admin route group. No new capacity is introduced, and nothing here reaches inside a vault.
- A limit snapshotted onto an account when it was created (its storage ceiling) stays what it was. Changing the limit governs accounts created afterwards, which is how the product already behaves.
- The screen is a new section of the existing admin space, beside Overview, Moderation, Accounts, Administrators and Record. Exporting or importing the whole configuration as one document is out of scope, as is scheduling a change for later.
