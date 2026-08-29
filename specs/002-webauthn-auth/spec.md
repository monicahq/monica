# Feature Specification: WebAuthn Authentication

**Feature Branch**: `2026-08-22-webauthn-auth`

**Created**: 2026-08-22

**Status**: Draft

**Input**: User description: "webauthn authentication"

## User Scenarios & Testing *(mandatory)*

Today somebody signs in to Erica in one of three ways: an email address and a password, a link sent
to their email address, or an identity provider. The first is the common one, and it is the weakest.
A password can be guessed, reused across sites, phished on a convincing copy of the sign in screen,
or read out of somebody else's breach. The two factor code that can sit behind it helps against
reuse and guessing, and helps very little against phishing, because a code typed into a fake screen
works exactly as well for the attacker as for its owner.

A passkey is a credential held by the device or the security key in front of the person, unlocked by
their fingerprint, their face or their device PIN. It never leaves that device, it is different on
every site, and it refuses to answer a site that is not the one it was created for. There is nothing
to type, nothing to remember, and nothing a convincing copy of our sign in screen can collect.

This feature adds passkeys to Erica as a way to sign in on their own, standing where the email
address and the password stand rather than behind them, and a place to manage them beside the
password, the two factor setting and the API keys on the account security screen. No existing way in
is taken away. What does change is that somebody holding a passkey may choose to remove their own
password, which is the only way this feature makes the phishable secret go away instead of merely
offering an alternative beside it. The link sent by email always remains, and it is what makes that
choice safe to offer.

Erica stays mono-user: a passkey belongs to the single person on an account, and an account may hold
several of them, one per device.

### User Story 1 - Add a passkey and sign in with it (Priority: P1)

Somebody signed in to Erica opens their account security screen, chooses to add a passkey, and their
browser asks them to confirm with their fingerprint, their face, their device PIN or their security
key. The passkey is created and appears on the screen with a name they can recognise. They sign out.
On the sign in screen they choose to sign in with a passkey, confirm the same way, and they are in,
without typing an email address or a password.

**Why this priority**: it is the whole feature in one slice. Adding a passkey nobody can sign in
with is worth nothing, and signing in with a passkey nobody can create is impossible, so the pair is
the smallest thing that delivers value. Everything else in this specification makes it safer or
easier to live with.

**Independent Test**: on a device with a platform authenticator, add a passkey from the security
screen, sign out, sign back in using only the passkey, and confirm the session is a normal signed in
session with the same rights as one opened with a password.

**Acceptance Scenarios**:

1. **Given** a signed in user with no passkey, **When** they add one and confirm with their device,
   **Then** the passkey is recorded against their account, it is listed on the security screen with
   a name, the date it was added, and the fact it has not been used yet.
2. **Given** a signed in user adding a passkey, **When** they dismiss or cancel the device prompt,
   **Then** nothing is recorded, they are returned to the security screen, and they are told the
   passkey was not added rather than being left to guess.
3. **Given** a user with a registered passkey who is signed out, **When** they choose to sign in
   with a passkey and confirm with their device, **Then** they are signed in and taken where they
   were heading, exactly as a password sign in would take them.
4. **Given** a user signing in with a passkey, **When** the sign in succeeds, **Then** the moment is
   recorded as their last sign in and against that particular passkey as its last use, and the sign
   in appears in their account activity naming the passkey used.
5. **Given** a user with a registered passkey, **When** they sign in with their password instead,
   **Then** that still works, and the passkey is untouched.
6. **Given** a user whose account has been deactivated, **When** they present a passkey that is
   registered and valid, **Then** they are refused, and the refusal reads the same as the refusal
   for a wrong password.
7. **Given** somebody presenting a credential that was never registered on this instance, **When**
   they attempt to sign in, **Then** they are refused with the same message as any other failed sign
   in, and nothing tells them whether the credential, the account or neither exists.
8. **Given** a passkey created for one account, **When** it is presented while trying to sign in,
   **Then** it signs in to that account and only that account, and it can never be made to open a
   different one.

---

