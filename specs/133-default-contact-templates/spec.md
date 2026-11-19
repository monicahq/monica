# Feature Specification: Four supplied contact templates

**Feature Branch**: `2026-11-18-default-templates`

**Created**: 2026-11-18

**Status**: Draft

**Input**: User description: "Give several new default templates that the application supplies when somebody creates their account. Also plan a migration for existing customers so they get them in their account too. New customers no longer get the Basic contact template, which is today the default one supplied; do not remove it from existing customers (superseded in Clarifications: Basic contact stays for everybody, as the default). Templates: Essentiel, Amis et proches, Réseau professionnel, Portrait complet (structure given in full below)."

## Vocabulary

- **Supplied template**: a template the application writes into an account on its own, marked as coming from the system rather than from the person. Today an account is supplied two: Basic contact and Basic animal.
- **Vault default**: the template a vault uses for a new contact when nobody picks one. Every vault has one for contacts.
- **Value list**: a list of choices a "single choice" or "multiple choice" field picks from (genders, contact categories, languages). Each account has its own copy and can edit it.
- **Repeating group**: a field that holds several entries, each entry made of one or more inner fields (a list of emails, a list of gift ideas each with an idea, a link and a note).

## The four templates

English is the source language of the application, so every name below has an English source wording, and the French wording given in the request is its French translation. Both wordings are agreed: the French one is the French translation, the English one is the source.

Field kinds use these words: short text, long text, URL, date, recurring date (a date that comes back every year, such as a birthday), single choice, multiple choice, address, file, image, link to a contact, repeating group.

### Essentials (fr: Essentiel)

Description: "Keep your contacts' details and essential landmarks at hand every day." (fr: « Retrouver les coordonnées et les repères essentiels de ses contacts au quotidien. »)

| Page | Section | Field | Kind |
| --- | --- | --- | --- |
| Everyday (Au quotidien) | Landmarks (Repères) | Categories (Catégories) | multiple choice, contact categories |
| | | Birthday (Anniversaire) | recurring date |
| | Contact details (Coordonnées) | Emails (E-mails) | repeating group of email |
| | | Phones (Téléphones) | repeating group of phone |
| | | Addresses (Adresses) | repeating group of address |
| | | Website (Site web) | URL |
| | How we met (Notre rencontre) | Date we met (Date de rencontre) | date |
| | | Place or context (Lieu ou contexte) | short text |
| | | Introduced by (Présenté par) | link to a contact |
| | Notes (Notes) | Free-form notes (Notes libres) | repeating group of long text |

### Friends and family (fr: Amis et proches)

Description: "Remember the small things that matter, to take care of your relationships." (fr: « Retenir les petites choses qui comptent pour prendre soin de ses relations. »)

| Page | Section | Field | Kind |
| --- | --- | --- | --- |
| Everyday (Au quotidien) | Personal landmarks (Repères personnels) | Birthday (Anniversaire) | recurring date |
| | | Pronouns (Pronoms) | short text |
| | | Languages spoken (Langues parlées) | multiple choice, languages |
| | Contact details (Coordonnées) | Emails (E-mails) | repeating group of email |
| | | Phones (Téléphones) | repeating group of phone |
| | | Addresses (Adresses) | repeating group of address |
| | | Preferred way to reach them (Canal de communication préféré) | single choice, communication channels |
| | How we met (Notre rencontre) | Date we met (Date de rencontre) | date |
| | | Place or context (Lieu ou contexte) | short text |
| | | Introduced by (Présenté par) | link to a contact |
| Tastes and attentions (Goûts et attentions) | Interests (Centres d’intérêt) | Hobbies (Loisirs) | multiple choice, hobbies |
| | | Topics this person loves (Sujets qui passionnent cette personne) | long text |
| | At the table (À table) | Favourite dishes (Plats préférés) | long text |
| | | Favourite drinks (Boissons préférées) | long text |
| | | Dietary preferences (Préférences alimentaires) | long text |
| | | Allergies to know about (Allergies à connaître) | long text |
| | Gift ideas (Idées de cadeaux) | Ideas (Idées) | repeating group: Idea (Idée, short text), Link (Lien, URL), Note (Note, long text) |
| | Notes (Notes) | Free-form notes (Notes libres) | repeating group of long text |

