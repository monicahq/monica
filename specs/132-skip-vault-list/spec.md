# Feature Specification: Skip the list of vaults

**Feature Branch**: `2026-11-18-public-changelog`

**Created**: 2026-11-18

**Status**: Draft

**Input**: User description: "some users will only have one vault, and just want the defualt vault. in the settings page of the acount, above Billing, create a new menu item called Preferences. In this screen, we can toggle on off (we have this component on the notifications settings screen in https://monica.test/settings/account/notifications) the presence of the dashboard - if off, we will load the default view, and we will not be able to delete the vault (button in the ui should be greyed out with a text explaining clearly to the user why - and through the api/mcp we should reject vault deletion). also, when we land on /vaults, it should arrive directly on the default vault. add proper documentation for this in the https://monica.test/docs/en."

## Vocabulary

- **List of vaults**: the screen at `/vaults` showing every vault of the account as a card. The request calls it "the dashboard". This spec does not use that word, because each vault already has a screen of its own that the code calls its dashboard (the screen at `/vaults/{vault}`).
- **Default vault**: the one vault of the account marked as default. An account with at least one vault always has exactly one.
- **Show the list of vaults**: the new preference this feature adds. On by default.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Go straight to the default vault (Priority: P1)

Michael keeps everything in one vault. Every time he signs in or clicks the way back to his vaults, he lands on a list holding one card and has to click it. He opens Preferences in the settings, finds the switch for the list of vaults, and turns it off. From then on, everything that would have shown him the list of vaults takes him straight into his default vault.

**Why this priority**: It is the reason for the feature. Without it the switch does nothing.

**Independent Test**: Turn the setting off, then open `/vaults`, sign in again, and leave the settings: each lands on the default vault.

**Acceptance Scenarios**:

1. **Given** the setting is on, **When** Michael turns the switch off, **Then** the setting is saved at once and the screen confirms it, as the notification switches do.
2. **Given** the setting is off and the account has a default vault, **When** Michael opens `/vaults`, **Then** he is redirected to the default vault's own screen.
3. **Given** the setting is off, **When** Michael signs in, or follows any link back to his vaults (the way out of the settings, the vault breadcrumb), **Then** he lands on the default vault.
4. **Given** the setting is off, **When** Michael turns it back on, **Then** `/vaults` shows the list of vaults again.
5. **Given** the setting is off and the account has no vault at all, **When** Michael opens `/vaults`, **Then** he sees the list of vaults with its empty state, so he can create his first vault.

---

### User Story 2 - The default vault cannot be deleted while the list is skipped (Priority: P1)

While the list of vaults is skipped, the default vault is where the application sends Michael. Deleting it would leave him with nowhere to land, so it is refused. On the vault's settings screen, the delete control stays visible but disabled, with a sentence saying why and what to do: turn the list of vaults back on in the settings.

**Why this priority**: It protects the account from a state the first story cannot handle, and the request names it explicitly.

**Independent Test**: With the setting off, open the default vault's settings and try to delete it through the screen and through the API; both refuse with the same reason.

**Acceptance Scenarios**:

1. **Given** the setting is off, **When** Michael opens the settings of his default vault, **Then** the delete control is shown disabled, announced as disabled to assistive technology, and described by a sentence explaining that the default vault cannot be deleted while the list of vaults is turned off, with a link to the setting.
2. **Given** the setting is off, **When** a request to delete the default vault reaches the application anyway (a crafted form, the API), **Then** it is refused, nothing is deleted, and the reason is the same sentence.
3. **Given** the setting is off, **When** Michael deletes a vault that is not the default one, **Then** the deletion proceeds as it does today.
4. **Given** the setting is on, **When** Michael deletes the default vault, **Then** the deletion proceeds as it does today, including the promotion of another vault to default.

---

### User Story 3 - Read how it works in the documentation (Priority: P2)

Somebody wondering why they land straight in a vault, or why they cannot delete it, finds the answer in the help centre.

**Why this priority**: The request asks for it, and the refusal sentence links to it, but the behaviour works without it.
**Independent Test**: Open the English documentation and find the setting described, with the refusal and how to lift it.

