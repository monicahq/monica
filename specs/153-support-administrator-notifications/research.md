# Research: Email the administrators about support requests

No question was left open by the specification. Each entry below is a decision read from the code.

## R1. Where the emails are sent

- **Decision**: from `OpenSupportRequest` and `AddSupportMessage`, after their transaction, next to
  the copy `OpenSupportRequest` already sends the user.
- **Rationale**: the web screens, the API and the MCP tools all call these two actions, so FR-006
  holds by construction. Administrator replies, notes and status changes go through other actions,
  so FR-005 holds by construction too.
- **Alternatives**: a model observer or an event and listener (indirection for two call sites).

## R2. Who receives them

- **Decision**: `SupportAvailability::administrators()`, without the author, compared by user id.
- **Rationale**: it is already the definition of "the people who answer support": active and holding
  the administrator capacity. Reading it when the action runs fixes the recipients at the moment the
  message is saved.
- **Alternatives**: a new query (a second definition of the same people).

## R3. The support switch

- **Decision**: the action sends nothing when `SupportAvailability::isEnabled()` is false.
- **Rationale**: the `support` middleware already guards every route, but an action can be called
  from a job, a command or a test. FR-012 is a property of the email, so the action owns it.

## R4. One notification per email

- **Decision**: `App\Notifications\SupportRequestOpened` and `App\Notifications\SupportMessageAdded`,
  each queued on `high` like `SupportRequestReceived`, each with its own `EmailType` case
  (`support_request_opened`, `support_message_added`). The names match the `UserActionEnum` cases
  the two actions already log.
- **Rationale**: FR-010 asks for a kind of email each. The two differ in subject, first line and the
  reopened sentence, so one class with a switch would hold two emails anyway.

## R5. What the notification carries

- **Decision**: plain values captured when the action runs (reference, subject, the category enum,
  the account's number, the author's name, the body, whether files are attached, whether a solved
  request was reopened, the admin URL), not the models.
- **Rationale**: the email reads what was true when the user wrote, as R2 does for recipients, and the
  queued job does not query. The category stays an enum so `__($category->shortLabel())` is rendered in the receiving
  administrator's language (FR-008), which `User` provides through `HasLocalePreference`.

## R6. The account's name

- **Decision**: one line names the author and the account's number together: the account's name is
  its person’s name (`AccountViewModel::title()`), and that person is the author.
- **Rationale**: an account holds exactly one user, so FR-003's "account name" and "user who opened it"
  are the same value. Printing it twice reads as two people.

## R7. Attachments

- **Decision**: a boolean and one sentence saying files are attached, never a count.
- **Rationale**: the count adds nothing the request page does not show, and a counted phrase would
  need `trans_choice()` in every locale for no gain.

## R8. Earlier decisions left alone

- `specs/114-support-requests` keeps its FR-009 as written. Specs record what was decided at the
  time; spec 153 says what it replaces.
- A failed delivery happens in the queue worker, after the user's request is answered (FR-007), as
  for every other email the product sends.
