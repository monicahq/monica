# Feature Specification: The Me contact in every new vault

**Feature Branch**: `2026-10-02-vault-owner-contact`

**Created**: 2026-10-02

**Status**: Draft

**Input**: User description: "Implement the automatic creation of the Me contact when a vault is created. Every new vault immediately holds a contact standing for its owner, named after the account at that moment, including the first vault created at sign up. One per vault. It stays editable and does not follow later changes to the account name. It cannot be deleted on its own, absorbed in a merge or moved out of the vault, whatever the entry point. Deleting the vault deletes it. It does not count against the contact quota. Existing vaults are left alone. The vault and its contact are created in one transaction, with dependent queued work started only after it commits. The dashboard and the interaction count are out of scope."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A new vault already holds me (Priority: P1)

Somebody creates a vault. When it opens, a contact standing for them is already in it, named with their first and last name as the account knows them.

**Why this priority**: every record in a vault is written from the owner's point of view (relationships, life events). Without the Me contact there is nothing to relate the others to.

**Independent Test**: create a vault for an account named Michael Scott and read its contacts.

**Acceptance Scenarios**:

1. **Given** an account named Michael Scott, **When** he creates the vault "Dunder Mifflin", **Then** the vault holds exactly one contact, marked as him, named Michael Scott.
2. **Given** that contact, **When** Michael renames it "Mike Scott", **Then** the change is kept.
3. **Given** that contact, **When** Michael later renames his account "Michael Gary Scott", **Then** the contact still reads "Mike Scott".
4. **Given** an account on the free plan already at its contact ceiling, **When** it creates a vault, **Then** the vault and its Me contact are created and the ceiling is not consumed.

---

### User Story 2 - Signing up gives the first vault its Me contact (Priority: P1)

Somebody signs up. The first vault the product creates for them holds their Me contact from the first screen.

**Why this priority**: the first vault is the one everybody sees first; the product must not have a different shape for it.

**Independent Test**: sign up as Pam Beesly and read the contacts of the first vault.

**Acceptance Scenarios**:

1. **Given** nobody, **When** Pam Beesly signs up, **Then** her first vault holds one contact marked as her, named Pam Beesly, built on the default contact template.
2. **Given** sign up, **When** the background seeding that follows runs, **Then** it does not create a second contact template or a second Me contact.

---

### User Story 3 - The Me contact stays (Priority: P2)

The Me contact cannot leave its vault by any route other than the vault itself being deleted.

**Why this priority**: the product's invariants depend on it; the guards mostly exist already and this story proves them.

**Independent Test**: try each route against the Me contact and confirm each refuses, then delete the vault.

**Acceptance Scenarios**:

1. **Given** the Me contact, **When** somebody deletes it from the screen, the API or a synchronised device, **Then** the deletion is refused with the existing wording.
2. **Given** the Me contact and another contact, **When** somebody asks to absorb the Me contact into the other, **Then** the merge is refused. Absorbing the other into the Me contact stays possible.
3. **Given** the Me contact, **When** a device tries to move it to another address book, **Then** the move is refused.
4. **Given** the Me contact, **When** the vault is deleted, **Then** the Me contact is deleted with it.

---

### User Story 4 - Nothing half made (Priority: P2)

If creating the Me contact fails, the vault is not created either, and no queued work refers to a vault that does not exist.

**Independent Test**: make the contact creation fail and check that no vault row, default template row or queued job survives.

**Acceptance Scenarios**:

1. **Given** an account with no published contact template, **When** it creates a vault, **Then** no vault is created and the person is told why.
2. **Given** a failure while the Me contact is written, **When** the vault is created, **Then** nothing is left behind and nothing is queued.

### Edge Cases

- A vault already holding a Me contact refuses a second one, however it is asked for.
- Vaults created before this change keep the contacts they have. None is added or changed.
- The vault's contact count and the template's record count include the Me contact, as they include every contact.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Creating a vault MUST create, in the same operation, one contact marked as standing for the account owner.
- **FR-002**: That contact MUST take the account's first and last name at the moment the vault is created, and MUST NOT be updated when the account name later changes.
- **FR-003**: FR-001 MUST hold for the first vault created at sign up. The default contact template, and what it depends on, MUST exist before that vault is created.
- **FR-004**: A vault MUST hold at most one Me contact.
- **FR-005**: The Me contact MUST stay editable like any other contact.
- **FR-006**: The Me contact MUST NOT be deleted on its own, absorbed into another contact, or moved out of its vault, through the screens, the API, the MCP server or synchronisation.
- **FR-007**: Deleting a vault MUST delete its Me contact with it.
- **FR-008**: The Me contact MUST NOT count against the account's contact ceiling, and creating a vault MUST NOT be refused because of that ceiling.
- **FR-009**: If any part of creating the vault or its Me contact fails, nothing MUST remain: no vault, no default template assignment, no contact, no queued work.
- **FR-010**: Work queued by the creation (journal entries, duplicate detection) MUST start only once the creation is committed.
- **FR-011**: Existing vaults MUST NOT be changed.
- **FR-012**: The constitution's Me contact invariant MUST be amended to say one Me contact per vault, which is what the API, the MCP server and this feature already assume.

### Key Entities

- **Vault**: a compartment of the account. Now always created with its Me contact.
- **Me contact**: an ordinary contact flagged as standing for the account owner, one per vault.
- **Default contact template**: the published template a vault's new contacts start from. Must exist before the Me contact can be written.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of vaults created after this change hold exactly one Me contact the moment they exist.
- **SC-002**: Every route that deletes, merges or moves a contact refuses the Me contact, each proven by a test.
- **SC-003**: A failed creation leaves zero rows and zero queued jobs behind, proven by a test.
- **SC-004**: An account at its contact ceiling can still create a vault.

## Assumptions

- The "name of the account" is the first and last name of its one user.
- An account whose system contact template is missing or unpublished cannot create a vault; the refusal is shown on the form and as a 422 on the API. This was previously allowed and produced a vault nobody could add a contact to.
- Creating the Me contact is journalled like any contact creation.
- Value lists and the default contact template move from the queued sign up chain into the sign up transaction, because the Me contact needs them. The rest of the chain stays queued.
- The dashboard and the interaction count are out of scope, even where the new contact changes what they display.
