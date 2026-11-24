# Feature Specification: Hide the main sections of a vault

**Feature Branch**: `151-vault-section-visibility` | **Created**: 2026-11-24 | **Status**: Draft

**Input**: User description: "ajoute une option dans un vault pour cacher visuellement les tabs principaux dans un vault. voici le design: Vault Settings.dc.html (claude.ai/design project c1c29144). reprends les toggle qu'on a deja dans le design system. ajoute la documentation appropriée. ajoute aussi la methode d'api et mcp pour permettre ceci."

## Design reference

A **What this vault shows** panel on Vault settings, between **What a new contact starts from** and **Export this vault**. It lists **People** ("Contacts, their relationships and everything written about them."), **Things** ("The possessions of this vault.", corrected from the design's "Entities you keep track of in this vault.", since entities sit under People) and **Knowledge** ("Notes and what you have written down outside a contact."), each with its icon, a state word (**Shown** or **Hidden**) and a switch, then a quiet notice **Hidden here, still available elsewhere** ("Hiding a section only removes it from the interface. Nothing is deleted, and People, Things and Knowledge stay fully available through the API and MCP."), then **Save changes**. The switch is the application's existing one, not the one the design draws inline.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Hide a section the vault does not use (Priority: P1)

Somebody keeps a vault only for the people in their life. They never write knowledge notes or track possessions there, and the **Things** and **Knowledge** entries in the sidebar are noise. In **Vault settings**, they switch **Things** and **Knowledge** off and save. The sidebar of that vault now shows only the vault entries and **People**.

**Why this priority**: This is the whole point of the feature. Without it, nothing else has a reason to exist. **Independent Test**: Open the settings of a vault, switch one section off, save, then open any screen of that vault and check the section is gone from the sidebar while the other sections are still there.

**Acceptance Scenarios**:

1. **Given** a vault with every section shown, **When** the person switches **Knowledge** off and chooses **Save changes**, **Then** Monica confirms the save, the **Knowledge** group disappears from that vault's sidebar, and the row now reads **Hidden**.
2. **Given** a vault where **Knowledge** is hidden, **When** the person switches it back on and saves, **Then** the **Knowledge** group reappears in the sidebar with its notes and lists untouched.
3. **Given** two vaults in the same account, **When** **People** is hidden in one, **Then** the other vault's sidebar still shows **People**.
4. **Given** a vault where a section is hidden, **When** the person searches inside that vault, **Then** no result belonging to the hidden section is offered.
5. **Given** the person flips a switch but leaves the screen without saving, **When** they come back, **Then** nothing has changed.

### User Story 2 - Change the setting through the API (Priority: P2)

Somebody scripts their setup of Monica. They read a vault through the API and see which sections it shows, then change that with the same call that already updates a vault's name, description and colour.

**Why this priority**: Every capability a person has on screen has its API counterpart in this product. It is needed for the feature to be complete, but the screen delivers the value first. **Independent Test**: Read a vault through the API, update one section's visibility, read it again and check the value changed and the sidebar follows.

**Acceptance Scenarios**:

1. **Given** a vault, **When** it is read through the API, **Then** the answer states, for each of the three sections, whether it is shown.
2. **Given** a vault, **When** an update sends only the visibility of **Things**, **Then** that value changes and the name, description, colour and the two other sections stay as they were.
3. **Given** an update sending a value that is not true or false for a section, **When** it is received, **Then** it is refused with a validation error naming that field, and nothing changes.
4. **Given** a vault where every section is hidden, **When** the API lists the contacts, possessions or knowledge of that vault, **Then** they are returned exactly as before.

### User Story 3 - Change the setting through an assistant (Priority: P3)

Somebody using an assistant connected to Monica over MCP asks it to hide **Knowledge** in their Family vault. The assistant has a tool to update a vault and does it.

**Why this priority**: The MCP server mirrors the API. It follows once the API exists. **Independent Test**: Call the vault update tool, then read the vault.

**Acceptance Scenarios**:

1. **Given** a connected assistant, **When** it updates a vault and sends only the visibility of **Knowledge**, **Then** the answer is exactly the one the API update gives, and everything not sent is kept.
2. **Given** an assistant naming a vault of another account, **When** it calls the update tool, **Then** it is told the vault does not exist.
3. **Given** an assistant lists the vaults, **When** the list comes back, **Then** each vault states which sections it shows.

### User Story 4 - Learn what hiding does (Priority: P3)

Somebody wonders whether hiding **People** deletes their contacts. The panel's notice and the help centre page on vault settings both say that nothing is deleted, that the data stays reachable through the API and MCP, and how to bring a section back.

**Why this priority**: Hiding something people care about without saying it is safe creates fear and support requests. **Independent Test**: Read the help page and the API reference for vaults.

**Acceptance Scenarios**:

1. **Given** the help centre page on vault settings, **When** somebody reads it, **Then** it explains what each section covers, what hiding removes from the screens, that nothing is deleted, and how to show it again.
2. **Given** the API reference for vaults, **When** somebody reads the read and update endpoints, **Then** the three new fields are described with their type and default.
3. **Given** the product changelog, **When** the change ships, **Then** it carries an entry about it.

### Edge Cases

- **All three sections hidden.** Allowed. The sidebar keeps the vault entries (**Dashboard**, **Reminders**, **Vault settings**), so the person can always come back to the setting.
- **Things already absent.** The **Things** group only appears when the account has possession types. Hiding **Things** in a vault where the group would not appear anyway changes nothing on screen, and the switch still records the choice so it applies once possession types exist.
- **A direct link to a hidden section.** A bookmark, a reminder, a timeline entry or a link elsewhere in Monica that points at a contact, a possession or a knowledge note of a hidden section still opens it. Hiding is visual and never makes a record unreachable. The section's entry in the sidebar is not highlighted, since it is not drawn.
- **People hidden and the groups and entity types.** The **People** group of the sidebar holds **People**, the account's entity types and **Groups**. Hiding **People** hides the whole group.
- **Saving the panel saves only the panel.** Changing a switch and saving does not touch the name, description, colour or default template of the vault, and saving another panel does not touch the switches.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Each vault MUST record, separately for **People**, **Things** and **Knowledge**, whether that section is shown.
- **FR-002**: Every section MUST be shown by default, for a vault created after this change and for every vault that existed before it.
- **FR-003**: The Vault settings screen MUST hold a **What this vault shows** panel matching the design reference, with one switch per section reflecting the vault's current values, a word stating each section's state, the notice about the data staying available, and a **Save changes** button.
- **FR-004**: The switches MUST be the switch component the application already uses for an on or off setting, and each switch MUST be named by its section for assistive technology.
- **FR-005**: Saving the panel MUST store the three values at once and confirm the save with a message, and MUST NOT change any other setting of the vault.
- **FR-006**: The sidebar of a vault MUST leave out the group of every hidden section, entries and badges included, and MUST keep the vault entries in every case.
- **FR-007**: Search inside a vault MUST leave out results belonging to a hidden section.
- **FR-008**: Hiding a section MUST NOT delete, move or alter any record, and MUST NOT make any record unreachable by its direct address.
- **FR-009**: The API's representation of a vault MUST include the three values, wherever a vault is returned (read, list, create and update answers).
- **FR-010**: The API's vault update MUST accept each of the three values independently as true or false, keep any value left out, and refuse anything else with a validation error naming the field.
- **FR-011**: Hiding a section MUST NOT change what the API or the MCP server return for the records of that section.
- **FR-012**: The MCP server MUST offer a tool to update a vault that answers exactly as the API's vault update does, including the three values, and the tool that lists vaults MUST show them through the API's representation.
- **FR-013**: A change of the setting, from the screen, the API or an assistant, MUST be written in the account's journal the way other changes to a vault are.
- **FR-014**: Only somebody who can reach the vault (its account) MUST be able to read or change the setting. A vault of another account MUST answer as not existing.
- **FR-015**: The help centre page on vault settings, the API reference for vaults, the MCP documentation where tools are listed, and the product changelog MUST describe the setting.
- **FR-016**: Every new piece of copy MUST be translatable and translated into every shipped language.

### Key Entities

- **Vault**: Gains three yes or no attributes, one per main section: whether **People** is shown, whether **Things** is shown, whether **Knowledge** is shown. They belong to the vault, not to the person, so they apply to whoever opens the vault.
- **Main section**: One of **People** (contacts, entity types and groups), **Things** (possessions) and **Knowledge** (notes and lists). Each matches one group of the vault's sidebar. The list is fixed by the product; a person cannot add one.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A person can hide or show a section of a vault in under 30 seconds from opening the vault, with one save.
- **SC-002**: After hiding a section, 100% of the screens of that vault draw the sidebar without it, and 0 records of the vault are lost, changed or unreachable by their address.
- **SC-003**: The same change made through the screen, the API and an assistant gives the same stored result in all three, and each is recorded in the account's journal.
- **SC-004**: Reading the API or MCP answer for the records of a hidden section gives the same result as before the section was hidden.
- **SC-005**: Somebody reading only the help centre page can say whether hiding a section deletes anything, and how to undo it.

## Assumptions

- "Les tabs principaux" means the three groups of the vault's sidebar below the vault entries: **People**, **Things** and **Knowledge**, which is what the design lists. **Dashboard**, **Reminders** and **Vault settings** cannot be hidden.
- "Visuellement" means navigation and search only: a hidden section's screens still open from a direct address. Refusing them would break reminders, timeline entries and links pointing at contacts, and the design says hiding "only removes it from the interface".
- The dashboard of the vault keeps its current content whatever is hidden. The design names the sidebar and search, nothing else.
- The API carries the setting on the existing vault read and update endpoints rather than on a new endpoint, since it is a property of the vault like its colour.
- No MCP tool updates a vault today, so one is added, mirroring the full vault update (name, description, colour and the three values). That is the only way for the tool to answer as the endpoint it mirrors.
- The capability ships to everybody at once, without a flag: it is small, reversible by the person, and removes nothing.
- The panel's copy is the design's copy. The description of the Vault settings screen is extended to mention which sections it shows, as the design's page header does.
