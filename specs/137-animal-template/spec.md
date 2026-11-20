# Feature Specification: A second supplied animal template

**Feature Branch**: `2026-11-19-animal-template`

**Created**: 2026-11-19

**Status**: Draft

**Input**: User description: "Change the default animal template, based on this proposal. Today Basic animal holds one page and four sections: Identity (species, breed, sex, birthday, adoption date, microchip number), People (a repeating list pairing a contact with a role), Care (two free texts, food and routine, then health and care), Notes (one free text). Enrich it around two uses, remembering the animal of somebody close and keeping useful information about one's own animal. Every field stays optional. Keep detailed weight, vaccination or appointment histories for a possible fuller template. Tell me how to migrate existing customers; maybe it is better not to touch their template?"

## Vocabulary

- **Supplied template**: a template the application writes into an account on its own, marked as coming from the system rather than from the person. Today an account is supplied Basic contact, Essentials, Friends and family, Professional network, Full portrait and Basic animal.
- **Animal**: the supplied template this feature adds, beside Basic animal, in every account. Basic animal stays supplied and stays the default.
- **Field identity**: the stable, language independent name of a field, together with its kind. It is what a value is matched on when a record changes template. The label a person reads is not part of it.

## The Animal template

Description: "Remember the animals that matter, their habits and the people who look after them."

Every field is optional. The French wording is the French translation.

### Page 1: Profile (Profil)

| Section | Field | Kind | French |
| --- | --- | --- | --- |
| Identity (Identité) | Species | Short text | Espèce |
| | Breed or mix | Short text | Race ou croisement |
| | Sex | Single choice, from the existing animal sexes list | Sexe |
| | Date of birth | Recurring date, year optional | Date de naissance |
| | Adoption date | Recurring date | Date d’adoption |
| | Colour and distinctive marks | Long text | Couleur et signes distinctifs |
| People (Personnes) | People linked to the animal | Repeating group | Personnes liées à l’animal |
| | ↳ Person | Link to a contact | Personne |
| | ↳ Role | Short text, for example owner, vet or pet sitter | Rôle |
| Character and preferences (Caractère et préférences) | Character | Long text | Caractère |
| | What they like | Long text | Ce qu’il aime |
| | What they dislike or fear | Long text | Ce qu’il n’aime pas ou ce qui lui fait peur |
| | How to approach them | Long text | Comment l’approcher |
| Photos and notes (Photos et notes) | Photos | Repeating group | Photos |
| | ↳ Image | Image | Image |
| | ↳ Caption | Short text | Légende |
| | Free-form notes | Long text, repeatable | Notes libres |

### Page 2: Daily life and care (Vie quotidienne et soins)

| Section | Field | Kind | French |
| --- | --- | --- | --- |
| Food and habits (Alimentation et habitudes) | Usual food | Long text | Alimentation habituelle |
| | Times and amounts | Long text | Horaires et quantités |
| | Favourite treats | Long text | Friandises préférées |
| | Routine and activities | Long text | Routine et activités |
| | Pet sitting instructions | Long text | Consignes pour le garder |
| Health and care (Santé et soins) | Known allergies and intolerances | Long text | Allergies et intolérances connues |
| | Health issues to know about | Long text | Problèmes de santé à connaître |
| | Treatments and instructions | Long text | Traitements et consignes |
| | Neutered or spayed | Single choice: yes, no, unknown | Stérilisation (oui, non, inconnu) |
| | Other usual care | Long text | Autres soins habituels |
| Identification and documents (Identification et documents) | Microchip number | Short text | Numéro de puce |
| | Other identification | Short text, for example a tattoo or a ring number | Autre identification |
| | Insurance | Short text | Assurance |
| | Policy number | Short text | Numéro de contrat |
| | Documents | Repeating group | Documents |
| | ↳ File | File | Fichier |
| | ↳ Description | Short text | Description |

### Concepts shared with Basic animal

A concept Basic animal already holds keeps its field identity in Animal, whatever its new label or section, so that an animal moved from one to the other keeps the value.

| Concept | Basic animal | Animal |
| --- | --- | --- |
| Species | Identity | Identity |
| Breed | Identity, "Breed" | Identity, "Breed or mix" |
| Sex | Identity | Identity |
| Birthday | Identity, "Birthday" | Identity, "Date of birth" |
| Adoption date | Identity | Identity |
| Microchip number | Identity | Identification and documents |
| Caregivers, with person and role | People, "Caregivers" | People, "People linked to the animal" |

Basic animal's "Food and routine", "Health and care notes" and single "Notes" have no single counterpart in Animal: the proposal splits the first two into several fields, and makes the notes repeatable. On a move they are set aside, as the move already does for any value without a home, and stay recoverable.

## Clarifications

### Session 2026-11-19