### Professional network (fr: Réseau professionnel)

Description: "Remember people, their work and the common ground that feeds the relationship." (fr: « Se souvenir des personnes, de leur travail et des points communs qui nourrissent la relation. »)

| Page | Section | Field | Kind |
| --- | --- | --- | --- |
| Professional profile (Profil professionnel) | Current role (Activité actuelle) | Job role (Fonction) | short text |
| | | Organisation (Organisation) | short text |
| | | Industry (Secteur) | single choice, industries |
| | | Areas of expertise (Domaines d’expertise) | multiple choice, areas of expertise |
| | Work contact details (Coordonnées professionnelles) | Emails (E-mails) | repeating group of email |
| | | Phones (Téléphones) | repeating group of phone |
| | | Work address (Adresse professionnelle) | address (one, not repeating) |
| | | Website (Site web) | URL |
| | | Professional profile (Profil professionnel) | URL |
| | Career (Parcours) | Experience (Expériences) | repeating group: Organisation (short text), Job role (Fonction, short text), Start date (Date de début, date), End date (Date de fin, date) |
| Relationship (Relation) | First meeting (Première rencontre) | Date we met (Date de rencontre) | date |
| | | Event or context (Événement ou contexte) | short text |
| | | Introduced by (Présenté par) | link to a contact |
| | Common ground (Points de connexion) | Shared interests (Intérêts communs) | long text |
| | | Topics to bring up (Sujets à aborder) | long text |
| | | How I can help this person (Comment je peux aider cette personne) | long text |
| | Documents (Documents) | Documents (Documents) | repeating group: File (Fichier, file), Description (Description, short text) |
| | Notes (Notes) | Free-form notes (Notes libres) | repeating group of long text |

### Full portrait (fr: Portrait complet)

Description: "Build a rich record over time." (fr: « Construire une fiche riche au fil du temps. »)

| Page | Section | Field | Kind |
| --- | --- | --- | --- |
| Profile (Profil) | Personal landmarks (Repères personnels) | Categories (Catégories) | multiple choice, contact categories |
| | | Birthday (Anniversaire) | recurring date |
| | | Gender (Genre) | single choice, genders |
| | | Pronouns (Pronoms) | short text |
| | | Languages spoken (Langues parlées) | multiple choice, languages |
| | Contact details (Coordonnées) | Emails (E-mails) | repeating group of email |
| | | Phones (Téléphones) | repeating group of phone |
| | | Addresses (Adresses) | repeating group of address |
| | | Websites and online profiles (Sites et profils en ligne) | repeating group: Label (Libellé, short text), Link (Lien, URL) |
| | How we met (Notre rencontre) | Date we met (Date de rencontre) | date |
| | | Place (Lieu) | short text |
| | | Context (Contexte) | long text |
| | | Introduced by (Présenté par) | link to a contact |
| Daily life (Vie quotidienne) | Work and studies (Travail et études) | Current job role (Fonction actuelle) | short text |
| | | Organisation (Organisation) | short text |
| | | Education (Études) | repeating group: School (Établissement, short text), Field of study (Domaine, short text), Start date (Date de début, date), End date (Date de fin, date) |
| | Tastes and habits (Goûts et habitudes) | Hobbies (Loisirs) | multiple choice, hobbies |
| | | Cultural tastes (Goûts culturels) | long text |
| | | Favourite dishes (Plats préférés) | long text |
| | | Favourite drinks (Boissons préférées) | long text |
| | | Dietary preferences (Préférences alimentaires) | long text |
| | | Habits to remember (Habitudes à retenir) | long text |
| | Communication (Communication) | Preferred way to reach them (Canal de communication préféré) | single choice, communication channels |
| | | Languages to use (Langues de communication) | multiple choice, languages |
| | | Usual availability (Disponibilités habituelles) | long text |
| | Thoughtful gestures (Attentions) | Gift ideas (Idées de cadeaux) | repeating group: Idea, Link, Note |
| | | Outing ideas to share (Idées de sorties à partager) | repeating group: Idea, Link, Note |
| Memories (Souvenirs) | Photos (Photos) | Photos (Photos) | repeating group: Image (Image, image), Caption (Légende, short text) |
| | Documents (Documents) | Documents (Documents) | repeating group: File (Fichier, file), Description (Description, short text) |
| | Notes (Notes) | Free-form notes (Notes libres) | repeating group of long text |

