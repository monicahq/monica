# Feature Specification: Groups

**Feature Branch**: `2026-10-29-groups` | **Created**: 2026-10-29 | **Status**: Draft

**Input**: "Add groups", then "un groupe doit être sérialisé en vCard avec une carte qui a KIND=group."

## Context

Group is one of the nine fixed concepts (constitution VII, spec 3.2 and 8.2): a set of contacts that exists apart from their relationships, such as a household, a couple, a family, a team or a circle of friends. Nothing implements it yet. The entities, their fields and their rules are already designed in [032 Group](../../spec/models/032-group-not-yet.md) and [033 GroupMember](../../spec/models/033-group-member-not-yet.md), and are not repeated here. [031 GroupType](../../spec/models/031-group-type-not-yet.md) is not built: a group has no kind.

## Clarifications

### Session 2026-10-29

- Q: Which meaning keeps the word "group", now that contacts get groups? → A: Contact groups keep "group"; the sets of fields in the life event type editor become "block", and the glossary row moves with them.
- Q: What kind does a group get when it is created on a phone? → A: Groups don't have a kind. There are no group kinds at all, in the app, the API or a device.
- Q: When two merged contacts are both current members of one group, what happens? → A: The memberships become one, with the earlier join date and the kept contact's role unless empty; the preview shows it and undo restores both.
- Q: Should the `CATEGORIES` property on contact cards also count as group membership? → A: No. Group cards are the only form; the CardDAV setup documentation tells DAVx5 users to store groups as separate vCards.
- Q: How should the group page and the vault's group list behave when they get long? → A: Both are paginated with the app's usual page size and "Load more"; the API and MCP paginate the same way.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Create a group and put people in it (Priority: P1)

"Jim, Pam and Cece are the Halpert household." The user creates a group in a vault, names it, and adds contacts of that vault to it. The group has its own page listing its members.

**Independent Test**: create the Halpert household, add three contacts, open the group page.

1. **Given** a vault, **When** the user creates a group with a name, **Then** it exists there with no members.
2. **Given** a group, **When** the user adds Jim, Pam and the vault's Me contact, **Then** all three are current members.
3. **Given** a contact of another vault, or one already a current member, **When** added, **Then** it is refused.

### User Story 2 - See somebody's groups on their page (Priority: P1)

"Which groups is Dwight in?" A contact's page lists the groups they belong to, current first.

**Independent Test**: open a contact in two groups and in a group they left; see all three.

1. **Given** a contact in the Schrute farm household and the Scranton team, **Then** their page lists both with their role.
2. **Given** a contact in no group, **Then** the block says so in one sentence with a way to add one.

### User Story 3 - Record when somebody joined and left (Priority: P2)

"Ryan left the Scranton team in 2007 and came back in 2009." A membership carries a join date and a leave date, each as precise as the user knows, and an optional role.

**Independent Test**: record a departure, then a return, and see two memberships, one current.

1. **Given** a current member, **When** they leave in 2007, **Then** the membership ends with year precision.
2. **Given** a former member, **When** they rejoin, **Then** a second membership starts and the first is unchanged.
3. **Given** a leave date before the join date, **Then** it is refused.

### User Story 4 - Keep groups in step with a phone (Priority: P2)

Pam's phone syncs the vault's address book. The Halpert household appears there as a group card listing its current members, and what Pam changes on the phone comes back.

**Independent Test**: create a group in the app, see it on a synced device, change it there, see the app.

1. **Given** a group with three current members, **When** a device syncs, **Then** it receives one group card naming the group and those three.
2. **Given** a group created on the device with two contacts of the vault, **When** it syncs, **Then** the group exists in the app with two current members and no contact was created for it.
3. **Given** a member removed on the device, **When** it syncs, **Then** that membership ends that day and keeps its role and history.
4. **Given** a group card deleted on the device, **When** it syncs, **Then** the group is deleted as from the app, and can be restored.

### Edge Cases

- A deleted contact's memberships disappear with it, from group cards too, and come back when it is restored.
- Deleting a vault deletes its groups with it.
- A membership outside the group's own dates is accepted with a warning, not refused.
- A member a group card names but the vault does not hold is ignored; the rest of the card is applied.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Users MUST be able to create, rename, describe, date, delete and restore a group in a vault.
- **FR-002**: A group MUST hold contacts of its own vault only, including the vault's Me contact, and never an entity.
- **FR-003**: A contact MUST be able to belong to any number of groups.
- **FR-004**: A membership MUST carry an optional role in free text and optional join and leave dates, each with its precision.
- **FR-005**: A contact MUST hold at most one current membership of a given group; a return is a new membership.
- **FR-006**: The group page MUST list current members, then former members with their periods, paginated with the app's usual page size and "Load more", as the API and MCP lists are.
- **FR-007**: The contact page MUST list the contact's groups, current first, with the contact's role in each.
- **FR-008**: The vault MUST have a list of its groups, paginated as in FR-006.
- **FR-009**: Joining and leaving MUST appear on the contact's timeline.
- **FR-010**: Every change to a group or a membership MUST be written to the changelog.
- **FR-011**: A group MUST carry a name, a description and its own dates, and no kind, template or field of its own.
- **FR-012**: Deleting a group MUST warn first and leave its members untouched; restoring it MUST bring its memberships back.
- **FR-013**: Every capability above MUST be available through the API, documented, and mirrored by MCP tools.
- **FR-014**: An account export MUST carry groups and memberships, and importing it MUST restore them.
- **FR-015**: A share link MUST leave group memberships out unless the user switches them on for that link.
- **FR-016**: Every screen MUST meet WCAG 2.2 AA, including adding members with the keyboard alone.
- **FR-017**: The vault's address book MUST serve each group as a vCard with `KIND:group`, its name as `FN`, and one `MEMBER` per current member.
- **FR-018**: Each group card MUST also carry the Apple form, `X-ADDRESSBOOKSERVER-KIND:group` and `X-ADDRESSBOOKSERVER-MEMBER`, and both forms MUST be read.
- **FR-019**: A group card a device sends MUST create or update a group and MUST NOT create a contact; `CATEGORIES` on a contact card MUST NOT create or change a membership, and the CardDAV setup documentation MUST tell DAVx5 users to store groups as separate vCards.
- **FR-020**: A member a device adds MUST start a membership that day; one it removes MUST end that membership that day.
- **FR-021**: A device MUST NOT change what a card cannot carry: description, dates, roles and former members.
- **FR-022**: The word "group" MUST name a group of contacts only; a set of fields in the life event type editor MUST be called a "block" in every configured locale.
- **FR-023**: Merging two contacts MUST move the absorbed contact's memberships to the kept one; where both are current members of one group, the two MUST become one membership with the earlier join date and the kept contact's role unless it is empty, shown in the merge preview and restored by undoing the merge.

## Success Criteria *(mandatory)*

- **SC-001**: A user creates a household with three members in under one minute.
- **SC-002**: Every web operation on groups has an API endpoint and an MCP tool, with no exception.
- **SC-003**: An account exported then imported holds the same groups, members and periods.
- **SC-004**: A group changed on a synced device matches in the app after one sync, with no duplicate contact.

## Assumptions

- Groups stay out of sight until every story above is complete, then are released to every account at once.
- The role is free text; a group cannot contain another group, and has no photo of its own.
- A shared link that includes memberships shows the group's name, never its other members.