### User Story 2 - See and manage the passkeys on the account (Priority: P2)

A person accumulates passkeys: one on their laptop, one on their phone, one on a security key in a
drawer. They open the account security screen and see all of them, each with a name they chose or a
sensible default, when it was added, and when it last let them in. They rename the one whose default
name means nothing to them. They remove the one on the laptop they sold, and are warned before it
happens.

**Why this priority**: without it, passkeys accumulate and become unmanageable, and somebody who
loses a device has no way to cut it off. It is not needed to prove the feature works, which is why
it sits behind Story 1, but the feature is not shippable without it.

**Independent Test**: register three passkeys, confirm all three are listed and distinguishable,
rename one, remove one after being warned, and confirm the removed one can no longer sign in while
the other two still can.

**Acceptance Scenarios**:

1. **Given** a user with several passkeys, **When** they open the security screen, **Then** every
   passkey is listed, most recently used first, each showing its name, when it was added, and when
   it was last used, with a passkey never used saying so rather than showing nothing.
2. **Given** a user with no passkey, **When** they open the security screen, **Then** the section
   explains what a passkey is and offers to add one, rather than showing an empty list.
3. **Given** a user viewing their passkeys, **When** they rename one, **Then** the new name is shown
   everywhere that passkey appears, and an empty name is refused with the reason given.
4. **Given** a user viewing their passkeys, **When** they ask to remove one, **Then** they are
   warned that the device holding it will no longer sign them in, and the removal happens only after
   they confirm.
5. **Given** a removed passkey, **When** it is presented at the sign in screen, **Then** it is
   refused exactly as an unregistered credential is, and it does not reappear in the list.
6. **Given** a user who adds a passkey, renames one or removes one, **When** the change is made,
   **Then** it is recorded in their account activity, and they are told by email at the address on
   the account, so a change made by somebody else does not go unnoticed.
7. **Given** a user attempting to register a passkey their account already holds, **When** the
   device offers the credential it already created here, **Then** the attempt is refused with an
   explanation, and no duplicate entry is created.
8. **Given** a user whose account is deleted, **When** the deletion completes, **Then** every
   passkey on it goes with it and none survives to reference an account that is gone.

---

### User Story 3 - Keep a way in when a passkey cannot be used (Priority: P2)

A phone falls in a lake. A laptop is at the office. A browser is one that cannot do passkeys at all.
Someone sits at a machine that is not theirs. In every one of those cases the person still has to be
able to reach their account, and the screen has to tell them how rather than presenting a control
that fails silently.

**Why this priority**: a sign in method that can strand somebody is worse than no sign in method.
This is what makes Story 1 safe to turn on, and it can be built and tested on its own.

**Independent Test**: with a passkey registered, sign in by password and by magic link and confirm
both still work. Then open the sign in screen in a context where passkeys are unavailable and
confirm the reason is stated and another way in is offered.

**Acceptance Scenarios**:

1. **Given** a user with a passkey registered, **When** they sign in, **Then** signing in with a
   password and signing in through a link sent by email both remain available and unchanged.
2. **Given** the sign in screen opened in a browser or on a device that cannot use passkeys,
   **When** it loads, **Then** it says passkeys are not available here and why, and it does not
   offer a control that leads nowhere.
3. **Given** an instance served over a connection that is not secure, **When** anybody opens the
   sign in screen or the security screen, **Then** passkeys are presented as unavailable on this
   instance with the reason stated, rather than failing when someone tries.
4. **Given** a user whose only registered passkey is on a device they no longer have, **When** they
   sign in another way, **Then** they can remove that passkey and add a new one without contacting
   anybody.
5. **Given** a user removing their last passkey, **When** they confirm, **Then** the removal
   succeeds and they are told which ways in they still have, so they are never left guessing what is
   left.
6. **Given** repeated failed passkey sign in attempts from one place, **When** the count passes the
   limit that already applies to password attempts, **Then** further attempts are refused for a
   period and the reason is stated, so a credential cannot be probed one request at a time.