**Acceptance Scenarios**:

1. **Given** the English documentation, **When** a reader opens the page about the setting, **Then** it explains what the setting does, where to find it, what happens on `/vaults`, and why the default vault cannot be deleted while it is off.
2. **Given** the page about deleting a vault, **When** a reader reads it, **Then** it mentions the refusal and points to the setting.

---

### Edge Cases

- **No vault at all** while the setting is off: there is nowhere to redirect, so the list of vaults is shown with its empty state (scenario 1.5).
- **Several vaults** while the setting is off: allowed. The person lands on the default vault and reaches the others through the vault switcher. Only the default vault is protected from deletion.
- **Changing which vault is the default** while the setting is off: allowed. The protection follows the default mark.
- **Creating a vault** while the setting is off: still possible from the vault switcher, which offers "New vault".
- **Self hosted instances**: the setting exists there too, since it lives on the Preferences screen, which every instance shows.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Each person MUST have a preference "Show the list of vaults", on by default for every existing and new person.
- **FR-002**: The existing Preferences screen (settings, group "You") MUST gain a section holding a switch for this setting, the same switch control the notification settings screen uses, saving at once. No new entry is added to the settings menu, so the setting is available on self hosted instances too.
- **FR-003**: While the setting is off and the account has a default vault, every request for the list of vaults MUST redirect to the default vault's own screen. This covers signing in and every link that leads to the list.
- **FR-004**: While the setting is off and the account has no vault, the list of vaults MUST be shown as it is today.
- **FR-005**: While the setting is off, deleting the default vault MUST be refused everywhere it can be requested: the web screen, the API, and any assistant tool that deletes a vault. The refusal MUST carry one sentence, the same on every surface, saying why and how to lift it.
- **FR-006**: The API MUST answer a refused deletion with a validation style error (422) carrying that sentence, and delete nothing.
- **FR-007**: On the default vault's settings screen, while the setting is off, the delete control MUST stay on the screen, be marked as disabled to assistive technology, not be activable, and be described by the refusal sentence, which links to the setting.
- **FR-008**: Deleting a vault that is not the default one, and deleting any vault while the setting is on, MUST behave exactly as today.
- **FR-009**: Turning the setting on or off MUST be written in the account's log.
- **FR-010**: The setting MUST be readable and writable through the API, as every preference a person can change on screen is.
- **FR-011**: The English documentation MUST describe the setting, its effect on `/vaults`, and the deletion refusal; the page on deleting a vault MUST mention the refusal.
- **FR-012**: Every new phrase MUST be translated into every shipped locale.

### Key Entities

- **Preference "Show the list of vaults"**: a yes or no value held by the person, beside their other preferences. On by default.
- **Vault**: existing. Its default mark decides where the person lands and which vault is protected.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: With the setting off, a person with one vault reaches their contacts in one fewer click after signing in than today.
- **SC-002**: With the setting off, 100% of attempts to delete the default vault are refused, on the screen and through the API, and none deletes data.
- **SC-003**: The disabled delete control tells a screen reader user why it is disabled, without them having to activate it.
- **SC-004**: A person who cannot delete their vault finds the reason and the way to lift it from the screen itself, without leaving it to search.

## Assumptions

- "The dashboard" in the request means the list of vaults at `/vaults`, not a vault's own screen, because the request's second sentence names `/vaults`.
- "Load the default view" means the default vault's own screen.
- "We will not be able to delete the vault" means the default vault only. Other vaults stay deletable, since deleting them does not remove the place the person lands.
- The setting is held by the person, like every other value on the Preferences screen. An account holds exactly one person, so it is also the account's.
- The setting is a product preference, not a flag: it ships on for everybody and is never tied to a plan.
- No MCP tool deletes a vault today, so the MCP part of the request is met by the API refusal any future tool would inherit.

## Clarifications

### Session 2026-11-18

- Q: A "Preferences" entry already exists in the settings menu. Where does the switch go? → A: In a new section of the existing Preferences screen. No second "Preferences" entry, nothing added above Billing.
