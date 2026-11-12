# Feature Specification: Add a Possession Screen

**Feature Branch**: `2026-10-25-location-management` | **Created**: 2026-10-26 | **Status**: Draft

**Input**: "same branch. add the ui to add a possession, based on this design: Add a possession.dc.html (claude.ai/design project 85e4275a). The photo should be a placeholder for now."

Possessions exist since 091 through the API and the assistant only. This feature adds the first
possession screen of a vault: the form that adds one. It also adds what the form shows and the record
lacks: how a possession was acquired, and the tags it carries (092 left that for later). The photo
area is drawn but does nothing yet.

## Clarifications

### Session 2026-10-26

- Q: The design allows "No type"; a possession must have one. → A: The type stays required; no "No type" choice.
- Q: "How you got it" has no storage. → A: Build it now, on the record, the API, the assistant and the form.
- Q: Possessions cannot carry tags. → A: Build it now: pick existing tags, create new ones from the form, API and assistant too.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Add with a name and a type (Priority: P1)

Michael Scott opens "Add a possession" from All possessions, writes "World’s Best Boss mug", picks
Kitchenware, saves, and is returned to All possessions with a confirmation.

**Independent Test**: Save a name and a type; the possession is in the vault, owned, counting one, not featured.

1. **Given** a missing name or type, **When** saving, **Then** the form returns with everything typed kept and a message on the field.
2. **Given** a vault of another account, **When** its form address is opened, **Then** the answer is not found.

### User Story 2 - Pick a type and fill its fields (Priority: P1)

Dwight Schrute types "veh" in the type picker, picks Vehicle, and fills Registration and Mileage,
which appeared under the picker. Picking another type replaces them.

**Independent Test**: Search, pick, fill, save; the values are on the possession.

1. **Given** the open picker, **Then** it lists "Recently used" (at most four) and "All types" by name, each with its colour and field count, and narrows as he types.
2. **Given** a search matching nothing, **Then** it says so and links to the possession types settings.
3. **Given** the open picker, **When** Escape or a click outside, **Then** it closes and focus returns to its trigger; the keyboard alone can choose a type.
4. **Given** a chosen type, **Then** its fields appear grouped and ordered as the type defines, none required; a value that does not fit its field is refused with a message on it.

### User Story 3 - Say where it is kept (Priority: P2)

Pam Beesly keeps a watercolour in "Home › Living room"; the form shows the address of Home.

1. **Given** the location list, **Then** every account location shows its full path, under "Not recorded".
2. **Given** a location under a place with an address, **Then** the nearest address is shown and where it comes from.

### User Story 4 - Record how it was acquired (Priority: P2)

Angela Martin records a porcelain cat as "A gift" from her sister, the day she received it, and its value.

1. **Given** "A gift" or "Inherited", **Then** a search for a person of the vault appears ("Given by", "Inherited from"); for "Bought" or "Made it" no person is kept.
2. **Given** a way chosen, **Then** the date reads "Bought on", "Received on", "Inherited on" or "Made on", and "Acquired on" with none.
3. **Given** a future date or a negative value, **When** saving, **Then** it is refused with a message on the field.
4. **Given** the giver is later deleted, **Then** the possession shows no giver and keeps everything else; restoring the contact brings the giver back.

### User Story 5 - Put tags on it (Priority: P2)

Kevin Malone tags his chili pot "insured" (existing) and "office party" (new, created on save).

1. **Given** the tag box, **When** typing then Enter or a comma, **Then** the tag is added, an existing tag reused ignoring case, suggestions offered as he types, each tag at most once.
2. **Given** a tag in the box, **When** its remove control or Backspace in an empty input, **Then** it leaves.
3. **Given** a tag carried by possessions, **When** deleted in settings, **Then** the confirmation says how many carry it, and deleting takes it off them.

### User Story 6 - Feature it (Priority: P3)

Oscar Martinez ticks "Feature this possession"; it is placed after the vault's other featured ones.

### User Story 7 - See where photos will go (Priority: P3)

