# Feature Specification: Filters on the admin accounts list

**Feature Branch**: `2026-11-19-admin-accounts-filters` | **Created**: 2026-11-19 | **Status**: Draft

**Input**: User description: "make accounts list in admin filterable"

## Clarifications

### Session 2026-11-19

- Q: Which filters should the list offer beside the search by address or name? → A: State, access
  and joined period.
- Q: Should the access filter show on a self hosted instance too? → A: Hosted only, as the billing
  panel of the account page already is.
- Q: Should the list also filter on flags? → A: Yes, a fourth filter keeping the accounts one chosen
  flag is on for.

## User Scenarios & Testing *(mandatory)*

Today the Accounts screen takes one box, an address or a name, and lists every account newest first.
Finding every deactivated account, or every account holding lifetime access, means reading each row.

### User Story 1 - Filter by state (Priority: P1)

Michael picks a state and the list holds only the accounts in it. It answers moderation questions,
and the state is already printed on each row.

**Independent Test**: with five accounts, one deactivated and one deleted, each state lists one row.

1. **Given** Toby's account is deactivated, **When** Michael picks "Deactivated", **Then** the list
   holds Toby's account alone.
2. **Given** "Active", **When** the list renders, **Then** deactivated, deleted and empty accounts
   are left out.
3. **Given** a state no account is in, **When** Michael picks it, **Then** the screen says nothing
   matches the filters and offers to clear them.

### User Story 2 - Filter by the access an account holds (Priority: P2)

Michael picks an access kind to answer billing and support questions on a hosted instance.

**Independent Test**: grant Pam access, give Jim a subscription, leave Dwight on the free plan; each
kind lists one row.

1. **Given** accounts on each access kind, **When** Michael picks one, **Then** only those accounts
   are listed.
2. **Given** a self hosted instance, **When** Michael opens the screen, **Then** the filter is absent.

### User Story 3 - Filter by the flag an account has on (Priority: P2)

Michael switched `groups` on for a few accounts before its release and wants to know which ones,
without opening each account. The Flags screen gives the number, nothing gives the names.

**Independent Test**: `groups` off for every account but on for Jim and Pam lists exactly those two.

1. **Given** `groups` off for every account but on for Jim's and Pam's, **When** Michael picks
   `groups`, **Then** the list holds those two accounts.
2. **Given** `groups` on for every account but off for Dwight's, **When** Michael picks it, **Then**
   the list holds every account except Dwight's.
3. **Given** the Flags screen reads on for 2 of 5, **When** Michael picks that flag alone, **Then**
   the list holds 2 accounts.

### User Story 4 - Filter by when an account joined (Priority: P3)

Michael watches sign ups. The list is already newest first, so this matters least.

1. **Given** accounts created today, ten days ago and a year ago, **When** Michael picks "Last 7
   days", **Then** the list holds the first; with "Last 30 days", the first two.

### Edge Cases

- Filters combine with each other and with the search: every condition must hold.
- Filters survive paging and a change of page size; choosing a filter starts again at page 1.
- An unknown value in the address, including a flag since removed from the code, is ignored.
- An account with nobody on it matches the state "Nobody on it" and no other.
- A deleted person's account keeps its access, so state and access stay independent.
- A flag nobody has switched answers its default for everybody, so it lists nobody or everybody.
- With no filter chosen, the list is exactly today's.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A state filter MUST offer any, active, deactivated, deleted and nobody on it, each
  account matching the one state its row prints.
- **FR-002**: On a hosted instance, an access filter MUST offer any, free plan, subscription,
  lifetime and granted, each account matching the kind its page reports. On a self hosted instance it
  MUST be absent, as the account page's billing panel is.
- **FR-003**: A joined filter MUST offer any time, last 7 days, last 30 days and last 12 months.
- **FR-004**: A flag filter MUST offer any flag and every flag the instance declares, one at a time,
  and keep the accounts the flag is on for, by the same rule the Flags screen counts with.
- **FR-005**: Every chosen filter and the search MUST apply together.
- **FR-006**: The chosen filters MUST be carried in the page address, so a filtered list can be
  bookmarked, reloaded, paged and sent to another administrator.
- **FR-007**: When a filter is chosen, one control MUST clear every filter and the search.
- **FR-008**: When nothing matches, the empty state MUST say nothing matches the filters.
- **FR-009**: A value the screen does not offer MUST be ignored rather than refused.
- **FR-010**: The filters MUST read nothing inside an account's vaults.
- **FR-011**: Each filter MUST have a visible label and work by keyboard, in a form announced as a
  search region, as the support queue's filters are.

### Key Entities

- **Account**: one row of the list. Its state comes from its person, its access from its orders, a
  grant or a subscription, its joined date from its creation, and its flags from the switches for it
  and for every account.

## Success Criteria *(mandatory)*

- **SC-001**: Every deactivated account of an instance is listed in one choice and one submit.
- **SC-002**: A copied filtered address shows the same rows to another administrator.
- **SC-003**: With no filter chosen, the list shows the same rows in the same order as before.
- **SC-004**: At 100,000 accounts, a filtered list opens as quickly as today's unfiltered list.
- **SC-005**: For every flag, the filter lists as many accounts as the Flags screen reads.

## Assumptions

- The filters take the shape of the support queue's: selects beside the search, one submit, a clear
  link. The order stays newest first.
- Nothing new is stored. Filters on storage or last sign in, sorting, and a link from the Flags screen
  to a filtered list are out of scope.