## Shared concepts

A contact moving between templates keeps a value when the new template holds the same concept. These are the concepts the five supplied contact templates (Basic contact and the four new ones) share. Each one is a single concept with a single identity and kind everywhere it appears, whatever its label says.

| Concept | Where it appears (label) | Kind |
| --- | --- | --- |
| Categories | Basic contact, Essentials, Full portrait | multiple choice |
| Birthday | Basic contact, Essentials, Friends and family, Full portrait | recurring date |
| Gender | Basic contact, Full portrait | single choice |
| Emails (and each email in it) | all five | repeating group of email |
| Phones (and each phone in it) | all five | repeating group of phone |
| Addresses (and each address in it) | Basic contact, Essentials, Friends and family, Full portrait | repeating group of address |
| Website | Basic contact, Essentials, Professional network | URL |
| Notes (and each note in it) | all five | repeating group of long text |
| Documents (file, description) | Basic contact, Professional network, Full portrait | repeating group |
| Photos (image, caption) | Basic contact, Full portrait | repeating group |
| Pronouns | Friends and family, Full portrait | short text |
| Languages spoken | Friends and family, Full portrait (not "Languages to use", which is another concept) | multiple choice |
| Preferred way to reach them | Friends and family, Full portrait | single choice |
| Date we met | Essentials, Friends and family, Professional network, Full portrait | date |
| Where we met: "Place or context", "Event or context", "Place" | Essentials, Friends and family, Professional network, Full portrait | short text |
| Introduced by | Essentials, Friends and family, Professional network, Full portrait | link to a contact |
| Job role: "Job role", "Current job role" | Professional network, Full portrait | short text |
| Organisation | Professional network, Full portrait | short text |
| Hobbies | Friends and family, Full portrait | multiple choice |
| Favourite dishes, Favourite drinks, Dietary preferences | Friends and family, Full portrait | long text, one concept each |
| Gift ideas (idea, link, note) | Friends and family, Full portrait | repeating group |

Every other field is a concept of its own. Outing ideas share the shape of gift ideas but not their identity, so an outing idea never turns into a gift idea.

Three values cannot follow, because the shape changes and not only the place: a work address (one address) and the list of addresses; a website (one link) and the list of websites and online profiles in Full portrait; the long "Context" of Full portrait and the short "Place or context" of the others. They are set aside as today, kept, and come back when the contact returns to a template that holds them.

## Clarifications

### Session 2026-11-18

- Q: Should existing accounts keep Basic contact as their vault default, or switch to Full portrait like new accounts? → A: Basic contact is kept everywhere, unchanged, and stays the default contact template in every account, new ones included. This replaces the earlier request to stop supplying it to new accounts. The four new templates are added beside it, and people pick one themselves.
- Q: How should a contact's values follow it when it moves to another template, given that today a value follows only a field of the same section, key and type? → A: A key dictionary across the five supplied contact templates (one key per concept, same key and type for the same concept), plus a fallback: when no field of the same section matches, the value follows the one field of the new template with the same key and type, wherever it sits; fields inside a repeating group follow their group.
- Q: Are the proposed English source names accepted (Essentials, Friends and family, Professional network, Full portrait)? → A: Yes.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A new account starts with the four templates (Priority: P1)

