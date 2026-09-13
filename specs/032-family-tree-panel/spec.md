# Feature Specification: Family tree panel

**Feature Branch**: `2026-09-13-family-tree-panel`

**Created**: 2026-09-13

**Status**: Draft

**Input**: User description: "add family tree, following the `Relationships tab.dc.html` design in the Monica design project."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - See a contact's line at a glance (Priority: P1)

Somebody opens a contact's Relationships page. Beside the list of groups, a panel draws that person's line: whoever is recorded as their parent above them, whoever they are with now beside them, and their children below. The person the page is about is marked out from the rest, and every face in the drawing is somebody already written down in the list.

**Why this priority**: the list answers "who is this person connected to". It cannot answer "how do these people sit relative to each other", which is the whole reason a tree exists. The drawing delivers that on its own, reading records that are already there.

**Independent Test**: write down a parent, a partner and two children for one contact, open its Relationships page, confirm the panel draws all five people in three tiers with the contact marked out.

**Acceptance Scenarios**:

1. **Given** a contact with a recorded parent, a current partner and children, **When** the Relationships page opens, **Then** a Family tree panel appears beside the groups, drawing the parent above, the contact and partner side by side, and the children below.
2. **Given** the panel, **When** it is read, **Then** it is titled after the contact whose page it is.
3. **Given** the contact the page is about, **When** the drawing is read, **Then** that person is marked out from everybody else in it.
4. **Given** somebody in the drawing who has a profile, **When** their node is followed, **Then** their page opens.
5. **Given** somebody in the drawing written down as a name only, **When** the drawing is read, **Then** their node is drawn differently from a node with a profile and leads nowhere.
6. **Given** a friend or a colleague, **When** the drawing is read, **Then** they do not appear, and a line beside the panel says why.

---

### User Story 2 - Read a line with nothing in it, or too much (Priority: P2)

A contact has no parent, no partner and no child recorded, so there is no line to draw. Another has seven children and two parents, so the line is wider than the panel.

**Why this priority**: the drawing is only useful once relationships exist. Both ends of the range have to read well before the panel can go on every contact, and a contact with nothing recorded is the commonest case in a young vault.

**Independent Test**: open the panel for a contact with no family recorded, confirm it says so and offers a way in. Then record seven children for another contact and confirm every one of them is drawn and reachable.

**Acceptance Scenarios**:

1. **Given** a contact with no parent, partner or child recorded, **When** the Relationships page opens, **Then** the panel says there is no line to draw yet and offers a way to write down a relationship.
2. **Given** a contact with children but no parent and no partner, **When** the panel is read, **Then** the contact and its children are drawn and no empty tier is shown.
3. **Given** a tier holding more people than the panel is wide enough for, **When** the panel is read, **Then** every person in that tier is drawn and the drawing can be moved sideways to reach them.
4. **Given** a line taller than the panel, **When** the panel is read, **Then** the drawing can be moved up and down to reach it.
5. **Given** a narrow window, **When** the page is read, **Then** the panel and the groups stack rather than sit side by side, and the panel keeps working.

---

### Edge Cases

- Both parents recorded: both are drawn above the contact, each joined to it.
- The contact has a partner who is a name only: the partner is drawn as a name-only node, joined to the contact like any other.
- A child whose other parent is not the contact's partner: the child hangs from the contact, since the drawing knows only who is recorded as whose parent.
- The same person is both a parent and a partner of the contact, which the records permit: they are drawn once per tier they belong to, and the drawing does not fail.
- Status cuts one way only: a partnership recorded as ended drops the person from beside the contact, matching what Immediate family counts, while a parentage recorded as ended still draws them, because ending contact with a child does not undo the parentage.
- Two people recorded as each other's parent, which the add and edit forms already refuse: the panel draws one generation each way and stops, so it cannot loop.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The Relationships page of a contact MUST carry a Family tree panel beside the relationship groups, and no other page MUST carry it.
- **FR-002**: The panel MUST be named after the contact whose page it is.
- **FR-003**: The Immediate family panel on the Overview page MUST be left exactly as it is.
- **FR-004**: On a window too narrow to hold both, the panel and the groups MUST stack rather than sit side by side, and the panel MUST keep every behaviour it has beside them.

