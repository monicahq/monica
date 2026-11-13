# Feature Specification: Vault-wide reminder occurrences over the API and the MCP server

**Feature Branch**: `2026-11-13-bug-fixes`

**Created**: 2026-11-13

**Status**: Draft

**Input**: User description: "Vault-wide reminder occurrences over the API and the MCP server, with a filter for birthdays. An assistant connected over MCP must answer 'whose birthday is it today, and whose is coming up?' in one call, the way the vault dashboard answers it on the web. A category filter (overdue, today, upcoming, history), a kind filter (every reminder, or birthdays only), a horizon for upcoming defaulting to seven days. Each occurrence names the contact or group, the reminder, the date, the category, whether it was answered, and for a birthday the age being reached. Same rows as the dashboard. Paginated, same API key scope as the per contact endpoint, documented, translated. Settle whether a birthday creates a reminder by itself."

## Context

The web app shows the dates of a whole vault in two places: the vault dashboard (Overdue, Today, Next seven days) and the vault reminders screen (upcoming and history). Both read the same source, the vault's deadlines. The API and the MCP server only list occurrences for one contact or one group at a time. Principle X of the constitution requires everything doable in the interface to be doable through the API, and the MCP server to expose the API without bypassing it, so this is a gap in coverage as much as a convenience.

### What holds today (settled from the code)

- **A birthday does not create a reminder by itself.** A reminder on a date field is created only when somebody ticks "Remind me about this date" beside it. The box is unticked for a date with no reminder, and saving a birthday without ticking it creates nothing. A birthday with no reminder therefore appears nowhere: not on the dashboard, not on the reminders screen, not through any endpoint.
- **Group reminders are part of the vault's deadlines.** The vault deadlines read every active reminder of the vault whatever it is attached to: a contact, a group, or a record of a custom type (an entity). The dashboard lists all three.
- **What the dashboard lists**: every occurrence due by today that nobody has answered (overdue and today), then the next occurrence of each active reminder up to the last day of the horizon. One upcoming row per reminder, not every repetition.

## Clarifications

### Session 2026-11-13

- Q: Does the birthdays kind also return birthdays that carry no reminder? → A: Yes. It lists every birthday of the vault falling in the window, with or without a reminder; one without a reminder carries nothing to answer.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ask an assistant what is due in a vault (Priority: P1)

Jim connects an assistant to his account over MCP and asks "what do I have to remember today and this week in my Dunder Mifflin vault?". The assistant calls one tool and answers with the overdue items, today's items and the next seven days, naming each person or group concerned.

**Why this priority**: This is the gap itself. Without it the assistant has to visit every contact one by one, which fails beyond a few dozen people.

**Independent Test**: Create a vault holding contacts and a group with reminders falling yesterday (unanswered), today and in three days, call the endpoint, and check the three come back in the right categories with their subjects named, in one response.

**Acceptance Scenarios**:

1. **Given** a vault with an unanswered reminder due yesterday, one due today and one in three days, **When** the user asks for the vault's occurrences with no filter, **Then** all three are returned, categorised overdue, today and upcoming, each naming the contact or group it concerns.
2. **Given** the same vault, **When** the user asks for the category today, **Then** only today's occurrence is returned.
3. **Given** a reminder attached to a group, **When** the user asks for the vault's occurrences, **Then** the group's occurrence is returned, naming the group.
4. **Given** the same user, vault and day, **When** the dashboard and the endpoint are both asked for the next seven days, **Then** they list the same occurrences.

---

### User Story 2 - Ask only for birthdays (Priority: P1)

Pam asks the assistant "whose birthday is it today, and whose is coming up?". The assistant asks for birthdays only and answers "Michael Scott turns 45 on Friday" without a second call.

**Why this priority**: This is the question that started the work, and it is the one most often asked of a personal relationship manager.

**Independent Test**: Give two contacts a birthday with a reminder (one with a year of birth, one without) and give a third contact an unrelated reminder, ask for birthdays only, and check that the two birthdays come back, the first with the age being reached and the second with none, and that the unrelated reminder does not.

**Acceptance Scenarios**:

