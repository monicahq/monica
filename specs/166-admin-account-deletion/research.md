# Research: Account deletion from the admin space

## R1. One erasure, two callers

**Decision**: `DestroyAccount` keeps the erasure and takes only the account. It finds the person itself,
a deleted one included, or none. A new `DestroyAccountAsAdministrator` refuses the administrator's own
account, calls `DestroyAccount`, and writes the administrator log.

**Rationale**: FR-008 requires both deletions to remove the same data, and one implementation is the
only way to keep that true. Today `DestroyAccount` takes a `User`, so an account whose person is
deleted or missing could not be erased.

**Rejected**: an `?User $administrator` parameter on `DestroyAccount` (two actions in one class); a
copy of the erasure in the admin action (the copies would drift).

## R2. What the erasure misses today

| Left behind | Where | Fix in `DestroyAccount` |
| --- | --- | --- |
| Files uploaded to vaults, contact avatars | `accounts/{id}/` on the disk of each `files` row | Delete `accounts/{id}` on every disk named by the account's files, exports and imports. This replaces the transfers-only deletion, since `accounts/{id}/transfers` lies inside it. |
| Sessions on other devices | `sessions.user_id` has no foreign key | Delete the person's rows when the session driver is `database` |

Everything else cascades from `accounts` or `users` in the database (OAuth codes and tokens, passkeys,
security keys, mobile sessions, administrator capacity, support requests, marketplace listings). The
Scout driver is `database`, so no outside index is left. Personal access tokens are polymorphic and
already deleted by hand.

## R3. Refusing one's own account

**Decision**: reuse `AdministratorCannotLockThemselvesOut` with its own message. The screen asks
`isTheViewer()` and shows the refused button with `aria-describedby`, like the deactivation panel.

**Rationale**: the deactivation panel next to it handles the same rule this way. `policies.md` would
add a policy for a rule stated on the screen and in the action, but the code beside the change ranks
higher (CLAUDE.md precedence 2) and one policy for half of the admin screen would be inconsistent.

## R4. The administrator log entry

**Decision**: a new `AdministratorActionEnum::AccountDestroyed`, described as `Deleted account
:account`, with `['account' => <number>]` as parameters, `account: null`, and the account as subject
(type and id only, which survives the deletion). `namesTheAccountHolder()` is false.

**Rationale**: FR-006. Passing the account model would serialise a row the queue can no longer load.

**Not changed**: earlier entries about the account keep the name in their parameters, and entries
written by the person as an administrator keep their address. The log refuses updates
(`LogIsAppendOnly`), and rewriting it is outside the request.

## R5. Confirmation

**Decision**: a `confirmation` field in an `x-modal.form`, validated inline in the controller: the
person's address after lowercasing and trimming (as the settings deletion does), or the account number
when the account has no person. The modal reopens on the error.

## R6. What is not built

No API endpoint and no MCP tool (spec assumptions). No changelog entry for the admin panel, which
nobody using Monica sees. One entry for R2, since files and sessions now go with a self-service deletion.