7. **Given** the address the instance is served from has changed, **When** a user presents a passkey
   created under the previous address, **Then** they are told the passkey no longer applies to this
   address and pointed at another way in, rather than shown a failure with no explanation.

---

### User Story 4 - Sign in on a device that holds no passkey (Priority: P3)

Somebody is on a borrowed laptop. Their passkey is on their phone. Their operating system and their
browser can bridge the two, showing a code to scan with the phone, and the phone does the
confirming. Erica's job is to not get in the way: offer the passkey option, let the platform run the
bridge, and accept the result.

**Why this priority**: it is a real and increasingly common case, and it needs nothing built beyond
what Story 1 already does, as long as Erica does not assume the credential lives on the machine in
front of the person. It sits last because it is the platform's flow, not ours.

**Independent Test**: with a passkey registered on a phone, sign in on a separate computer that has
no passkey of its own, using the platform's cross-device flow, and confirm the session is a normal
one.

**Acceptance Scenarios**:

1. **Given** a user whose passkey lives on another device, **When** they choose to sign in with a
   passkey on the machine in front of them, **Then** the platform's own cross-device flow is allowed
   to run and a successful confirmation signs them in.
2. **Given** a cross-device sign in that the person abandons, **When** the platform reports it ended
   without a result, **Then** the sign in screen returns to its starting state and says nothing was
   signed in, leaving the person able to try again or choose another way.
3. **Given** a user adding a passkey, **When** they choose a security key or another device rather
   than the one in front of them, **Then** the passkey is registered the same way and listed the
   same way as any other.

---

### User Story 5 - Give up the password entirely (Priority: P3)

Somebody who has passkeys on the devices they actually use decides the password is the weakest thing
left on their account and removes it. They are warned what that means, told what will still sign
them in, and it is done. Their account now has no password to phish, reuse or guess. If they change
their mind, they set one again.

**Why this priority**: it is the point of passkeys for a lot of people, and it is the only slice
that removes a phishable secret rather than adding an alternative beside it. It sits last because it
needs Story 1 to have a passkey at all and Story 2 to manage them, and because it is the slice with
the most ways to go wrong for the person doing it.

**Independent Test**: with a passkey registered, remove the password, confirm signing in with the
passkey still works and that submitting a password is refused, then set a password again and confirm
it works.

**Acceptance Scenarios**:

1. **Given** a user holding at least one passkey, **When** they ask to remove their password,
   **Then** they are warned that the password will no longer sign them in, told what still will, and
   the removal happens only after they confirm.
2. **Given** a user holding no passkey, **When** they ask to remove their password, **Then** the
   request is refused and they are told they need a passkey first.
3. **Given** a user with no password, **When** they submit an email address and any password at the
   sign in screen, **Then** they are refused with the same message as any other failed sign in, and
   nothing tells them the account has no password.
4. **Given** a user with no password, **When** they ask to set one, **Then** they can, and it signs
   them in from then on.
5. **Given** a user with no password, **When** they ask for a password reset link, **Then** the flow
   works as a way to set one, rather than failing because there is nothing to reset.
6. **Given** an instance where passkeys cannot work, **When** a user asks to remove their password,
   **Then** the request is refused with the reason, so nobody gives up the credential that works for
   one that cannot.
7. **Given** a user who removes or sets a password, **When** the change is made, **Then** it is
   recorded in their account activity and they are told by email.

---

### User Story 6 - Create an account with a passkey and no password (Priority: P2)

Somebody new gives their name and their email address, and the screen offers to create a passkey as
the way they will sign in from then on. They confirm with their fingerprint, their face or their
device PIN, and the account exists with no password at all. Choosing a password instead is still
there, one link away, for whoever wants it or whoever is on a device that cannot make a passkey.

**Why this priority**: without it the product contradicts itself. Story 5 lets somebody remove their
password, so the current path forces every new account to invent a password it may delete a minute
later, and to pick the one credential this feature exists to get rid of. It is not needed for the
feature to work, which is why it is not P1, but the feature reads as unfinished without it.

**Independent Test**: on a device with a platform authenticator, create an account from the
registration screen without ever typing a password, then sign out and sign back in with the passkey
alone.

