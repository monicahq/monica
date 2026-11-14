# Research: Preview list and admission while registration is closed

Each decision reuses a mechanism the codebase already has. The spec has no open question left.

## D1. The switch is a catalogued instance setting, as registration is

- **Decision**: `PreviewListEnabled` in `InstanceSettingEnum` (group `SigningIn`, kind `Boolean`), on
  the key `monica.preview_list_enabled`, read from `MONICA_PREVIEW_LIST_ENABLED` with `false` as its
  default. Nothing in the catalogue changes: it is one more case, exactly as `RegistrationEnabled`.
- **Rationale**: the deployment supplies the value and the catalogue already gives the override on
  the Configuration screen, the password gate, the cache, the effect on the next request and the
  administrator log (`UpdateInstanceSetting`), which is FR-001 and a third of FR-013.
- **Rejected**: a variable with no override (`fixedAtDeployment()`), which every other switch of
  signing in allows; a Pennant flag (`.claude/rules/flags.md`: never an operator choice).

## D2. The root address sends a visitor to `/preview`

- **Decision**: `GET preview` (`auth.previewList.new`) renders `app.auth.preview-list`, and
  `HomeController::index()` redirects a visitor there, when `monica.preview_list_enabled` is on and
  `monica.registration_enabled` is off. Otherwise `/preview` answers not found. Every other branch is
  untouched. The card is built from `x-auth-page`, as the sign in screen is.
## D3. One table, state read from its dates

- **Decision**: `preview_list_entries`, in [data-model.md](./data-model.md). An entry is registered
  when `registered_at` is set, admitted when `admitted_at` is set, waiting otherwise. Only the SHA-256
  of the link token is stored, as `MagicLink` does.
- **Rejected**: a state column, which is a second fact to keep in step with the dates.

## D4. Joining says the same thing every time

- **Decision**: `JoinPreviewList` lowercases the address and creates a waiting entry with the current
  locale, unless a user or an entry already holds it, in which case it does nothing. The controller
  validates `email` with the rules sign up uses (`disposable_email` included, Turnstile when the
  instance has it), sits behind `throttle:6,1`, answers not found unless the list is open, and always
  redirects to `/preview` with the same status.
- **Rejected**: the `unique` rule (its error tells a visitor the address is known, against FR-005).

## D5. Admitting sends a notification to the entry

- **Decision**: `AdmitPreviewListEntry` stores a new token hash, an expiry 14 days ahead and
  `admitted_at`, notifies the entry with `PreviewListEntryAdmitted`, and logs
  `AdministratorActionEnum::PreviewListEntryAdmitted` with the address as a parameter. It refuses an
  entry that is registered or whose address holds an account, with `AddressAlreadyHoldsAnAccount`,
  which the controller turns into an error on the screen.
- **Rationale**: `PreviewListEntry` uses `Notifiable` and `HasLocalePreference`, so the email goes to
  its address in its language with no anonymous route. The notification is queued on `high` and does
  not implement `HasEmailType`: the journal of emails belongs to an account, and none exists yet.
## D6. Admitting the oldest is a loop over D5

- **Decision**: `AdmitOldestPreviewListEntries` takes a number from 1 to 100, reads that many waiting
  entries by `created_at` then `id`, and runs `AdmitPreviewListEntry` on each, skipping the ones it
  refuses. The screen says how many were admitted.
- **Rationale**: one rule, one test target. The ceiling keeps a request to 100 writes and 100 queued
  emails. Each admission is its own line in the administrator log, as FR-013 asks.

## D7. The link opens the existing sign up screen

- **Decision**: `GET register/admission/{token}` sits outside the `registration` group and renders
  `app.auth.register` with the address read only and the token in a hidden `admission` field, or
  answers not found. `EnsureRegistrationIsEnabled` lets a request through when its `admission` input
  is a usable token. The three sign up controllers pass the entry to `CreateAccount`, which takes the
  address from it, marks it verified before `Registered` fires, and sets `registered_at`.
- **Rationale**: `Registered` is what sends the verification email, and it sends none to a verified
  address. The sign up screen offers no outside provider today, so FR-009 asks for no change there.
- **Rejected**: keeping the token in the session (the API has none); a second sign up screen.

## D8. Removing deletes the row

- **Decision**: `RemovePreviewListEntry` deletes the entry and logs
  `AdministratorActionEnum::PreviewListEntryRemoved`. The screen asks first through `x-delete-action`.
- **Rationale**: principle VIII is about data inside an account disappearing unasked. This is an
  explicit, warned, logged removal of an address that never became an account.

## D9. Documents that change with it

- `resources/docs/api/02-authentication.php`: the register endpoint takes `admission`
  ([contracts/api.md](./contracts/api.md)).
- `docs/FEATURES.md` gets an entry (`feature-documentator` skill). `.env.example` does not list the variable.