- Q: Does Animal replace Basic animal for new accounts? → A: No. Animal is added beside Basic animal in every account, new and existing, so that people choose. Basic animal stays supplied to new accounts and stays the default of every vault. This replaces the earlier reading of "change the default template".
- Q: How does somebody pick Animal when adding an animal? → A: The form that adds an animal offers the template, as the form that adds a contact does. The vault's default is preselected; the picker shows only when the kind has more than one template to offer.
- Q: Should existing accounts have their Basic animal changed into Animal? → A: No. Animal is added beside Basic animal through a one-off backfill. Basic animal stays their vault default, and nothing they hold moves. Rewriting Basic animal was ruled out by principle VI of the constitution; leaving existing accounts without Animal was rejected.
- Q: Which fields of Animal show under the animal's name? → A: Species, Breed or mix and Date of birth, in that order.
- Q: An animal had no page of its own when this spec was written. Where does the header live? → A: On the record page and edit form built in the branch this work is stacked on (commit `6d12e152`, "record page and empty list"). An animal has no last contact and no reminders, so its header shows the highlighted fields only. Moving an animal to another template has no screen there yet; the move itself already accepts an animal, and that is where the values following it are proven.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Every account has Animal beside Basic animal (Priority: P1)

Somebody creates an account, or already has one. When they open their templates, they find Basic animal as before and Animal beside it, laid out as above, with every field optional. Basic animal stays the template a vault starts an animal on. Their own changes to Basic animal, their animals and their values are exactly as before.

**Why this priority**: This is the change itself, and it must reach everybody without changing anything they already have. Principle VI of the constitution forbids the system from changing what somebody already sees without them accepting it.

**Independent Test**: Create an account and run the backfill on an existing one; in both, open the templates and check Animal against the tables, and check that Basic animal, the vault defaults and every animal's values are unchanged.

**Acceptance Scenarios**:

1. **Given** nobody, **When** an account is created, **Then** its templates include Basic animal and Animal, and every vault starts a new animal on Basic animal.
2. **Given** an existing account with Basic animal, **When** the backfill runs, **Then** the account holds Basic animal and Animal, and every vault default, every version of Basic animal and every animal's values are as before.
3. **Given** an account that already received Animal, **When** the backfill runs again, **Then** nothing is added a second time.
4. **Given** an existing account that received Animal then deleted it, **When** the backfill runs again, **Then** Animal is not brought back.
5. **Given** any account, **When** Animal is added, **Then** its animals are still listed under one kind, Animals, and no second kind appears.
6. **Given** an account whose owner reads the application in French, **When** they open Animal, **Then** every page, section, field and choice reads with the French wording of the tables.

---

### User Story 2 - Somebody picks Animal when adding an animal (Priority: P1)

Somebody adds an animal. The form asks which template it starts from, with the vault's default chosen already. They pick Animal and fill what they know, or nothing but a name. The animal's page then shows its species, breed and date of birth under its name.

**Why this priority**: Without a way to pick it, Animal exists but nobody can write an animal on it, since a vault's default stays Basic animal.

**Independent Test**: In an account holding both templates, add an animal on Animal with only a name, then one with species, breed and a yearless date of birth, and open it.

**Acceptance Scenarios**:

1. **Given** an account holding Basic animal and Animal, **When** its owner opens the form that adds an animal, **Then** the form offers both templates with the vault's default preselected.
2. **Given** that form, **When** its owner picks Animal, **Then** the form shows Animal's pages, sections and fields, and the saved animal is written on Animal.
3. **Given** an account holding only one active template for the kind, **When** its owner opens the form, **Then** no choice of template is shown.
4. **Given** the form, **When** somebody sends a template of another kind or of another account, **Then** the animal is refused and nothing is written.
5. **Given** an animal on Animal with nothing filled but its name, **When** it is saved, **Then** it is saved.
6. **Given** an animal on Animal with species, breed and a date of birth whose year is unknown, **When** its owner opens it, **Then** those three show under its name in that order, the date without a year.

---

### User Story 3 - An animal moves between the two templates without losing what they share (Priority: P3)

When moving an animal from Basic animal to Animal becomes possible, the values of the concepts both hold follow it, and the rest is set aside rather than deleted.

**Why this priority**: No screen moves an animal yet. The move already accepts an animal, so the rule is proven there now and holds when a screen arrives.

**Independent Test**: Move an animal on Basic animal with every field filled to Animal through the move action, and compare.

**Acceptance Scenarios**:

1. **Given** an animal on Basic animal with species, breed, sex, birthday, adoption date, microchip number and two caregivers filled, **When** it is moved to Animal, **Then** all of them show in Animal, and its food and routine, health and care and notes are set aside, recoverable.

---

### Edge Cases

