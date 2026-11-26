# Feature Specification: Sign in from the mobile apps

**Feature Branch**: `2026-11-26-mobile-app-sign-in`

**Created**: 2026-11-26

**Status**: Draft

**Input**: The server half of the Android app's sign-in (`monicahq/android`, spec `002-multi-instance-auth`, contract `contracts/monica-server.md`): the apps sign people in on the official instance or any self-hosted one through the authorization server Monica already runs for assistants, with no feature flag.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Sign in from the app on any instance (Priority: P1)

Somebody taps "Log in" in the Android app. Their browser opens their instance's usual sign-in page (password, two-factor, security key, single sign-on), then a page asking whether Monica for Android may access their account. They allow it and the app can use the API for them. Nobody configured anything on the instance.

**Why this priority**: Without it the apps cannot work at all.

**Independent Test**: On a freshly installed instance and on an upgraded one, run the authorization flow with PKCE for the mobile client, exchange the code, and call `GET /api/v1/me` with the token.

**Acceptance Scenarios**:

1. **Given** a fresh install or an upgrade, **When** setup has run, **Then** exactly one mobile client exists, the same on every instance, and running setup again changes nothing.
2. **Given** a signed-in person, **When** the app asks for access, **Then** the consent page names Monica for Android and the instance, whether or not the account has the MCP server switched on, and appears even if this person already allowed the app before.
3. **Given** the person allows access, **When** the app exchanges the code with the right verifier, **Then** it receives an access token valid one hour and a refresh token, and the API answers with it.
4. **Given** an assistant's token, **When** it calls the JSON API, **Then** it is refused; **given** the app's token, **When** it calls the MCP server, **Then** it is refused.

### User Story 2 - An instance tells the app how to sign in (Priority: P1)

The app reads a public description of the instance: its version, whether it accepts new accounts, the app's client identifier, and the sign-in, token and revocation addresses, so it can tell a compatible Monica from anything else before signing in.

**Why this priority**: The app cannot start a sign-in without it, and self-hosted addresses are arbitrary.

**Independent Test**: Read the status and the authorization server description of an instance served at the root and of one served under a path.

**Acceptance Scenarios**:

1. **Given** any instance, **When** its status is read, **Then** it says which API version the apps can use, the app's client identifier, whether registration is open, and the oldest app version it accepts, if any.
2. **Given** any instance, **When** its authorization server description is read, **Then** it lists the revocation address and the API scope, and every member assistants read today keeps its value; under `https://example.com/monica`, every address starts with that path.

### User Story 3 - Stay signed in, and sign out one phone (Priority: P2)

The app renews its access silently. Signing out on a phone ends that phone's session only; the person's other phones, assistants and API keys keep working.

**Why this priority**: Signing in once a day, or signing out every phone at once, would make the app unusable.

**Independent Test**: Sign in from two phones, renew on both, sign out on one, and check the other still works.

**Acceptance Scenarios**:

1. **Given** a refresh token, **When** the app renews, **Then** it gets a new pair and the old refresh token stops working.
2. **Given** a refresh token already used or an authorization code already exchanged, **When** it is presented again, **Then** it is refused and the session concerned ends.
3. **Given** a phone signs out, through the revocation address with its refresh token or through `DELETE /api/v1/logout`, **Then** that phone's access and renewal stop working at once, and nothing else does.

### User Story 4 - See and end phone sessions from the web or the API (Priority: P2)

In Settings → Account → Security, a "Mobile apps" section lists each phone signed in (its name, when it signed in, when it was last used) and lets the person end any one of them, for example after losing it. The API offers the same list and the same action.

**Why this priority**: The safety net for a lost phone.

**Independent Test**: Sign in from two phones, end one from the settings and the other through the API.

**Acceptance Scenarios**:

1. **Given** two phones signed in, **When** the security settings open, **Then** "Mobile apps" lists both, and "Connected assistants" lists neither.
2. **Given** the list, **When** one session is ended and confirmed, **Then** that phone is signed out and the other phone, assistants and API keys are untouched.

### User Story 5 - Sign up from the app (Priority: P3)

