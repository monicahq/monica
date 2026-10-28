# Feature Specification: Account export and import

**Feature Branch**: `2026-10-18-account-export-import`
**Created**: 2026-10-18
**Status**: Draft
**Input**: "Add an export/import process. This is an example file of an export from a previous version of the application." The example is a Monica 4 export, format `1.0-preview.1`, written by Monica 3.6.1.

## Context

Somebody leaving Monica 4 holds a JSON file Monica cannot read, and somebody using Monica can only take data out one contact at a time, as a vCard. This feature adds both directions: an Monica export of one vault or of all of them, and an import that reads that export and the Monica 4 file. The example holds one account: 1,767 contacts, 2,302 emails and phones, 220 notes, 118 reminders, 94 relationships, 58 addresses, 27 activities, 4 debts, 2 tasks, 1 gift, 22 photos and documents embedded in the file (18 MB of its 27 MB), 4 journal entries and 3,327 audit log lines. Monica 4 can also export calls, conversations and pets, which the example happens not to use.

| Monica 4 | Monica |
| --- | --- |
| Name: first, middle, last, nickname | The structured name. A contact with no name at all is an unnamed contact |
| Gender, birthdate, description | The Gender, Birthday and Description fields. A gender Monica does not know becomes Other |
| Emails, phones, addresses, notes, photos, documents | The fields of the same name. The avatar photo becomes the contact's avatar |
| Facebook, Twitter, WhatsApp, Telegram, LinkedIn and custom kinds | The Website field when the value is an address, otherwise a note naming its kind |
| Starred, inactive, partial | Favourite, archived, partial contact |
| Relationships | Relationships of the Monica type with the same meaning. A type with none (godfather) is created as the account's own |
| Life events, deceased date, first met date | Life events of the Monica nature with the same meaning. A nature with none (first kiss) is created as the account's own |
| Activities, calls, conversations, journal entries | Life events of the Time together, Call, Message and Reflection natures, with their contacts, or Me for a journal entry |
| Reminders, stay in touch | Reminders on the contact, keeping their cadence. A stay in touch frequency in days rounds to whole weeks |
| Pets | Animals of the Basic animal template, linked to their contact |
| Gifts, debts, tasks, tags, job, company, food preferences, day ratings | A note on the contact, headed by what it was (Debt, Tag). Day ratings become notes on Me |
| Audit log, modules, reminder rules, address books, CardDAV subscriptions, other users, view counters, user preferences, the two factor secret | Not imported, counted in the report. Contacts synchronised over CardDAV arrive as ordinary contacts |

## Clarifications

### Session 2026-10-18

- Q: What does an import do with Monica 4 data Monica has no equivalent for (gifts, debts, tasks, tags, job, company, food preferences, day ratings)? → A: Each becomes a note on its contact, headed by what it was; day ratings become notes on Me.
- Q: What does an import do with a record the target vault already holds? → A: Nothing. An import only adds records and never changes or removes an existing one; a backup is restored into a new or empty vault.
- Q: What happens when a file would take a hosted account over its vault, contact or storage cap? → A: The import is refused before anything is written, naming the cap, the numbers in the file and where to get full access.
- Q: When the contact chosen as Me disagrees with the vault's Me, which values win? → A: Me keeps every value it has; imported values only fill its empty fields and add to its repeated ones, and the imported relationships and life events attach to Me.
- Q: Which template does a Monica 4 contact take, since the file carries none? → A: The vault's default template, values matched to fields by key; a value with no matching field becomes a note naming it.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Move from Monica 4 to Monica (Priority: P1)

Michael exported his account from Monica 4 and opens Import in Monica. He picks the file. Monica reads it and shows what it holds and what will not come over. He chooses a new vault, confirms that the contact the file names as him is Me, and starts. The import runs while he does something else, he is told when it ends, and its report accounts for every item of the file.

**Why this priority**: every Monica 4 user who moves needs it, and without it they start from nothing.

**Independent Test**: import the example file into a new vault, then compare the report and a sample of contact pages with the file.

**Acceptance Scenarios**:

1. **Given** the example file, **When** Michael picks it, **Then** he sees 1,767 contacts, 94 relationships, 22 files and the other kinds counted, and what will not come over, before anything is written.
2. **Given** he confirms Me, **When** the import ends, **Then** the vault's Me contact carries that contact's fields, relationships and life events, and no second Me exists.

### User Story 2 - Take my data out of Monica (Priority: P2)

Pam exports her Family vault. She is warned that the file holds everything in it, sensitive fields included, unencrypted. The file is prepared while she does something else, she is told when it is ready, and she downloads it.

**Why this priority**: data a person cannot take out is data held hostage, and the export is what the third story reads.

**Independent Test**: export a vault holding contacts, entities, relationships, life events, reminders and files, then check every one is in the file.

**Acceptance Scenarios**:

1. **Given** Pam chooses one vault or all of them, **When** she confirms the warning, **Then** an export is prepared and she is told when it is ready.

### User Story 3 - Bring an Monica export into another instance (Priority: P3)