**Acceptance Scenarios**:

1. **Given** the registration screen on a device that can make a passkey, **When** it loads,
   **Then** creating a passkey is the primary thing offered, choosing a password is offered as an
   alternative rather than as an equal, and what a passkey is is said in a sentence before anything
   is asked of the device.
2. **Given** somebody filling in the registration screen, **When** they confirm the passkey on their
   device, **Then** the account exists, holds that passkey, holds no password, and they are told the
   passkey was created.
3. **Given** somebody who has created an account with a passkey, **When** they sign out and come
   back, **Then** the passkey signs them in with nothing typed.
4. **Given** the registration screen, **When** somebody chooses to set a password instead, **Then**
   the account is created with a password exactly as it is today, and they can add a passkey
   afterwards.
5. **Given** somebody creating an account with a passkey, **When** they dismiss or cancel the device
   prompt, **Then** they are told no passkey was created, and they are offered both routes again
   rather than being left on a screen that has stopped meaning anything.
6. **Given** a device or a browser that cannot make a passkey, **When** the registration screen
   loads, **Then** it says so and offers the password route, and no control appears that would fail.
7. **Given** an account created with a passkey, **When** the email address is confirmed, **Then**
   confirming works exactly as it does for an account created with a password.
8. **Given** an account that ended up with neither a passkey nor a password, because the prompt was
   abandoned after the account was made, **When** they next open the product, **Then** they are told
   plainly that a link sent to their email address is what reaches them, and they are offered both a
   passkey and a password.

---

### Edge Cases

- A person cancels, ignores or times out the device prompt, whether registering or signing in.
  Nothing is recorded, and they are told plainly that nothing happened.
- Two browser tabs start a registration at the same time. At most one passkey results, and the other
  says the attempt is no longer valid.
- A challenge is replayed: the same one-time value is sent back twice, or long after it was issued.
  The second use and the late use are both refused.
- A stored credential's use counter goes backwards, which can mean the credential was copied. The
  attempt is refused and the account owner is told.
- A user signs in with a passkey while already signed in, in another tab or on another device.
  Existing sessions are unaffected and the new one is a normal session.
- A passkey is presented for a user whose email address was never confirmed. The existing rule for
  unconfirmed addresses applies unchanged: the passkey decides who they are, not what they may do.
- A passkey is presented for a user who signs in through an identity provider. It is refused for the
  same reason a password is refused for them today.
- A passkey is presented for a soft deleted user. It is refused as if it were never registered.
- A name is submitted that is empty, made only of spaces, or longer than the field allows. It is
  refused with the reason given, and the passkey keeps the name it had.
- A person has many passkeys, more than fits comfortably on one screen. The list stays readable and
  usable.
- A device reports no usable name for itself when a passkey is created. A default name is given that
  the person can change, and no passkey is ever nameless.
- The instance is reached at localhost during development, where a connection that is not secure is
  nonetheless allowed by browsers. Passkeys work there, so a contributor can test the feature.
- A user with no password and one passkey removes that passkey after being warned. They are not
  stranded: a link sent to the address on the account still reaches them, and the screen said so
  before they confirmed.
- A user with no password asks for a password reset link. The flow works as a way to set one, and no
  screen treats a missing password as an error state.
- A user with two factor authentication enabled and no password signs in through the emailed link.
  They still answer the two factor challenge, because that rule is unchanged.
- A passwordless account is left with no passkey the person can reach and an email address that no
  longer reaches them. There is no self-service way back, and the product says so plainly instead of
  implying one exists.
- An instance where a user removed their password stops being able to do passkeys, because a
  certificate expired or the address changed. The emailed link is what gets them in, and the screen
  points at it.
- Somebody abandons the device prompt at registration after the account has already been made. The
  account exists with neither a passkey nor a password, which is the state FR-033 already names, and
  the product says so rather than leaving them to discover it at the next sign in.
- Two people race to register the same email address, one by passkey and one by password. The
  address is unique, so the second is refused as it is today.
