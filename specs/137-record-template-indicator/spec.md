# Feature Specification: Template named on the edit screen of a record

**Feature Branch**: `2026-11-19-changelog-panda-redesign`

**Created**: 2026-11-19

**Status**: Draft

**Input**: User description: "When somebody edits a contact, show the name of the template the record is using on the edit screen. The same applies when editing any record in general, such as an animal. Follow design 1C of `Edit Contact Template Indicator.dc.html`."

## Design reference

Design 1C, "À droite du titre", of the Claude Design project `e80a62cb-60bb-4e94-a26f-ed7cac97c519`, file `Edit Contact Template Indicator.dc.html`:

- The page header keeps its title ("Edit contact") and its description ("What the template does not decide about Agathe.") on the left.
- On the right of the header, aligned to the bottom of the title block, stands a small caption in capitals, "Current template", and under it a bordered link holding a template icon (`layers`), the name of the template, and a muted arrow (`arrow-right`).
- The link leads to the screen that edits that template.
- Below 1024px the block folds under the title instead of standing beside it.
- Nothing else on the screen changes.

## User Scenarios & Testing *(mandatory)*

### User Story 1: See which template shapes a contact while editing it (Priority: P1)

Somebody edits a contact and wants to know why some fields are on the page and others are not. The header names the template the contact is using, and one click on that name opens the template so they can change it.

**Why this priority**: The edit screen already says "What the template does not decide", without saying which template. Naming it is the whole feature.

**Independent Test**: Open the edit screen of a contact built from the template "Basic contact". The header shows "Current template" and "Basic contact", and following the link opens the edit screen of "Basic contact".

**Acceptance Scenarios**:

1. **Given** a contact built from the template "Basic contact", **When** somebody opens its edit screen, **Then** the header shows the caption "Current template" and the name "Basic contact".
2. **Given** that edit screen, **When** somebody activates the template name, **Then** the builder of that template opens.
3. **Given** a contact built from a template the product supplied and nobody renamed, **When** somebody opens its edit screen, **Then** the template name reads in that person's language, the same way it reads in the list of templates.
4. **Given** a screen narrower than 1024px, **When** somebody opens the edit screen, **Then** the template block sits under the title rather than beside it, and nothing overflows horizontally.

---

### Edge Cases

- **The record was moved to a newer version of its template, or the template has a newer version than the record.** The block names the template, never a version. The name is the template's current name.
- **The template was renamed after the record was created.** The block shows the current name.
- **The template is turned off.** The record still renders from it, so the block still names it and still links to it.
- **The template was deleted.** Deletion is refused while any record still uses one of its versions, so an edit screen never meets a record without a template. No empty state is needed.
- **A very long template name.** It wraps or truncates inside the block without pushing the title off the screen.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The edit screen of a contact MUST name, in its header, the template the contact is rendered from.
- **FR-002**: The name MUST be introduced by the caption "Current template".
- **FR-003**: The name MUST be a link to the builder of that template.
- **FR-004**: The name shown MUST be the one the list of templates shows for the same template, including the translated name of a supplied template nobody renamed.
- **FR-005**: The block MUST stand to the right of the title on a wide screen and fold under it below 1024px.
- **FR-006**: The link MUST be reachable and operable with the keyboard, carry a visible focus indicator, and announce both the template name and where it leads to somebody using a screen reader.
- **FR-007**: Every string the block adds MUST be translated into every shipped locale.
- **FR-008**: The block MUST be built so that the edit screen of a record of an invented type (an animal) can show it in the same form once that screen exists. Building that screen is out of scope.
- **FR-009**: Nothing else on the edit screen MUST change: the title, the description, the panels and their order stay as they are.

### Key Entities

- **Template**: A named, account level container of tabs, sections and fields. Its name is either set by somebody or, for a template the product supplied, a translated default.
- **Record**: A contact, or a record of a type somebody invented (an animal). It renders from one version of one template. Only the contact has an edit screen today.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: On every contact edit screen, the template the contact uses is named without any interaction, in 100% of cases.
- **SC-002**: Somebody reaches the template behind a contact from its edit screen in one click.
- **SC-003**: The template name on the edit screen matches the name in the list of templates for 100% of templates, renamed or supplied.
- **SC-004**: The edit screen shows no horizontal scroll at a width of 320px.

## Clarifications

### Session 2026-11-19

- Q: Records of an invented type have no edit screen today. How far should this feature go for them? A: Contacts only. The entity edit screen shows the block when it is built; the entity creation form is unchanged.

## Assumptions

- Templates belong to the account, so anybody who can edit a record can also open its template. No refusal state is needed for the link.
- The link opens the template builder in account settings, the screen that already exists, in the same tab.
- The block names the template only. It does not say which version the record uses, nor offer to move the record to another template; both are outside this feature.
- The contact view screen (not the edit screen) is unchanged.
- Life events are also rendered from a structure, but theirs belongs to a life event type rather than a template, so they are out of scope.
