# Feature Specification: Paginate long lists

**Feature Branch**: `2026-10-14-paginate-long-lists`
**Created**: 2026-10-14
**Status**: Draft
**Input**: "paginate long list of elements"

## Context

Lists that grow with somebody's data are paged unevenly. Some load every row each time the screen opens, some stop at a fixed number with no way to see the rest, and the admin screens say Load more while replacing the page. This feature gives every such list one of two forms:

- **Numbered pages** (Previous, page numbers, Next, and a page size choice) for a list somebody looks a row up in.
- **Load more**, which adds the next page under the rows already shown, for a list read from the top.

| Screen | Today | Becomes |
| --- | --- | --- |
| People list of a vault | Load more | Numbered pages |
| Merge picker of a contact | Every contact in the vault | Numbered pages |
| Entities of one type in a vault | Every entity, archived included | Numbered pages |
| Share links of a contact | Every link, revoked included | Numbered pages |
| Visits of a share link | Stops at 50 | Numbered pages |
| Invoices in billing settings | Every payment | Numbered pages |
| Admin accounts, logs, moderation, administrators | Load more that replaces the page | Numbered pages |
| Duplicates | Previous and Next, default framework look | Numbered pages |
| Notes and other repeated values on a contact page | Every value of the field | Load more |
| A contact's Reminders page, History filter | Every answered occurrence, also on every contact page | Load more |
| Delivery log of a reminder | Stops at 50 | Load more |
| Notification panel | Stops at 25 | Load more |

## Clarifications

### Session 2026-10-14

- Q: Should the merge picker narrow by a typed name as well as page? → A: Yes, it narrows by a typed name and pages the matching contacts.
- Q: Where does keyboard focus go after Load more, and on which screens? → A: On the new Load more link, on every Load more in the application.
- Q: How does a field with many values show them on a contact page? → A: 10 at a time with Load more inside the section. Notes show the most recently added first; other repeated values keep the order the person set.
- Q: Load more or numbered pages? → A: Numbered pages for lists somebody looks a row up in, the People list, the admin screens and Duplicates included; Load more for lists read from the top.
- Q: Can somebody choose how many rows a page holds? → A: Yes, on every numbered list: 10, 20, 50, 100, 500 or All.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Find a row in a long list by page (Priority: P1)

Michael has 3,000 contacts in a vault. On the People list, the merge picker for Dwight, an entity type or Dwight's share links, he sees the first page, the rows it holds out of how many, and Previous, page numbers and Next. He picks 100 rows a page, or All, and jumps to page 7. Jan, who administers the instance, gets the same on the admin screens, and Michael on Duplicates.

**Why this priority**: these lists either load everything each time or hide what is past a fixed number, and they are where somebody looks for one row.

**Independent Test**: seed a vault with 3,000 contacts, open the People list, change the page size, go to a numbered page, and check its rows.

**Acceptance Scenarios**:

1. **Given** more rows than one page, **When** the list opens, **Then** only the first page is shown, with "1 to 50 of 3,000" and links to the other pages.
2. **Given** page 3 is shown, **When** Michael activates Next, Previous or the number 7, **Then** that page is shown with the filters and sort he had.
3. **Given** page 7 at 50 rows, **When** Michael picks 100 rows a page, **Then** page 1 at 100 rows is shown, and the choice holds as he changes pages.
4. **Given** Michael picks All, **Then** every row is shown on one page and no page links remain.
5. **Given** the merge picker, **When** Michael types part of a name, **Then** only the matching contacts are listed, in numbered pages.

### User Story 2 - Read a long list from the top (Priority: P2)

Pam opens Dwight, whose notes section holds 200 notes, then his reminder history, a reminder's delivery log, and the notification panel. Each shows a first page and Load more, which adds the next page under the rows already shown.

**Why this priority**: today these load everything or silently stop, and the reminders block slows every contact page.

**Independent Test**: create 200 notes on one contact, open it, load more until the end, and check each note appeared once, newest first.