- Somebody registers with a passkey on a device that already holds a passkey for this instance,
  belonging to a different account. Both exist and each opens only its own account.

## Requirements *(mandatory)*

### Functional Requirements

**Registering a passkey**

- **FR-001**: Users MUST be able to add a passkey to their account from the account security screen,
  without leaving the application and without entering their password again.
- **FR-002**: The system MUST require an active signed in session to add a passkey, and MUST attach
  the new passkey to the user on that session and to no other.
- **FR-003**: The system MUST issue a one-time, short-lived challenge for each registration, tie it
  to the session that asked for it, accept it exactly once, and refuse it after it expires.
- **FR-004**: The system MUST refuse to register a credential the account already holds, and MUST
  say so, rather than creating a second entry for the same credential.
- **FR-005**: The system MUST give every passkey a name at creation, taken from what the device
  reports about itself when it reports something usable, and a plain default otherwise.
- **FR-006**: The system MUST record, for each passkey, when it was added and when it was last used
  to sign in, and MUST leave the last use empty for a passkey never used rather than pretending it
  was used at creation.
- **FR-007**: The system MUST accept passkeys held on the device in front of the person and passkeys
  held on a separate device or a security key, without preferring or requiring either.
- **FR-008**: The system MUST NOT verify or record the make and model of the authenticator, and MUST
  NOT restrict which authenticators are allowed. A self-hosted instance cannot maintain a list of
  approved hardware, and Erica MUST behave identically on both deployments.

**Signing in with a passkey**

- **FR-009**: Users MUST be able to sign in with a passkey from the sign in screen without typing an
  email address, a password or a code.
- **FR-010**: The system MUST issue a one-time, short-lived challenge for each sign in attempt,
  accept it exactly once, and refuse a challenge that has expired or already been answered.
- **FR-011**: The system MUST verify that an assertion was produced for this instance's address and
  for a credential registered here, and MUST refuse it otherwise.
- **FR-012**: The system MUST refuse a sign in when the credential's use counter has not advanced as
  expected, and MUST tell the account owner by email that this happened.
- **FR-013**: A passkey sign in MUST be refused for a user who is not active, who signs in through
  an identity provider, or whose record has been deleted, and every refusal MUST read identically to
  the refusal for a wrong password, so nothing reveals whether an account exists.
- **FR-014**: The system MUST apply the same rate limit to failed passkey sign in attempts that it
  applies to failed password attempts, counted per credential and per place the attempt came from.
- **FR-015**: A session opened with a passkey MUST carry exactly the same rights as a session opened
  with a password. There MUST be no capability that one grants and the other does not.
- **FR-016**: The system MUST record a passkey sign in in the account activity, naming which passkey
  was used, and MUST update that passkey's last use and the user's last sign in.
- **FR-017**: The system MUST send the same new sign in notification for a passkey sign in that it
  sends for a password sign in, so the account owner learns of a sign in they did not make whichever
  credential was used.

**How passkeys sit beside the existing ways in**

- **FR-018**: Adding a passkey MUST NOT remove or weaken any existing way to sign in. Password,
  magic link and identity provider sign in all keep working exactly as they do today.
- **FR-019**: A passkey MUST be a way to sign in on its own, standing in place of the email address
  and the password rather than following them. The system MUST NOT offer a passkey as a second
  factor, and MUST NOT add it to the two factor challenge screen.
- **FR-020**: A passkey sign in MUST NOT ask for a two factor code, including from a user who has
  two factor authentication enabled. A passkey is held by a device and released only once its owner
  has proved themselves to that device, so the assertion already carries both factors, and a code
  typed afterwards adds nothing to it.
- **FR-021**: The system MUST say that signing in with a passkey does not ask for the two factor
  code, and MUST say it both where passkeys are presented and where the two factor setting is.
  Somebody who turned two factor authentication on has to be able to see that a passkey does not go
  through it.
- **FR-022**: The magic link MUST keep asking for the two factor code exactly as it does today. The
  exemption in FR-020 belongs to passkeys alone, because a link is a single emailed secret with
  nothing proving who opened it, and MUST NOT be read as a general rule that skipping the password
  skips the challenge.
