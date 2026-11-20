# Feature Specification: Name a template copy before it is made

**Feature Branch**: `142-name-template-copy`

**Created**: 2026-11-20

**Status**: Draft

**Input**: User description: "un client ecrit: \"When I make a copy of a template it automatically creates a slug like `copy-of-basic-contact-2` and there is no way to change it. Ideally there would be an option to customize the slug name after copying a template but prior to it being saved.\" comment permettre cette idee ?"

## Context

Today the Copy button on the list of templates makes the copy at once, with no question asked. The copy is named "Copy of {original}" and its slug is derived from that name, with a number added when the slug is taken (`copy-of-basic-contact-2`). The person then lands in the builder of the copy.

The slug of a template is fixed once the template exists. The edit form says so ("Fixed when the template was created, so old links keep working"), because the slug is the template's address in every URL and in the API. A slug chosen by the application is therefore a slug the person keeps forever.

Making a copy through the API or through an assistant already accepts a name and a slug. Only the screen gives no way to choose them.

The answer to the customer is therefore to ask for the name and the slug **before** the copy exists, which is exactly what they asked for, and not to make slugs editable afterwards, which would break the links the rule protects.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Choose the name and slug of a copy (Priority: P1)

Somebody wants a variant of the "Basic contact" template for their colleagues. They press Copy on its row. Instead of the copy appearing at once, they are asked what to call it. The name field already reads "Copy of Basic contact" and the slug field already reads the slug that name would get. They change the name to "Work contact", the slug follows it and becomes `work-contact`, and they confirm. The copy is made with that name and that slug, and they land in its builder as before.

**Why this priority**: This is the whole request. Without it the slug of every copy is one the person did not choose and can never change.

**Independent Test**: Press Copy on a template, change the name and the slug, confirm, and check that the new template carries both values, that its URL uses the chosen slug, and that the original is untouched.

**Acceptance Scenarios**:

1. **Given** a template named "Basic contact", **When** the person presses Copy, **Then** they are shown a form whose name reads "Copy of Basic contact" and whose slug reads the slug the copy would get if nothing were changed, and nothing has been created yet.
2. **Given** that form, **When** the person changes the name and has not touched the slug, **Then** the slug follows the name as they type, the same way it does when a template is created.
3. **Given** that form, **When** the person types a slug of their own, **Then** the slug stops following the name.
4. **Given** that form, **When** the person confirms, **Then** the copy is made with the chosen name and slug, they land in its builder, and the confirmation message they see today is shown.
5. **Given** that form, **When** the person leaves without confirming, **Then** no copy exists.

---

### User Story 2 - Be told why a slug is refused (Priority: P2)

The person types a slug that another template of the account already uses, or one with capitals or spaces. They are told what is wrong next to the field, what they typed is kept, and nothing is created until the slug is acceptable.

**Why this priority**: Letting the person choose a slug only works if a bad choice is explained rather than silently changed.

**Independent Test**: Submit the form with a slug already used in the account, then with an invalid slug, and check that each is refused with a message on the slug field and that no template was created.

**Acceptance Scenarios**:

1. **Given** a template whose slug is `work-contact` in the account, **When** the person submits a copy with the slug `work-contact`, **Then** the form is shown again with a message saying the slug is taken, with their values kept, and no copy is made.
2. **Given** the form, **When** the person submits a slug containing capitals, spaces, accents or other characters than lowercase letters, numbers and single dashes, **Then** it is refused with the same message the creation form gives.
3. **Given** the form, **When** the person empties the slug and submits, **Then** the slug is derived from the name, as it is when a template is created.
4. **Given** the form, **When** the person empties the name and submits, **Then** it is refused because a template needs a name.

---

### Edge Cases

- A slug used by a deleted template of the same account is refused when typed, because a deleted template still holds its slug. When the slug is left empty, the derived one skips it, as it does today.
- A slug identical to one used in another account is accepted: slugs are unique within an account only.
- The suggested slug is computed when the form is shown. If another copy takes it before the person confirms, the submission is refused as taken and the person picks another one.
- A system template and a template of the account's own are copied the same way.
- A template with no version to copy cannot be copied today; the form refuses it the same way the immediate copy does.
- A slug longer than the maximum a template slug may have is refused.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Pressing Copy on the list of templates MUST show a form asking for the name and the slug of the copy, and MUST NOT create anything until that form is confirmed.
- **FR-002**: The form MUST open with the name "Copy of {original name}" and with the slug the copy would receive if nothing were changed.
- **FR-003**: The slug MUST follow the name while the person has not typed a slug of their own, and stop following it once they have, the same way the creation form behaves.
- **FR-004**: The form MUST say that the slug is the address of the template in URLs and in the API and that it cannot be changed once the copy exists.
- **FR-005**: The slug MUST be validated by the same rules as the slug of a new template: lowercase letters, numbers and single dashes, the same maximum length, and unique among the account's templates.
- **FR-006**: An empty slug MUST be derived from the name, with a number added when it is taken, as the copy does today.
- **FR-007**: A refused submission MUST show the reason next to the field concerned and keep what the person typed.
- **FR-008**: Confirming MUST make the copy exactly as today (same content, a draft version, placed last in the list, written in the account's journal) with the chosen name and slug, then take the person to the builder of the copy.
- **FR-009**: The form MUST offer a way back to the list of templates that creates nothing.
- **FR-010**: The original template MUST remain unchanged whatever is submitted.
- **FR-011**: The slug of a template MUST remain unchangeable after it is created. This feature does not open slug editing on existing templates.
- **FR-012**: Making a copy through the API and through an assistant MUST keep working as it does today, including deriving the name and slug when they are left out.
- **FR-013**: Every new piece of copy on the form MUST be translatable and meet the project's accessibility requirements (labelled fields, errors announced with the field they belong to, usable with a keyboard alone).

### Key Entities

- **Template**: Has a name shown in the picker and a slug that is its address in URLs and the API, unique within the account and fixed once created. A copy is a new template whose content comes from the original's published version, or its first version when none is published.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A person can make a copy with a name and slug of their choice in a single pass through the form, without editing anything afterwards.
- **SC-002**: No copy is ever created with a slug the person did not see before confirming.
- **SC-003**: Every refused slug comes with a message saying why, and no submission is lost on refusal.
- **SC-004**: Existing integrations that copy templates through the API or an assistant behave identically before and after the change.
- **SC-005**: The customer who reported the problem can produce a copy whose slug reads `work-contact` (or any free slug of their choice) instead of `copy-of-basic-contact-2`.

## Assumptions

- Asking before creating is chosen over allowing the slug to be edited afterwards. Editing a slug afterwards would break every URL and API call already using it, which the project explicitly forbids today ("so old links keep working").
- The form asks for the name and the slug only. The description, the language and what the template applies to are copied from the original and can be changed afterwards on the edit form, as today.
- The name typed for the copy is shown as is, in the account's language; it is no longer the translated system name of the original.
- Whether the form is a screen of its own or a dialog over the list is a design decision for the plan. Either satisfies this specification.
- The API and the MCP tool need no change: both already accept a name and a slug for the copy. The API documentation is checked during planning to confirm it says so.
- This changes something a person notices, so it carries an entry in What's new.