**Acceptance Scenarios**:

1. **Given** 200 notes, **When** Pam opens Dwight, **Then** the 10 notes added most recently show, newest first, and Load more adds the next 10 older ones.
2. **Given** 30 phone numbers, **Then** the first 10 show in the order Pam set, and Load more adds the rest in that order.
3. **Given** the last page is shown, **Then** Load more is gone, and it never leads to an empty page.
4. **Given** the contact page, **Then** the reminders block shows a short preview of history and a link to the full Reminders page.

### Edge Cases

- A page number past the last answers with the last page; a malformed number or page size answers with the default; never an error.
- On numbered pages, a row added or removed while somebody reads can shift one row across a page boundary. Load more never shows a row twice nor skips one.
- Changing a filter, a sort or the page size starts again from page 1.
- A page asked through the wrong vault or another account answers not found, as the first page does.
- Without JavaScript, page links and Load more still work as links.
- When the last Load more page arrives there is no new Load more link, so focus lands on an end of list line in its place.
- 500 rows or All on a large list takes longer to open. That is the person's choice, and the default size stays fast.
- An archived entity and a revoked share link stay in their list, in the position they hold today.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every list in the Context table MUST open showing one page of rows, in the form the table gives it.
- **FR-002**: A numbered list MUST offer Previous, page numbers and Next, mark the current page for a screen reader, and abbreviate a long run of numbers.
- **FR-003**: A numbered list MUST say which rows it shows out of how many.
- **FR-004**: A numbered list MUST offer a page size of 10, 20, 50, 100, 500 or All, kept while changing pages, filters and sort.
- **FR-005**: All MUST show every row on one page, with no page links.
- **FR-006**: A Load more list MUST offer Load more when, and only when, more rows exist, and add them below the rows shown without reloading the screen.
- **FR-007**: Every list MUST keep the order it uses today, except notes on a contact page, which show newest first.
- **FR-008**: Opening a list at a page size other than All MUST NOT read the rows of pages that are not shown.
- **FR-009**: On every Load more in the application, Load more MUST be operable with the keyboard, focus MUST land on the new Load more link once rows are added, and a screen reader MUST hear that rows were added.
- **FR-010**: A list that holds nothing MUST keep the empty state it shows today.
- **FR-011**: The contact page reminders block MUST show a preview of history with a link to the full list.
- **FR-012**: The merge picker MUST narrow its list to the contacts whose name matches what was typed.
- **FR-013**: A field with repeated values on a contact page MUST show 10 values at a time, with Load more inside its section.
- **FR-014**: Saving a section MUST NOT remove a repeated value because it was not loaded on the screen.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: At its default page size, each list in scope opens within 10% of the same time with 5,000 rows as with 50.
- **SC-002**: Reading every page of a list that nobody changes meanwhile yields each row exactly once.
- **SC-003**: A keyboard-only user reaches page 10 of a numbered list, and the 101st row of a Load more list, without a pointer.

## Assumptions

- **Default page size**: the People list keeps 50; every other numbered list opens at 20; a Load more list that pages today keeps its size; a newly paged one shows 25, and repeated values 10.
- **The page size travels with the address** of the page, so it survives a reload and a page change, and the browser remembers the last choice per screen in a cookie, so the next visit opens at it. It is kept per browser, not per person (decided 2026-10-16).
- **This replaces Load more on the People list**, which spec 041 had settled.
- **Unchanged apart from FR-009**: the timeline, the reminder tabs, search, the activity and email logs in settings, and the marketplace keep Load more.
- **Out of scope**: relationships on a contact, decided in `spec/models/026-relationship-not-yet.md`. The Active reminders tab and the marketplace name sort, which page after reading every row. Lists bounded by design: vaults, account settings lists, security lists, templates, pickers narrowed by typing.
- **No API change.** Every list in scope is already paginated by the API. Its cap of 100 rows a page is unaffected: 500 and All are a way of reading on screen, not a capability.