- **FR-023**: The system MUST NOT extend the two factor recovery codes to cover passkeys, and MUST
  NOT present them as a way to recover a lost passkey. Recovery codes stay tied to the two factor
  setting they belong to.
- **FR-024**: Users MUST be able to remove their password from the account security screen once they
  hold at least one passkey, and the system MUST refuse the removal for an account holding none.
- **FR-025**: Before removing a password, the system MUST warn that the password will no longer sign
  them in, name what still will, and require an explicit confirmation.
- **FR-026**: Users MUST be able to set a password again at any time after removing it, so going
  passwordless is never a one-way door.
- **FR-027**: A user with no password who submits a password at the sign in screen MUST be refused
  with the same message as any other failed sign in. Nothing may reveal whether an account has a
  password, or whether it exists at all.
- **FR-028**: Removing a password, and setting one again, MUST each be recorded in the account
  activity and MUST notify the account owner by email.

**Creating an account with a passkey**

- **FR-046**: The registration screen MUST offer creating a passkey as its primary action wherever a
  passkey can be made, and MUST offer choosing a password as an alternative rather than as an equal
  option side by side with it.
- **FR-047**: The registration screen MUST say what a passkey is, in one plain sentence about
  unlocking with a fingerprint, a face or a device PIN, before anything is asked of the device.
- **FR-048**: The system MUST be able to create an account that has a passkey and no password, and
  such an account MUST be indistinguishable afterwards from one that reached the same state by
  removing its password.
- **FR-049**: The system MUST tell somebody the passkey was created before it takes them onward, so
  that the one credential their account now depends on is confirmed rather than assumed.
- **FR-050**: When the device prompt is cancelled, dismissed or times out during registration, the
  system MUST say no passkey was created and MUST offer both a passkey and a password again.
- **FR-051**: Where a passkey cannot be made, whether because of the instance or the browser, the
  registration screen MUST say so and MUST offer the password route, and MUST NOT show a control
  that would fail.
- **FR-052**: An account that reaches the state of holding neither a passkey nor a password MUST be
  told, on the next screen it sees, that a link sent to its email address is what reaches it, and
  MUST be offered both a passkey and a password.
- **FR-053**: Creating an account with a passkey MUST send the same email confirmation, and MUST
  record the same activity, as creating one with a password, plus the record of the passkey itself.
- **FR-054**: The password route through registration MUST keep working exactly as it does today,
  including its validation, its terms acceptance and its bot check.

**Managing passkeys**

- **FR-029**: Users MUST be able to see every passkey on their account, with its name, when it was
  added and when it was last used, most recently used first.
- **FR-030**: Users MUST be able to rename any of their passkeys, and the system MUST refuse an
  empty name and a name longer than the limit, stating the reason.
- **FR-031**: Users MUST be able to remove any of their passkeys, and the system MUST warn them
  before the removal that the device holding it will no longer sign them in, and require an explicit
  confirmation.
- **FR-032**: When a user removes a passkey, the system MUST tell them which ways of signing in they
  still have, so nobody has to work out for themselves whether they just locked themselves out.
- **FR-033**: When a user with no password asks to remove their last passkey, the system MUST warn
  them that a link sent to the address on the account becomes their only way in, and MUST offer them
  the chance to set a password instead. It MUST NOT refuse the removal outright, and it MUST NOT
  perform it without an explicit confirmation.
- **FR-034**: A removed passkey MUST stop working immediately and MUST be refused exactly as an
  unregistered credential is.
- **FR-035**: The system MUST record adding, renaming and removing a passkey in the account
  activity, and MUST notify the account owner by email of an addition and of a removal.
- **FR-036**: A user MUST NOT be able to see, rename or remove a passkey belonging to another
  account, and any attempt MUST fail as though the passkey did not exist.
- **FR-037**: Deleting an account MUST remove every passkey on it.

**When passkeys cannot work**

- **FR-038**: The system MUST detect that passkeys are unavailable, whether because the instance is
  not served over a secure connection or because the browser cannot do them, and MUST say so with
  the reason on both the sign in screen and the security screen, instead of offering a control that
  fails.