Jim moves from the hosted service to his own server. He imports an Monica export into an empty vault there, and finds the same contacts, values, relationships, life events, reminders and files, laid out by the same templates.

**Why this priority**: it completes the round trip, and it is what a backup is for.

**Independent Test**: export a vault, import the file into an empty vault on a second instance, and compare every contact page.

**Acceptance Scenarios**:

1. **Given** the vault used a marketplace template, **When** imported, **Then** the template arrives as Jim's own copy, at the version the records were on.
2. **Given** the file holds records the target vault already holds, **When** imported, **Then** they arrive as new records beside the existing ones, which do not change, and the Duplicates screen lists the pairs.

### Edge Cases

- A relationship, activity or reminder naming a contact the file does not hold, and an embedded file that cannot be read, are left out and reported, and the rest goes on.
- An import whose writing or rollback has not moved for a day, because the worker running it stopped between two runs, is rolled back by the daily purge, so it neither stays half written nor keeps the account from starting another import.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Somebody MUST be able to export one vault or every vault of their account, and import a Monica 4 file or an Monica export, from the web and through the API.
- **FR-002**: Before writing anything, an import MUST read the whole file and show what it holds per kind and what will not come over and why, and MUST refuse a file that is unrecognised, damaged, of a format version this instance does not know, or would cross a cap of the account, naming the cap, the numbers and where to get full access.
- **FR-003**: An import MUST land in a vault the person chooses, a new one or one of theirs; an Monica export holding several vaults MUST land in one new vault per vault of the file, each counted against the vault cap.
- **FR-004**: An import MUST ask which imported contact is Me, preselecting the one the file names, and add it to the vault's Me contact: its values fill only Me's empty fields, and its repeated values, relationships and life events are added; choosing none imports it as an ordinary contact. The contact chosen as Me creates no contact, so it MUST NOT count against the contact cap.
- **FR-005**: A Monica 4 contact MUST take the vault's default template, each value placed in the field with the matching key, and a value with no matching field MUST become a note naming it.
- **FR-006**: An import MUST only add, never change or remove a record or a value that existed before it started, and MUST say before starting when the file was already imported into that vault.
- **FR-007**: An import and an export MUST run in the background, one of each at a time per account, show their progress, and tell the person when they end, in the product and by email.
- **FR-008**: An import that fails or is cancelled MUST leave the account as it was before it started.
- **FR-009**: An import MUST NOT store a credential the file carries: password, two factor secret, CardDAV account details.
- **FR-010**: An imported record MUST keep the date it was created in the source, and an imported date the precision it had: an unknown year stays unknown, an approximate date stays approximate.
- **FR-011**: An import MUST end with a report, kept with the account, accounting for every item of the file.
- **FR-012**: Possible duplicates among the imported contacts, and with the vault's own, MUST appear on the Duplicates screen; nothing is merged without the person.
- **FR-013**: An export MUST hold every contact, entity, field value, relationship, life event, reminder and file of the vaults chosen, with the templates, value lists, relationship types and natures they use, and leave out credentials, passkeys, API keys, connected sign in providers, share links, notification channels, billing, the changelog and record snapshots.
- **FR-014**: Before an export starts, the person MUST be warned that the file holds everything in those vaults, sensitive fields included, unencrypted.
- **FR-015**: An export MUST be downloadable only by its account holder, signed in, and MUST be deleted seven days after it is ready. A newer export does not delete an earlier one; a daily purge removes every export past its seven days.
- **FR-016**: Every export, download and import MUST be recorded in the account's changelog, and an imported record's entries MUST name the import as their source.
- **FR-017**: The Monica export format MUST carry its version, and every later Monica MUST import every earlier version.
- **FR-018**: Every step of an export or an import (preparing an export, downloading it, uploading a file, starting and cancelling an import) MUST ask the person to confirm their password first, in a card on the screen they are on, the way the security settings do. The export and import screen itself is readable without it, and refreshes itself while an export is being prepared.
- **FR-019**: A vault's settings MUST offer to export that vault, with the same warning and the same password confirmation as the export and import screen.

### Key Entities

- **Export** is one request to take data out (the vaults chosen, its state, when it was ready and when it expires), and **Import** one run of a file into a vault (the source format, the vault, the contact chosen as Me, its state, its progress and its report of what became of every item).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: The example file imports in under 10 minutes, and its report accounts for all 1,767 contacts, 94 relationships and 22 files.
- **SC-002**: A vault exported and imported into an empty vault of another instance shows no difference on any contact page.
- **SC-003**: An import stopped at any point leaves no contact, value, relationship or file behind.
- **SC-004**: No credential present in an imported file can be found anywhere in the account afterwards.
- **SC-005**: Somebody leaving Monica 4 completes an import by choosing only the file, the vault and Me.

## Assumptions

- **The account holder only**, for their own account. An instance administrator neither imports into nor exports somebody else's account.
- **Size and scope**: the web accepts files of at least 500 MB, and a self hoster can run the same import from the console for a larger file. Imports from other products, scheduled exports and webhook events, which do not exist yet, are out of scope.