Somebody without an account taps "Sign up" in the app. Monica shows its registration page instead of the sign-in page, and once the account exists (and the email is confirmed when the instance asks for it) the flow continues to the consent page and back to the app.

**Why this priority**: New people arrive from the app stores; it reuses everything above.

**Independent Test**: Start an authorization with `prompt=create` as a guest, register, confirm the email, and reach the consent page.

**Acceptance Scenarios**:

1. **Given** registration is open, **When** a guest starts a sign-up from the app, **Then** the registration page shows, and after registering the person lands on the consent page.
2. **Given** registration is closed, **When** a guest starts a sign-up, **Then** the sign-in page shows with a message saying the instance does not accept new accounts.

### Edge Cases

- A dynamically registered assistant asks for the API scope, or the mobile client asks for the MCP scope: refused with `invalid_scope`.
- A return address differing by one character from the registered one is refused.
- A revocation request with an unknown or already revoked token answers success and changes nothing.
- The device name sent by the app is empty, too long or holds markup: stored cleaned, or shown as "Unnamed phone".

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every instance MUST hold exactly one first-party public mobile client, with a fixed identifier and the return address `com.monicahq.android:/oauth/callback`, created by setup on install and upgrade, idempotently, without any administrator step.
- **FR-002**: The JSON API MUST accept access tokens carrying the new API scope, next to API keys, with the rights of an API key without extra scopes (no sensitive fields, no administration), and MUST refuse other OAuth tokens.
- **FR-003**: Only the mobile client MAY obtain the API scope, and the mobile client MUST NOT obtain the MCP scope.
- **FR-004**: The consent page MUST be shown at every sign-in of the mobile client, MUST NOT depend on the MCP server flag, and MUST name the app and the instance in the reader's language.
- **FR-005**: Access tokens MUST last one hour for every client; refresh tokens MUST last 30 days and change at every renewal.
- **FR-006**: Each mobile sign-in MUST create a mobile session (user, device name, signed in, last renewed, ended) holding only fingerprints of its codes and tokens; sessions ended or unused for 30 days are pruned.
- **FR-007**: Presenting an exchanged authorization code or a replaced refresh token again MUST end the session concerned.
- **FR-008**: A public revocation endpoint (RFC 7009) for a refresh token, and `DELETE /api/v1/logout` for a mobile token, MUST end that session's access and renewal at once and nothing else.
- **FR-009**: `GET /api/v1/status` MUST describe the instance to the apps: API version for apps, mobile client identifier, scope, registration open or closed, oldest app version accepted (configurable, empty by default).
- **FR-010**: The authorization server description MUST add the revocation endpoint, the API scope and the `create` prompt, and MUST keep every member assistants rely on.
- **FR-011**: An authorization request with `prompt=create` from a guest MUST lead to registration when it is open, and registration and email verification MUST continue to the authorization request.
- **FR-012**: Settings → Account → Security MUST list active mobile sessions and let the person end one after a confirmation; "Connected assistants" MUST NOT list the mobile client.
- **FR-013**: The API MUST list the person's active mobile sessions and end one of them, with the same rules as the settings.
- **FR-014**: The authorization, revocation and description addresses MUST be rate limited per address without hindering normal use.
- **FR-015**: Signing in from a phone and ending a phone session MUST be written to the account log.
- **FR-016**: Web sign-in, API keys, the password API login and the MCP server (keys and OAuth) MUST behave exactly as before.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A fresh install and an upgrade both let the Android app sign in with zero administrator steps; running setup twice leaves one mobile client.
- **SC-002**: Ending one of two phone sessions, from the phone, the web or the API, signs out that phone only, in 100% of the tested cases.
- **SC-003**: Every existing test for web sign-in, API keys and the MCP server passes unchanged, and no token, code or verifier can be read from the database or the logs.

## Assumptions

- The app's side and decisions R1–R16 live in `monicahq/android`, spec `002-multi-instance-auth`; iOS will add its own return address to the same client.
- No MCP tool and no webhook: none exists for API keys or assistants, and Monica has no webhook events yet.