Somebody creates an account. When they open their templates, they find Basic contact and Basic animal as today, plus Essentials, Friends and family, Professional network and Full portrait, each laid out exactly as described above. Their first vault still uses Basic contact as the template for a new contact, and the contact standing for themselves is still written on it. They can pick one of the four new templates whenever they add a contact.

**Why this priority**: This is the change of product: what every new person can pick from when they describe somebody.

**Independent Test**: Create an account, list its templates, open each new one and compare pages, sections and fields to the tables above; add a contact in the first vault without choosing and check it uses Basic contact; add one choosing Full portrait and check it uses Full portrait.

**Acceptance Scenarios**:

1. **Given** nobody, **When** an account is created, **Then** its templates are Basic contact, Essentials, Friends and family, Professional network, Full portrait and Basic animal.
2. **Given** a new account, **When** its owner opens Friends and family, **Then** it shows two pages, Everyday and Tastes and attentions, with the sections and fields of the table, in that order.
3. **Given** a new account, **When** its owner adds a contact to the first vault without choosing a template, **Then** the contact uses Basic contact.
4. **Given** a new account, **When** its owner adds a contact and picks Professional network, **Then** the contact uses Professional network.
5. **Given** a new account whose owner reads the application in French, **When** they open a supplied template, **Then** every page, section and field reads with the French wording of the tables.
6. **Given** a new or existing account, **When** its owner creates a vault, **Then** that vault's default contact template is Basic contact, never one of the four new templates.

---

### User Story 2 - Existing accounts receive the four templates (Priority: P1)

Somebody whose account existed before this change opens their templates after the release. The four new templates are there next to what they already had. Basic contact, their own templates and every contact already written are unchanged, and each vault keeps the default it had.

**Why this priority**: Without it, existing customers never see the new templates, and the product has two populations with different starting points.

**Independent Test**: Take an account created before the change, with contacts on Basic contact and a template of its own; run the backfill; check that the four templates were added and nothing else changed.

**Acceptance Scenarios**:

1. **Given** an existing account with Basic contact, **When** the backfill runs, **Then** the account holds Basic contact plus the four new templates.
2. **Given** an existing account, **When** the backfill runs, **Then** every vault default, every existing template and every contact's values are exactly as before.
3. **Given** an account that already received the four templates, **When** the backfill runs again, **Then** nothing is added a second time.
4. **Given** an existing account that had deleted a supplied template, **When** the backfill runs, **Then** that deleted template is not brought back, and the four new ones are added.

---

### User Story 3 - Contacts on a new template behave like contacts on Basic contact (Priority: P2)

Somebody fills a contact on one of the new templates. The birthday drives the age shown and the birthday reminder, emails and phones are recognised when the application looks for duplicates, and the contact synchronises with a phone address book the same way a Basic contact one does.

**Why this priority**: The new templates must not quietly lose behaviour people rely on today.

**Independent Test**: Fill birthday, email, phone and address on a contact using each new template; check age, reminder, duplicate detection and the address book export.

**Acceptance Scenarios**:

1. **Given** a contact on Essentials with a birthday, **When** the vault lists upcoming dates, **Then** the birthday appears as a birthday, with the contact's age.
2. **Given** two contacts on Full portrait sharing an email, **When** the application looks for duplicates, **Then** it proposes them as likely the same person.
3. **Given** a contact on Professional network with a work address, **When** it is synchronised with an address book, **Then** the address reaches the address book.
4. **Given** a contact on Basic contact with a birthday, two emails, a phone, an address and a note, **When** it is moved to Essentials, **Then** every one of those values shows on Essentials and the birthday reminder still fires.
5. **Given** a contact on Friends and family with pronouns, hobbies, favourite dishes and two gift ideas, **When** it is moved to Full portrait, **Then** all of them show on Full portrait, and the gift ideas are under Gift ideas, not under Outing ideas.
6. **Given** a contact on Essentials with a website, **When** it is moved to Full portrait and back, **Then** the website is set aside on Full portrait and shows again on Essentials.