- **FR-039**: The system MUST keep passkeys working when the instance is reached at localhost, so
  the feature can be developed and tested without a certificate.
- **FR-040**: The system MUST refuse to remove a password on an instance where passkeys are
  unavailable, so nobody gives up the credential that works for one that cannot.
- **FR-041**: When a passkey is presented that was created for a different address than the one the
  instance now answers on, the system MUST explain that the passkey no longer applies to this
  address and point at another way in.
- **FR-042**: The system MUST NOT expose any passkey material to the user, to another account, or
  through any export, and MUST NOT provide a way to copy a passkey out of Erica.

**Across the whole feature**

- **FR-043**: Every string this feature adds MUST be translatable and MUST be translated in every
  locale the application ships.
- **FR-044**: Every screen and control this feature adds MUST be operable with the keyboard alone,
  announced correctly by a screen reader, free of information carried by colour alone, visibly
  focused, readable zoomed to 200%, and usable on desktop, tablet and phone.
- **FR-045**: Adding, renaming and removing a passkey, signing in with one, and removing and setting
  a password, MUST each be available outside the interface once a public API exists, with no change
  to what they do. The behavior MUST live in one place so that a second surface adds nothing but
  transport.

### Key Entities

- **Passkey**: one credential registered against the single user of an account, and possibly the
  only credential that account has, since somebody holding a passkey may remove their password.
  Carries the name the person sees, when it was added, when it was last used to sign in, and the
  material the instance needs to verify that a later assertion came from it. That material is never
  shown to anybody and never leaves the instance. A user may hold several. A passkey belongs to
  exactly one account and can never open another.
- **Registration challenge**: a one-time value the instance issues when somebody asks to add a
  passkey, tied to their session, valid briefly, and spent when it is answered. It exists so a
  registration cannot be replayed or made on somebody else's behalf.
- **Sign in challenge**: a one-time value the instance issues when somebody asks to sign in with a
  passkey, valid briefly, and spent when it is answered. It exists so a captured assertion cannot be
  used a second time.
- **Instance address**: the address a passkey is bound to when it is created, and which every later
  assertion is checked against. It is not a user-facing setting, but it is why a passkey stops
  applying when the instance moves, and the product has to explain that rather than fail.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A signed in user adds their first passkey in under 60 seconds from opening the account
  security screen, with no page they have to leave and nothing to type.
- **SC-002**: A user with a registered passkey signs in in under 10 seconds, typing nothing.
- **SC-003**: Among users who have added at least one passkey, at least 80% of their subsequent sign
  ins use a passkey rather than a password.
- **SC-004**: 100% of sign in attempts using a credential that is unregistered, removed, replayed,
  or attached to a user who is not active are refused, and the refusals are indistinguishable from
  each other and from the refusal for a wrong password.
- **SC-005**: No sequence of actions available in the interface leaves an account with nothing that
  can reach it. Every reachable state keeps at least a link sent to the address on the account, and
  the interface names what remains after every change that removes something.
- **SC-006**: A user who removes their password is warned first, is told what still signs them in,
  and can set one again in under 60 seconds, so the change is reversible by the person who made it.
- **SC-007**: A user with three passkeys removes the one they meant to remove on the first attempt,
  because each is distinguishable by name, date added and last use.
- **SC-008**: On an instance or a browser where passkeys cannot work, 100% of users are told why and
  offered another way in, and 0% are shown a control that fails when used.
- **SC-009**: Every screen this feature adds is fully usable with the keyboard alone and with a
  screen reader, and passes WCAG 2.2 AA.
- **SC-010**: Every string this feature adds is translated in every locale the application ships,
  with zero untranslated strings.
- **SC-011**: Every passkey addition, renaming, removal and sign in is retrievable afterwards from
  the account activity, attributed to the passkey involved.
- **SC-012**: Every refusal a person can meet in these flows names something they can do next, so no
  failed sign in and no failed registration leaves somebody with nothing to try. Verified by walking
  every refusal path the feature can produce.
