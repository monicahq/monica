# Feature Specification: Relationships empty state

**Feature Branch**: `2026-09-23-relationships-empty-state` | **Created**: 2026-09-23 | **Status**: Draft

**Input**: "Implement a new blank state for the relationships tab in
/vaults/personal/contacts/chandler-bing/relationships, based on design 1c of
`Relationships empty state.dc.html`. The category of relationships is based on the choices in the
account."

## Context

When a contact has no relationship, the Relationships tab shows a panel titled "Relationships", one
sentence ("Nobody is written down as connected to Chandler yet.") and one "Add a relationship" button.
It says nothing about what the tab will hold once it is filled.

Once filled, the tab groups the links under the relationship categories of the account, in the order
the account puts them in. Every account starts with five supplied categories (Family, Romantic,
Friendship, Professional, Other) and may add, reorder and switch off its own.

Design 1c draws the empty tab as the shape of the filled one: one row per category, each with an icon,
the category name, a line naming what it holds, and its own **Add** button. The panel title goes,
since the tab already names the section. The design shows four hard coded groups; this feature draws
the account's categories instead.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The empty tab shows where links will go (Priority: P1)

The reader opens Chandler Bing's Relationships tab. Chandler has no relationship yet. The tab lists
the account's categories in the account's order (Family, Romantic, Friendship, Professional, Other),
each with a line naming the kinds of relationship in it ("Parent, Sibling, Cousin, Spouse"), and an
Add button on every row.

**Why this priority**: this is the screen the request asks for.

**Independent Test**: open the tab of a contact with no relationship and read the rows.

**Acceptance Scenarios**:

1. **Given** a contact with no relationship, **When** the Relationships tab opens, **Then** one row
   is shown per category of the account that holds at least one active relationship type, in the
   account's order.
2. **Given** a category, **Then** its row shows its name as the account writes it, in the reader's
   language for a supplied category, and a line naming the active types it holds.
3. **Given** the account renamed, reordered or added a category, **Then** the empty tab shows that
   name and that order, and the added category too.
4. **Given** a category switched off, or one whose types are all switched off, **Then** it is not
   shown.
5. **Given** the empty tab, **Then** no panel title repeats "Relationships" and no family tree is
   drawn.

---

### User Story 2 - Add starts from the chosen category (Priority: P1)

The reader presses Add on the Family row. The add form opens with a Family type already chosen
as the type of the relationship. The reader picks the other person and saves, as today.

**Why this priority**: without it, the per-row button does the same as one global button and the
rows are decoration.

**Independent Test**: press Add on a row and check which type the form has chosen.

**Acceptance Scenarios**:

1. **Given** the Family row, **When** Add is pressed, **Then** the add form opens with the first
   active type of Family chosen.
2. **Given** that form, **Then** every other type of every category can still be chosen.
3. **Given** the form is opened without a type, or with a type that is not the account's or not
   offered, **Then** it opens as it does today, and no error is shown.
4. **Given** the form is saved, **Then** the relationship is recorded exactly as it is today, and the
   tab shows the filled layout.

---

### Edge Cases

- The account has no category holding an active type: the tab shows today's sentence and a link to
  the relationship types settings, since nothing can be added.
- A category added by the account has no supplied icon: it gets the generic relationship icon.
- A category with many types: the line names the first four and stops, without ellipsis text in the
  copy.
- A long category name or line wraps on narrow screens and the Add button stays reachable.
- Keyboard only: every Add button is reachable in the order of the rows, and its accessible name
  says which category it adds to ("Add to Family"), so ten "Add" buttons are not announced alike.
- A right to left language: the row reads from the other side, as every other row in the product.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: When a contact has no relationship, the Relationships tab MUST show one row per active
  category of the account that holds at least one active relationship type, in the account's order.
- **FR-002**: Each row MUST show an icon, the category's name as the account writes it, a line naming
  up to four of its active types in the account's order, and an Add button.
- **FR-003**: A supplied category MUST carry its own icon (Family, Romantic, Friendship, Professional,
  Other); a category the account added MUST carry the generic relationship icon.
- **FR-004**: Each Add button MUST open the add relationship form with the first active type of its
  category chosen, and its accessible name MUST name the category.
- **FR-005**: The add form MUST accept a relationship type it is opened with, and MUST ignore one that
  is not the account's or is not offered (switched off, or in a switched off category).
- **FR-006**: The empty tab MUST NOT show a panel title and MUST NOT show the family tree.
- **FR-007**: When no category can be shown, the tab MUST say so and point to where types are set up.
- **FR-008**: A contact with at least one relationship MUST see the tab exactly as it is today.
- **FR-009**: Every new string MUST be translated in every configured locale.

### Key Entities

- **Relationship category**: an account's grouping of relationship types, with a name, a position,
  an on or off state, and for the five supplied ones a fixed key.
- **Relationship type**: one kind of link (Parent and Child, Spouse, Friend), belonging to one
  category, with an on or off state and a position.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: On an empty tab, the reader reaches the add form with the right kind of relationship
  already chosen in one press.
- **SC-002**: The empty tab lists the same categories, in the same order, as the filled tab would
  group links under.
- **SC-003**: No existing behaviour of the filled tab or of the add form changes for a reader who
  does not come from an Add button.

## Assumptions

- "The first active type" is the first in the account's order for that category. Choosing a
  category rather than a type keeps each row to one button, as the design draws it.
- The line under a category is built from the names of its types rather than written copy, since
  the account decides what a category holds. For a type whose two sides differ (Parent and Child),
  the line uses its name as the settings screen shows it.
- The "Other" category is shown like the rest when it holds an active type, although the design
  draws only four groups.
- The family tree stays out of the empty tab, as it does today.