1. **Given** a contact whose birthday carries a reminder and falls in five days, and whose year of birth is known, **When** the user asks for birthdays only, **Then** the occurrence is returned with the contact's name, the date and the age the contact reaches that day.
2. **Given** a contact whose birthday carries a reminder but whose year of birth is unknown, **When** the user asks for birthdays only, **Then** the occurrence is returned with no age.
3. **Given** a contact with a reminder that does not read the birthday field, **When** the user asks for birthdays only, **Then** that reminder is not returned.
4. **Given** a contact with a birthday and no reminder on it, falling in three days, **When** the user asks for birthdays only, **Then** the birthday is returned with the contact's name, the date and the age, and is marked as carrying no reminder.
5. **Given** a contact whose birthday carries a reminder, **When** the user asks for birthdays only, **Then** the birthday is returned once, as the reminder's occurrence, never a second time as a bare date.
6. **Given** a contact with a birthday and no reminder on it, **When** the user asks for every reminder (the default kind), **Then** the birthday is not returned.

---

### User Story 3 - Look further ahead or back (Priority: P2)

Dwight asks for everything due in the next thirty days, then for what was answered recently.

**Why this priority**: Useful for planning, but the default seven days answers the common question.

**Independent Test**: Place a reminder twenty days ahead, ask with the default horizon (not returned), then with a thirty day horizon (returned). Answer an occurrence and ask for history (returned).

**Acceptance Scenarios**:

1. **Given** a reminder falling in twenty days, **When** the user asks for upcoming with no horizon, **Then** it is not returned.
2. **Given** the same reminder, **When** the user asks for upcoming with a thirty day horizon, **Then** it is returned.
3. **Given** an occurrence answered yesterday, **When** the user asks for history, **Then** it is returned with its answer and when it was answered, most recently answered first.

---

### User Story 4 - Developers integrating the API (Priority: P3)

A developer reads the public API reference and the MCP documentation and finds the new endpoint and tool described with their filters, their response and an example.

**Why this priority**: Required by the constitution for every API addition, but it does not change what the product can do.

**Independent Test**: Open the API reference and the MCP documentation and find the endpoint and the tool with every filter described.

**Acceptance Scenarios**:

1. **Given** the public API reference, **When** a developer looks for vault reminder occurrences, **Then** the endpoint, its filters, its pagination and an example response are documented.

---

### Edge Cases

- **A vault of another account**: answers not found, exactly as for every other vault scoped endpoint; nothing of it is revealed.
- **A vault with no reminders**: an empty list, not an error.
- **An unknown category, kind or horizon**: refused as a validation error naming the field.
- **A horizon of zero, a negative horizon, or one longer than the maximum**: refused as a validation error.
- **The user's day differs from the server's**: today, overdue and upcoming are worked out in the timezone of the user asking, so a user in Tokyo and one in Scranton can get different categories for the same occurrence at the same moment.
- **A reminder that has been deactivated**: its future occurrences are not listed, as on the dashboard; its answered occurrences still appear in history.
- **A birthday on 29 February**: follows whatever date the existing reminder logic already gives it in a year that is not a leap year, with or without a reminder; this feature does not change it.
- **A birthday with no reminder that fell yesterday**: not listed. Only a reminder can be overdue.
- **A contact with a birthday whose reminder was deactivated**: listed by the birthdays kind as a bare birthday, since no active reminder covers it.
- **A contact whose birthday is today and whose year of birth is known**: the age given is the age reached today.
- **An archived or deleted contact**: follows what the dashboard does for it today; this feature does not change it.
- **A reminder on a record of a custom type (an entity)**: returned like any other, naming the record; it never carries an age.
- **A key without the scope reminders require**: refused, as for the per contact endpoint.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The API MUST offer one endpoint listing the reminder occurrences of a whole vault.
- **FR-002**: The MCP server MUST offer one tool that exposes exactly that endpoint: the same filters, the same validation, the same response, and the same refusals.
- **FR-003**: The endpoint MUST accept a category among overdue, today, upcoming and history, the same values the per contact endpoint already accepts. With no category it MUST return overdue, today and upcoming together, as the dashboard does, and leave history out.
- **FR-004**: Categories MUST be worked out in the timezone of the user asking.
- **FR-005**: The endpoint MUST accept a kind: every reminder of the vault (the default), or birthdays only, meaning reminders that read the default Birthday field.
- **FR-006**: The endpoint MUST accept a horizon in days for upcoming occurrences, defaulting to seven, with a maximum of 365.
- **FR-007**: For the same user, vault, day and horizon, the overdue, today and upcoming occurrences of the default kind MUST be the same ones the vault dashboard lists, worked out from the same source rather than from a second query. With the birthdays kind, the occurrences of birthday reminders MUST be that same set narrowed to birthdays; the birthdays added by FR-018 are the only difference.
- **FR-008**: History MUST list answered occurrences, most recently answered first, as the vault reminders history screen does.
- **FR-009**: Each occurrence MUST state: its identifier; the reminder it belongs to and its name; the contact, group or record it concerns, with its type, name and address; the date it falls on; its category; and whether and how it was answered and when.
- **FR-010**: An occurrence of a birthday reminder MUST state the age the contact reaches on that date when the year of birth is known, and state no age when it is not. Any other occurrence MUST state no age.
- **FR-011**: Occurrences MUST be ordered by date, earliest first, except history (FR-008).
- **FR-012**: The endpoint MUST be paginated the way the other list endpoints are, with the same parameters and the same metadata.
- **FR-013**: The endpoint MUST require the same API key scope as the per contact reminder occurrence endpoint.
- **FR-014**: A vault outside the user's account MUST answer not found. Occurrences of another account or another vault MUST never be listed.
- **FR-015**: Each occurrence MUST carry links to answer it through the existing answer endpoints. This feature MUST NOT add a way to answer or snooze.
- **FR-016**: The public API reference and the MCP documentation MUST describe the endpoint and the tool in the same change, and the API changelog MUST record the addition.
- **FR-017**: Every new string a reader sees MUST be translated in every configured locale.
- **FR-018**: The birthdays kind MUST also return the birthdays of the vault's contacts that carry no reminder, falling today or within the horizon. Such an entry MUST state that it carries no reminder, MUST carry no occurrence identifier, no answer and no link to answer, and is categorised today or upcoming only: it is never overdue and never part of history, since there is nothing to answer.
- **FR-019**: A birthday that carries a reminder MUST appear once, as the reminder's occurrence, and never also as a bare date.
- **FR-020**: The default kind MUST NOT return birthdays that carry no reminder, so it stays identical to the dashboard.

