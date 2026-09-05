# Feature Specification: Adopt laravel/passkeys

**Created**: 2026-09-05

**Status**: Draft

**Input**: User description: "refactor passkey using laravel/passkeys package"

Passkeys ship today on `asbiin/laravel-webauthn` 6.0 (#92, 2026-08-29). This swaps their engine,
endpoints and browser client for the first-party `laravel/passkeys`. What a person can do is
unchanged; where they do it, and two warnings they got, are not.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Sign in with a passkey (Priority: P1)

**Why this priority**: the only way into an account with no password. Break it and they are locked out.

**Independent Test**: sign in on an account holding one passkey, without typing an address.

1. **Given** an account with a passkey, **When** the person picks it at the sign-in screen, **Then**
   they are signed in without naming themselves first.
2. **Given** a deactivated account or one that signs in through an identity provider, **When** its
   passkey answers, **Then** the refusal is the same sentence a wrong password gets.
3. **Given** a signed-in person, **When** the sign-in finishes, **Then** the account journal names
   the passkey used, and the email announcing a new sign-in goes out.

---

### User Story 2 - Manage passkeys (Priority: P2)

**Why this priority**: one passkey is a single point of failure, and an unrevokable one is a trap.

**Independent Test**: add, rename and remove a passkey from the security screen.

1. **Given** the security screen, **When** a passkey is added, **Then** it is listed with its name
   and an email announces it.
2. **Given** a listed passkey, **When** it is removed, **Then** it is gone, a warning was shown
   first, and an email announces it.
3. **Given** a passkey that has signed somebody in, **When** the list is shown, **Then** it says
   when it was last used; one that never has says so instead.

---

### User Story 3 - Create an account with a passkey and no password (Priority: P3)

**Why this priority**: the registration screen offers it. The package only registers a passkey for
somebody already signed in, so this journey keeps a route of its own.

**Independent Test**: register choosing a passkey, then sign out and back in with it.

1. **Given** the registration screen, **When** somebody registers with a passkey, **Then** an
   account is created holding no password, and they are signed in.
2. **Given** an account whose only credential is a passkey, **When** they reach the security screen,
   **Then** they can add another passkey without being asked for a password they do not have.

---

### Edge Cases

- An insecure connection: passkeys are offered nowhere, and giving up a password is refused.
- The same credential twice: the second is refused by the database, not only the browser.
- A device that cannot do passkeys says so instead of offering a dead button.
- Every outcome, a cancelled prompt included, is announced to a screen reader.
- A passkey made for an address this instance no longer answers on gets the ordinary refusal.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every passkey behaviour in `specs/002-webauthn-auth/spec.md` MUST still hold except
  its FR-012 and FR-041, which FR-009 repeals.
- **FR-002**: `asbiin/laravel-webauthn` MUST be gone from `composer.json`, and no code may reference
  `LaravelWebauthn\` or `config('webauthn.*')`.
- **FR-003**: The users provider in `config/auth.php` MUST return to the framework's own driver, so
  password sign-in no longer depends on a package.
- **FR-004**: Passkeys MUST live in the table the package owns. The `webauthn_keys` migration MUST
  be replaced rather than added to, because this project is not in production.
- **FR-005**: The rules the application adds on top of the credential check (deactivated account,
  identity-provider account) MUST still run before any signature is verified.
- **FR-006**: Sign-in MUST stay throttled per credential rather than per address, so one passkey
  being hammered cannot lock out somebody else behind the same address.
- **FR-007**: Adding a passkey MUST NOT require confirming a password, since an account may not have
  one. The package asks for one by default and MUST be told not to.
- **FR-008**: The account journal MUST still record a passkey being used, added and removed, and the
  three emails announcing those MUST still go out.
- **FR-009**: The counter-anomaly email and the different-address hint MUST go, with their listeners,
  notification and tests, and spec 002 MUST be amended in the same change, since its FR-012 and
  FR-041 name them. The library still refuses a replayed counter; only the warning goes.
- **FR-010**: The package's routes and controllers MUST serve sign-in and passkey management, and
  screens, forms and tests follow them to their URLs. Account creation keeps its own route.
- **FR-011**: The browser side MUST move to `@laravel/passkeys`, `@simplewebauthn/browser` MUST go,
  and the live-region announcements MUST be rebuilt on it. Principle IX binds this to WCAG 2.2 AA.
- **FR-012**: The two unused repositories under `app/Providers/Webauthn` MUST be deleted: they
  filter on a `kind` column no migration creates, and nothing references them.
- **FR-013**: The RS1 workaround MUST go unless the new package needs it, and be re-justified if so.
- **FR-014**: `monica:localize` MUST run and every locale be corrected; only FR-009 changes copy.

### Key Entities

- **Passkey**: one credential registered against the single person on an account. Carries the name
  its owner gave it, when it was last used, and the credential itself. Deleted with its user.

## Success Criteria *(mandatory)*

- **SC-001**: Every journey above works end to end and the suite passes. A test may be rewritten
  where it names the old package or its URLs; only FR-009's two cases lose theirs.
- **SC-002**: Anyone whose credential went with the old table enrols a new passkey and signs in unaided.
- **SC-003**: No email, screen or translated string differs, other than what FR-009 removes. URLs
  and route names do change, and no link, form or redirect may be left pointing at an old one.
- **SC-004**: `vendor/bin/pint`, Larastan and `monica:localize --check` report nothing new.

## Assumptions

- Nobody is in production. The project is one month old and its own rules say migrations are edited
  in place until then, so dropping `webauthn_keys` costs only the credentials on dev machines.
- Two factor authentication is `pragmarx/google2fa-laravel` and is untouched by this.
- `laravel/passkeys` and `@laravel/passkeys` are at v0.2.1, pre-1.0. Their API may break before 1.0,
  and adopting their routes and client makes this move when it does. Accepted.
- The package's login ceremony is discoverable-credential based, as this application already is.