**Who is drawn**

- **FR-005**: The drawing MUST hold three tiers, and a tier holding nobody MUST NOT be drawn: whoever is recorded as a parent of the contact, the contact together with whoever the contact is with now, and whoever is recorded as a child of the contact.
- **FR-006**: Only parentage and partnership MUST reach into the drawing. Somebody reached by no other kind of relationship MUST NOT appear, and the panel MUST say in words that friends and colleagues stay in the list.
- **FR-007**: Status MUST cut one way only: a partnership reaches the drawing only while recorded as current, matching what Immediate family counts, while a parentage reaches it whatever its status, because ending contact with a child does not undo the parentage.
- **FR-008**: Membership of a tier MUST be decided by the protected behaviour a relationship type carries, never by its name, its key or its category, so that renaming a type, moving it or withdrawing it leaves the drawing unchanged and a type somebody invents under the same name reaches nobody.
- **FR-009**: People inside a tier MUST be drawn in a stable order that does not change between readings.

**How a person is drawn**

- **FR-010**: The contact the page is about MUST be marked out from everybody else in the drawing.
- **FR-011**: Somebody with a profile MUST be drawn with their avatar and MUST lead to their page. Somebody written down as a name only MUST be drawn differently and MUST lead nowhere.
- **FR-012**: Every node MUST carry the name of the person it stands for, readable without following it, and a name-only node MUST say so in words rather than by its dashed edge alone. The panel MUST NOT carry a legend: the wording belongs on the node it describes.

**How the tiers are joined**

- **FR-013**: A line MUST join each recorded parent to the contact, each current partner to the contact, and each child to the contact.
- **FR-014**: A child MUST NOT be drawn as hanging from the contact and a partner together, because the records never say a partner is a child's other parent. The mock draws otherwise; see [research.md](research.md) D2.

**Size, and reaching what is drawn**

- **FR-015**: Every person belonging to a tier MUST be drawn. No tier MUST be capped, summarised or shrunk to fit.
- **FR-016**: The panel MUST occupy the same width and the same height whatever the line holds, so a large family does not push the rest of the page around, and a drawing larger than the panel MUST be reachable in full by moving it both sideways and up and down within it.
- **FR-017**: Everything reachable by moving the drawing MUST also be reachable by keyboard alone.

- **FR-018**: A contact with no parent, no partner and no child recorded MUST be told there is no line to draw yet, and MUST be offered a way to write down a relationship.

- **FR-019**: The panel MUST draw nobody outside the account and the vault the contact belongs to.
- **FR-020**: The panel MUST answer without asking the records once per person drawn, so that a large family costs the page no more than a small one.

### Key Entities

This feature defines no entity. It reads **Relationship**, the link already recorded between two people, **Relationship type**, whose protected behaviour says whether the link is parentage or a partnership, and **Contact**, whose profile or lack of one decides how a node is drawn. The line the panel draws is derived on each reading and stored nowhere.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody looking at a contact can name that person's parents, current partner and children without leaving the Relationships page and without following a single link.
- **SC-002**: The panel occupies identical space on a contact with no family recorded and on a contact with twelve people in their line.
- **SC-003**: Every person the panel draws can be reached, and every one with a profile opened, using the keyboard alone.
- **SC-004**: The page costs the same number of questions of the records whether the line holds two people or twenty.
- **SC-005**: 100% of the people the panel draws are people the relationship groups on the same page already list, so the two never disagree, and a contact with nothing recorded is always offered a way to write down the first relationship.

## Assumptions

- The panel only reads. It writes nothing and adds no way to record, correct or remove a link, beyond the way in offered when there is nothing to draw.
- "One generation above and one below" is taken literally: parents and children, never grandparents or grandchildren, and never siblings, since a sibling is reached through a parent and drawing them would make the tier ambiguous.
- The Immediate family panel on the Overview page stays. The two overlap deliberately: one is a list on one page, the other a drawing on another. Confirmed with the requester.
- No tier is capped, and the mock's "Open" control is dropped. Both confirmed with the requester; the departures from the mock are listed in [research.md](research.md) D2 and D8.
- Ordering inside a tier follows the order the rest of the application already lists people in, so that the panel and the groups beside it agree.