### Key Entities

- **Reminder**: a rule asking to be reminded of a date, attached to a contact, a group or a record of a custom type. It either carries its own date or reads a date field, such as Birthday.
- **Reminder occurrence**: one dated instance of a reminder, which can be pending, answered or cancelled.
- **Vault deadlines**: the set the dashboard and the reminders screen show: what is due and unanswered by today, then the next occurrence of each active reminder.
- **Birthday**: the default recurring date field on a contact. It can carry a year or not, and it carries a reminder only when somebody asked for one.
- **Bare birthday**: a birthday falling in the window that carries no reminder. Listed by the birthdays kind only, with nothing to answer.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An assistant answers "whose birthday is it today and this week?" for a vault with one tool call, whatever the number of contacts in the vault.
- **SC-002**: For the same user, vault and day, the endpoint with the default kind and the dashboard list the same occurrences in 100% of the cases covered by the tests.
- **SC-006**: Asked for birthdays only, the endpoint returns every contact of the vault whose birthday falls in the window, whether or not anybody asked to be reminded of it.
- **SC-003**: The answer for a vault of 1,000 contacts comes back as fast as the vault dashboard loads for the same vault.
- **SC-004**: No occurrence from another account or another vault is ever returned, proven by a test per refusal.
- **SC-005**: A developer finds the endpoint, every filter and an example response in the public API reference without reading the code.

## Assumptions

- The category values reuse the per contact endpoint's, so an assistant that already knows one knows the other.
- "Birthdays only" means reminders whose field is the default Birthday field of the account. A date field the user added and called "Birthday" is not included.
- The age is worked out the way the contact screen already works out ages.
- A bare birthday falls on the next anniversary of the date written in the field, worked out in the timezone of the user asking, the same way a yearly reminder on that field would place it.
- A birthday written without a day (a year or a month alone), if the field allows it, has no date to fall on and is not listed.
- The horizon applies to upcoming occurrences only. Overdue has no lower bound, as on the dashboard.
- One upcoming occurrence per reminder within the horizon, as on the dashboard, not every repetition of a reminder that recurs more often than the horizon.
- The response reuses the existing reminder occurrence representation, widened with the subject and the age, so the per contact and per group endpoints also gain the subject without losing anything.
- Out of scope: changing the web dashboard, answering or snoozing from the new endpoint, any list spanning several vaults, and the vault reminders screens grouped by contact or listing sent emails.