The Photos area is drawn, cannot be activated, opens no file chooser, and says photos come later.

### Edge Cases

- No type in the account: the picker says so and links to settings; saving is refused.
- No location: the list offers only "Not recorded". A person search matching nobody says so.
- Nothing added in the vault yet: "Recently used" is absent.
- A type, location or person gone between opening and saving, or of another account or vault: refused, nothing saved, typed values kept.
- A tag deleted between opening and saving: a tag of that name is created again on save, as any new name is (FR-013).

## Requirements *(mandatory)*

- **FR-001**: All possessions MUST offer a control opening the form for that vault, reachable only for a vault of the person's account.
- **FR-002**: The form MUST sit in the vault shell with a trail (vault, All possessions, New possession) and a bar along its foot holding Cancel and "Add possession", saying what is about to be added.
- **FR-003**: A refused save MUST return the form with every value kept, the type's fields shown again, and each message beside its field.
- **FR-004**: A save MUST return to All possessions with a confirmation naming the possession, and MUST write everything or nothing.
- **FR-005**: The name and the type MUST be required, the description optional.
- **FR-006**: The type picker MUST be searchable, grouped (Recently used, All types), closable with Escape and outside clicks, operable by keyboard, and link to the types settings.
- **FR-007**: "Recently used" MUST hold the distinct types of the latest possessions added in the vault, at most four, newest first, and be offered as quick choices under the picker too.
- **FR-008**: The chosen type's fields MUST appear grouped and ordered by the type, each with the entry its kind asks for, checked by the existing field value rules.
- **FR-009**: The location MUST be optional, listed with full paths in settings order, with the nearest address shown and a link to add a location.
- **FR-010**: A possession MUST be able to record a way of acquisition (Bought, A gift, Inherited, Made it), a person of its vault for a gift or an inheritance only, a date not in the future, and a non negative declared value with its currency, all optional.
- **FR-011**: The currency MUST default to the one of the person's language.
- **FR-012**: A deleted person a possession came from MUST stop showing as its giver, and show again if the contact is restored.
- **FR-013**: A possession MUST be able to carry any number of the account's tags, each once; a new name typed on the form is created with the default colour on save.
- **FR-014**: Deleting a tag MUST take it off every possession, and its confirmation MUST say how many carry it.
- **FR-015**: Acquisition data and tags MUST be readable and writable through the API and the assistant wherever a possession is created, updated or read, and journaled with the creation.
- **FR-016**: The form MUST offer "Feature this possession", unticked, saying how many the vault already features when any.
- **FR-017**: The Photos area MUST be inert and carry its reason, tied to it for assistive technology.
- **FR-018**: Every wording MUST be translated in every locale, counts through counted phrases, and the form MUST meet WCAG 2.2 AA.

### Key Entities

- **Possession** (exists): gains way of acquisition, the person it came from, acquisition date, declared value and currency, and its tags.
- **Way of acquisition** (new fixed list), **Possession tag** (now carried by possessions), **Contact** (may be the giver, same vault).

## Success Criteria *(mandatory)*

- **SC-001**: A possession with a name and a type is added in under 30 seconds from All possessions.
- **SC-002**: Among 25 types, one is found with at most three keystrokes plus confirmation.
- **SC-003**: Everything the form shows is saved and read back through the API; nothing typed is lost on refusal.
- **SC-004**: The whole form is filled and saved with the keyboard alone and announced correctly by a screen reader.
- **SC-005**: No untranslated copy and no wrong plural at zero, one or many, in any locale.

## Assumptions

- Same branch, as asked. Status owned, quantity one, no identifier; condition stays a later feature.
- Types belong to the account; the design's "types in this vault" and "vault settings" link mean the account's types and their settings.
- The form needs JavaScript, as the life event form does. Nothing is preselected under "How you got it": a preselected answer records a guess.
- The declared value carries a currency because no account holds one; the design's "in €" becomes the chosen currency.
- New tags take the default tag colour. Recently used types are derived, not stored.
- No screen shows one possession yet, so saving returns to All possessions; editing from a screen is later.
