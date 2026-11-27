# Feature Specification: Account deletion from the admin space

**Feature Branch**: `2026-11-27-admin-account-deletion`

**Created**: 2026-11-27

**Status**: Draft

**Input**: User description: "je veux pouvoir effacer completement un compte depuis l'interface d'administration. Aujourd'hui sur /admin/accounts/115, on a seulement la possibilité de désactiver un compte. Peut on ajouter une autre boîte en dessous qui parle de suppression ? Supprimer un compte veut dire absolument tout effacer de lui."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - An administrator deletes an account and everything in it (Priority: P1)

Somebody asks support to erase their account, or an account has to go for another reason. The
administrator opens the account's screen, finds a red panel under the deactivation panel, confirms,
and the account is gone with everything it held, the files on disk included.

**Why this priority**: it is the feature. Today the only way to erase an account is to sign in as its
owner, which support cannot do.

**Independent Test**: delete an account holding vaults, contacts, files and an API key from the admin
screen, then check that none of it can be reached and that no file of it is left on disk.

**Acceptance Scenarios**:

1. **Given** an account of somebody else, **When** the administrator confirms the deletion by typing
   the person's email address, **Then** the account and everything under it is deleted, and the
   administrator lands on the list of accounts with a message saying it is gone.
2. **Given** the confirmation is typed wrongly, **When** the administrator submits, **Then** nothing
   is deleted and the form says the address does not match.
3. **Given** the account pays a subscription, **When** it is deleted, **Then** the subscription is
   stopped first; if the payment provider does not answer, nothing is deleted and the screen says so.
4. **Given** the deletion is done, **When** any administrator reads the administrator log, **Then** an
   entry says who deleted which account number and when.

---

### User Story 2 - An administrator cannot delete their own account from here (Priority: P2)

**Why this priority**: an instance with one administrator must not lose its only administrator by a
click in the admin space. Deleting one's own account stays in the account settings.

**Independent Test**: open one's own account in the admin space and try to delete it.

**Acceptance Scenarios**:

1. **Given** the administrator views their own account, **When** the screen renders, **Then** the
   deletion panel is shown with its button refused and the reason next to it.
2. **Given** a request to delete one's own account reaches the server anyway, **Then** it is refused
   and nothing is deleted.

### Edge Cases

- An account whose person was already deleted, or that holds nobody: it can still be deleted, and the
  confirmation asks for the account number instead of an address when there is no address to type.
- An account that is deactivated: it can be deleted; deactivation is not a precondition.
- An account in the middle of an export or an import: the transfer files go with it.
- Records other accounts depend on (a marketplace listing the account published, a support request it
  opened): they go with the account, as they do when the owner deletes it themselves.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The admin account screen MUST show a deletion panel below the deactivation panel, saying
  that the deletion is final and listing what goes with it.
- **FR-002**: The deletion MUST ask for confirmation in a dialog, and MUST require the person's email
  address typed exactly (or the account number when the account has no person) before it proceeds.
- **FR-003**: The deletion MUST remove everything the account owns: its person, vaults, contacts,
  records, files on disk, photos, API keys, sessions, passkeys, OAuth grants, flags, exports, imports,
  support requests and marketplace listings.
- **FR-004**: The deletion MUST stop an active subscription first and MUST refuse to delete anything
  when the payment provider cannot be reached.
- **FR-005**: The system MUST refuse an administrator deleting their own account from the admin space,
  and the screen MUST show that refused control with its reason.
- **FR-006**: The deletion MUST be written in the administrator log with the administrator, the moment
  and the account number, and nothing personal about the deleted account.
- **FR-007**: The person MUST NOT be sent an email about the deletion; support replies by hand when
  the deletion was requested.
- **FR-008**: The deletion an owner makes from their settings and this one MUST erase the same set of
  data, so one never leaves behind what the other removes.

### Key Entities

- **Account**: what is deleted, with everything that belongs to it.
- **Administrator log entry**: the trace that survives the deletion, and the only one.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: After a deletion, zero records and zero files belonging to the account remain.
- **SC-002**: An administrator deletes an account in under one minute from its admin screen.
- **SC-003**: No deletion happens without the typed confirmation, in 100% of attempts.
- **SC-004**: Every deletion appears in the administrator log.

## Assumptions

- The deletion is immediate and final. No grace period, no restore.
- The period already paid for is not refunded, as with self-service deletion. Invoices and payment
  history kept by the payment provider stay there, since accounting law requires them.
- No API endpoint and no MCP tool: account administration has none today (deactivation included), and
  an irreversible deletion over a token is a risk the request does not ask for.
- Only administrators reach the screen, through the existing admin route protection.
- The self-service deletion currently leaves vault files on disk; closing that gap is part of FR-003
  and FR-008.