- **SC-013**: The sign in screen and the security screen each render in under 500 milliseconds at
  the 95th percentile, measured server side, for an account holding twenty passkeys.
- **SC-014**: Somebody can create an account and be signed in with a passkey, having typed a name
  and an email address and nothing else, in under 90 seconds.
- **SC-015**: No account can be created that holds neither a passkey nor a password without the
  person being told, on the next screen, what still reaches them and how to fix it.

## Assumptions

- A passkey belongs to the single person on an account, because Erica is mono-user. One account may
  hold several passkeys, typically one per device. There is no notion of a passkey shared between
  people.
- Passkeys are added from the account security screen by somebody already signed in, and at
  registration. Registration was out of scope in the first version of this specification, and User
  Story 6 brings it in: once an account may hold no password, forcing every new one to invent a
  password it can delete a minute later is the product arguing with itself.
- A passkey is created against an account that exists, so at registration the account is made first
  and the passkey immediately after, in that order. That is a property of the protocol rather than a
  choice, and it is why FR-052 exists: a prompt abandoned between the two leaves an account with
  neither credential, and the product has to say so rather than let it be discovered at the next
  sign in.
- The passkey list lives on the existing account security screen, beside the password, the two
  factor setting and the API keys, rather than on a screen of its own.
- Cross-device sign in, meaning using a phone's passkey on a computer, is provided by the operating
  system and the browser. Erica allows it and accepts the result. It does not build a flow of its
  own for it.
- Authenticator attestation is not verified and no hardware is allowlisted. A self-hosted instance
  cannot maintain a hardware metadata service, and the product must behave identically on hosted and
  self-hosted deployments.
- Passkeys require a secure connection, with localhost the exception browsers already make, so the
  feature is developed and tested locally without a certificate.
- A passkey is bound to the address the instance answers on. An instance that changes address
  invalidates the passkeys created under the old one. This is a property of the technology and not a
  choice, so the product explains it instead of trying to work around it.
- Using a passkey to confirm a sensitive action already inside the application, such as changing an
  email address or deleting an account, is out of scope for this version.
- A passkey is a way to sign in in its own right, not a second factor. It stands where the email
  address and the password stand, and nothing is added to the two factor challenge screen.
- A passkey sign in does not ask for the two factor code. A passkey is released by the device only
  once its owner has proved themselves to it, so the assertion already carries both factors. This is
  a deliberate exception to the rule the magic link follows, where skipping the password does not
  skip the challenge, and the difference is that a link is one emailed secret with nothing proving
  who opened it. Because it is an exception, the security screen has to say it out loud rather than
  leave somebody assuming their two factor setting covers every route in.
- An account may hold passkeys and no password, once it holds at least one passkey. This is the
  point of passkeys for many people and it removes the last phishable secret, at the cost of putting
  the weight of recovery on the address on the account.
- The link sent by email is what makes going passwordless safe, so it is the recovery path and it is
  never removed. It keeps asking for the two factor code, which is what stops it being a way around
  a passwordless account's protection.
- The existing password reset flow doubles as the way to set a password on an account that has none.
  Nothing new is built for that.
- Two factor recovery codes stay attached to the two factor setting. They are not repurposed as a
  way back from a lost passkey.
- Notifying the account owner of a passkey being added or removed reuses the existing email
  machinery, and the existing new sign in notification covers a passkey sign in. No new notification
  channel is introduced.
- There is no `Api` namespace in the application yet, so API coverage means the behavior sits in an
  action that an API controller can call later without rework, as the constitution requires during
  this period.
- Removing a password is refused on an instance where passkeys cannot work, because the result would
  be an account with no credential that functions there.

## Dependencies

- The existing sign in screen, account security screen and account activity, all of which this
  feature adds to rather than replaces.
- The existing email delivery used for sign in notifications, which carries the new passkey added
  and passkey removed messages.
- The existing rate limiting applied to failed sign in attempts, extended to cover passkey attempts.
- Browser and operating system support for passkeys on the device in front of the person. Erica
  cannot supply it and has to detect its absence.