---

### User Story 4 - The new choice lists are there and editable (Priority: P3)

Somebody fills Hobbies, Industry, Areas of expertise or Preferred way to reach them and picks from a list that already holds sensible choices, which they can edit like every other value list.

**Why this priority**: The fields work without it only if the person writes every choice first, which nobody will.

**Independent Test**: Open the value lists of a new account and of a backfilled one; check that the four new lists exist with their choices and can be edited.

**Acceptance Scenarios**:

1. **Given** a new or backfilled account, **When** its owner opens the value lists, **Then** Hobbies, Communication channels and Industries are listed with their supplied choices, and Areas of expertise is listed empty.
2. **Given** an existing account that already has a list of its own with the same name, **When** the backfill runs, **Then** the account's list is left alone and the supplied one is added beside it.

### Edge Cases

- An existing account already has a template or a section of its own with the same name as a supplied one: both stay, and the person can tell them apart only by name; nothing of theirs is renamed or merged.
- The backfill stops halfway through an account: running it again finishes that account without duplicating what it already wrote.
- An account with no vault: the templates are still added; no vault default is touched.
- An end date earlier than the start date in Experience or Education is accepted, as dates are today; the person's input is not refused.
- Experience or Education entry still in progress: the end date stays empty.
- A new account created while the backfill runs gets the four templates once, from account creation, and the backfill skips it.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The application MUST supply four contact templates, Essentials, Friends and family, Professional network and Full portrait, each with exactly the pages, sections, fields, field kinds and order of the tables in this document.
- **FR-002**: Each supplied template MUST carry the description given above.
- **FR-003**: Every name of a supplied template, page, section, field and choice MUST be translated in every shipped language, with the French wording of this document as the French translation, and MUST appear in the reader's language.
- **FR-004**: A new account MUST receive Basic contact and Basic animal exactly as today, plus the four templates.
- **FR-005**: Basic contact MUST stay the default contact template of every vault, in new and existing accounts, including vaults created after the first one, and the contact standing for the user MUST still be written on Basic contact. None of the four templates becomes a vault default on its own; a person makes one the default only by choosing it.
- **FR-006**: Existing accounts MUST receive the four templates through a one-off backfill that runs once in production.
- **FR-007**: The backfill MUST NOT change, remove or replace Basic contact, any template the person made or imported, any vault default, or any contact value.
- **FR-008**: The backfill MUST be safe to run again: a template already supplied, or supplied then deleted, MUST NOT be added a second time.
- **FR-009**: A birthday field on the new templates MUST drive the age shown and the birthday reminder exactly as the birthday of Basic contact does.
- **FR-010**: Emails and phones on the new templates MUST count for duplicate detection as they do on Basic contact.
- **FR-011**: Emails, phones, addresses, the work address, websites and birthdays on the new templates MUST synchronise with address books as the matching fields of Basic contact do.
- **FR-012**: Emails, phones and addresses MUST keep the labels people pick today (home, work, and so on).
- **FR-013**: The application MUST supply four new value lists to every new account and, through the backfill, to every existing one: Hobbies, Communication channels, Industries and Areas of expertise, each editable like the existing lists.
- **FR-014**: Industries MUST start with a supplied list of about fifteen common industries. Areas of expertise MUST start empty, for the person to fill, since expertise is too personal to guess.
- **FR-015**: Hobbies MUST start with a supplied list of common hobbies, and Communication channels with: phone call, text message, email, instant messaging, video call, in person, post.
- **FR-016**: Languages spoken and Languages to use MUST pick from the existing Languages list; Categories from the existing contact categories list; Gender from the existing genders list.
- **FR-017**: A file field MUST accept the same documents as the Documents section of Basic contact, and an image field the same pictures as its Photos section.
- **FR-018**: Introduced by MUST link to one contact, under the same rules as every other link to a contact.
- **FR-019**: Supplying the templates MUST be written in the account's activity log as supplying Basic contact is today.
- **FR-020**: The product changelog MUST tell people the four templates exist, including existing customers who now have them.
- **FR-021**: Each concept of the Shared concepts table MUST carry the same identity and kind in every supplied contact template where it appears, and Basic contact's existing identities MUST be the ones reused. Every other field MUST carry an identity no other concept uses, including fields inside repeating groups.
- **FR-022**: When a contact moves to another template, a value MUST first follow, as today, the field of the same section with the same identity and kind. When there is none, it MUST follow the one field of the new template with the same identity and kind, wherever that field sits. A field inside a repeating group MUST follow only the field of the same identity inside the group its parent followed to.
- **FR-023**: When the new template holds more than one candidate field for a value, or none, the value MUST be set aside as today, never guessed.
- **FR-024**: The fallback of FR-022 MUST apply to every template, not only the supplied ones, and MUST leave unchanged every move that already succeeds today.