- An existing account already holds a template or a section of its own whose name matches one of Animal: both stay, nothing of theirs is renamed or merged, and the backfill skips Animal for that account and records why.
- The backfill stops halfway through an account: running it again finishes that account without duplicating what it already wrote.
- An existing account deleted Basic animal: the backfill does not bring it back, and still adds Animal.
- An existing account deleted its Animals kind: the backfill brings back neither the kind nor Basic animal, and adds nothing, since Animal would have no kind to apply to.
- A vault with no default animal template, for example after Basic animal was deleted: the form that adds an animal starts from the first template on offer instead of refusing.
- Animal deactivated by the person: it is not offered on the form, and animals already on it are untouched.
- A date of birth with no year: the animal shows no age, and any birthday reminder still fires on the day.
- Neutered or spayed left empty is distinct from "unknown": empty means nobody answered.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The application MUST supply Animal, laid out exactly as the tables above, to every account created after the release, beside Basic animal, which it MUST keep supplying unchanged.
- **FR-002**: Animal MUST carry the description given above, and every field MUST be optional.
- **FR-003**: Every name of Animal, its pages, sections, fields and choices MUST be translated in every shipped language, with the French wording of the tables as the French translation, and MUST appear in the reader's language.
- **FR-004**: Basic animal MUST stay the default animal template of every vault, in new and existing accounts, including vaults created after the release. Animal MUST NOT become a vault default on its own.
- **FR-005**: Animal MUST apply to the same Animals kind Basic animal applies to. No account may end up with two Animals kinds.
- **FR-006**: Each concept of the Shared concepts table MUST carry in Animal the same field identity it carries in Basic animal. Every other field of Animal MUST carry an identity no other concept uses, including fields inside repeating groups.
- **FR-007**: Animal MUST be identified by a stable name of its own, distinct from Basic animal's, and so MUST each of its sections, so that a supplied Animal can be told apart from Basic animal and from anything the person built.
- **FR-008**: The release MUST NOT change, republish or retire any version of Basic animal in any account, MUST NOT move any existing animal to another version, and MUST NOT change any vault's default template.
- **FR-009**: The date of birth MUST accept a day and month without a year.
- **FR-010**: Neutered or spayed MUST offer exactly yes, no and unknown, translated, and MUST allow no answer.
- **FR-011**: Existing accounts MUST receive Animal through a one-off backfill that runs once in production, supplies it through the same path account creation uses, and is safe to run again: Animal already supplied, or supplied then deleted, MUST NOT be added a second time.
- **FR-012**: The form that adds a record of a kind MUST let the person pick the template it starts from among the account's active templates of that kind, with the vault's default preselected, and MUST show no choice when only one is available. The pick MUST be refused when it names a template of another kind or of another account.
- **FR-013**: The product changelog MUST tell people Animal exists in every account, beside Basic animal, and how to pick it.
- **FR-014**: The help centre pages that describe Basic animal MUST describe Animal beside it, and how to pick it when adding an animal.
- **FR-015**: In Animal, Species, Breed or mix and Date of birth MUST be the highlighted fields and no other field may be highlighted, so that the header of an animal's page shows those three, in that order, under its name. A field left empty shows as not recorded, as on a contact. Basic animal's highlighted fields are not changed, in any account.
- **FR-016**: Detailed histories of weight, vaccinations or appointments MUST NOT be part of Animal.

### Key Entities

- **Supplied template**: Animal, marked as supplied by the application and identified by a stable name independent of the language, so that the backfill can tell whether an account already has it.
- **Animals kind**: the kind of record animals belong to in each account, which Basic animal and Animal both apply to.
- **Field identity**: the stable name and kind of a field, what a value is matched on when an animal changes template.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of accounts created after the release hold Basic animal and Animal, and 100% of their vaults start a new animal on Basic animal unless somebody picks Animal.
- **SC-002**: 0 existing accounts see a change to Basic animal, to a vault default or to an animal's values after the release.
- **SC-003**: 100% of existing accounts that did not hold a clashing name hold Animal after the backfill, and running it a second time adds nothing.
- **SC-004**: Moving an animal from Basic animal to Animal keeps visible 100% of the values of the seven shared concepts, and sets aside, without deleting, 100% of the others.
- **SC-005**: Somebody can add an animal on Animal with one choice more than adding one on Basic animal: picking the template.
- **SC-006**: Every label of Animal reads in the reader's language in 100% of shipped languages.

## Assumptions

- Animal is added, not substituted: Basic animal is neither edited nor withdrawn anywhere.
- Rewriting Basic animal in existing accounts is excluded whatever the answer: principle VI of the constitution says a published version is frozen, a record stays on its version until somebody moves it, and the system may not change what somebody sees without them accepting it. Some accounts have also edited Basic animal, and a rewrite would have to guess what to do with those changes.
- The name "Animal" is kept for the template although the kind is also called Animal; they appear on different screens.
- The animal sexes list already exists and is reused. Neutered or spayed reads its three choices from a new supplied list, editable like Animal sexes, because a single choice field always reads its choices from a list.
- Every field kind the template needs already exists. No new kind is built.
- Food and routine, Health and care notes and the single Notes of Basic animal are not given a field identity in Animal: forcing one onto a narrower field would put, for example, a whole care routine under "Usual food".