### Key Entities

- **Supplied template**: one of the four templates, marked as supplied by the application, identified by a stable name that does not depend on the language, so that the backfill can tell whether an account already has it.
- **Field identity**: the stable, language independent name of a field, together with its kind. It is what a value is matched on when a contact changes template, and what birthdays, duplicate detection and address book synchronisation already look for. The label a person reads is not part of it.
- **Section**: a block of fields. Each of the four templates owns its sections, so editing a section in one template never changes another.
- **Value list**: the four new lists and the existing ones they sit beside, each owned by one account.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of accounts created after the release hold Basic contact, the four templates and Basic animal, and 100% of their vaults default to Basic contact.
- **SC-002**: 100% of accounts that existed before the release hold the four templates after the backfill, and 0 of them lost Basic contact or saw a vault default or contact value change.
- **SC-003**: Running the backfill a second time adds 0 templates and 0 value lists.
- **SC-004**: For each of the four templates, the pages, sections and fields an owner sees match the tables of this document one for one, in English and in French.
- **SC-005**: A birthday, an email and a phone written on any of the four templates produce the same age, reminder, duplicate suggestion and address book entry as on Basic contact.
- **SC-006**: Creating an account takes no noticeably longer than it does today from the person's point of view.
- **SC-007**: Moving a contact between any two of the five supplied contact templates keeps visible 100% of the values whose concept both templates hold, and sets aside, without deleting, 100% of the others.

## Assumptions

- The English names of the four templates were accepted on 2026-11-18; the English names of pages, sections and fields follow the same literal approach. The English names are the source strings of the application, so they will be the ones every other language is translated from.
- "Migration for existing customers" is read as a one-off backfill over every account, which supplies the templates through the same path account creation uses. It is not a schema change to an existing migration, which the project never edits.
- Basic contact and Basic animal are untouched, for new and existing accounts.
- Every account keeps Basic contact as its vault default; nothing pushes anybody to a new template.
- A template supplied then deleted by the person stays deleted; the backfill respects that choice, as account creation already does for Basic contact.
- No field of the four templates is required: a contact can be saved with any of them empty.
- The fields that today drive birthdays, duplicate detection and address book synchronisation keep their meaning by keeping the identifiers the application relies on, so that Basic contact and the new templates behave alike.
- Highlighted fields (shown at the top of a contact) follow Basic contact: Categories and Birthday, where a template has them.
- Notes are listed newest first, and emails, phones, addresses and the other repeating groups in the order they were written, as on Basic contact.
- The help centre page about templates is updated to describe the four supplied templates.
