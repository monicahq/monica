# Tasks: Sign in from the mobile apps

**Input**: `specs/165-mobile-app-sign-in/` (spec.md, plan.md, research.md, data-model.md, contracts/)

**Tests**: Required by constitution principle III, one layer each (`.claude/rules/testing.md`). Data
comes from The Office. Existing MCP OAuth, API key and web sign-in tests must pass unchanged.

## Phase 1: Setup

- [X] T001 Read the skills (`actions`, `controllers`, `routes`, `migrations`, `models`, `middlewares`, `config`, `view-models`, `views`, `components`, `api-writer`, `api-docs-writer`, `passport-development`, `writer`, `changelog`, `translations`, `help-bubbles`) and `.claude/rules/` (`code-style.md`, `testing.md`, `policies.md`, `mcp.md`, `translations.md`) before writing code
- [X] T002 Create `config/passport.php` (`config` skill) holding Passport's `middleware` key, and a named limiter `oauth` (30 per minute per IP) in `app/Providers/AppServiceProvider.php`; confirm with `php artisan route:list --name=passport -v` that the group middleware reaches `passport.token` and `passport.authorizations.authorize` (research R2)

## Phase 2: Foundational (blocks every story)

- [X] T003 Create the migration `database/migrations/…_create_mobile_sessions_table.php` with every column of data-model.md `MobileSession.*` (`id`, `user_id`, `oauth_client_id`, `device_name`, `access_token_id`, `refresh_token_hash`, `previous_refresh_token_hash`, `authorization_code_hash`, `last_used_at`, `ended_at`, timestamps), each with its `->comment()`, `up()` only
- [X] T004 Create `app/Models/MobileSession.php` and its factory: constants `CLIENT_ID` (one fixed UUID) and `REDIRECT_URI` (`com.monicahq.android:/oauth/callback`), the `user` relationship, `scopeActive` and `Prunable` per the rules of data-model.md `MobileSession`; add `mobileSessions()` to `app/Models/User.php`
- [X] T005 [P] Create `tests/Unit/Models/MobileSessionTest.php`: `active()` leaves out ended sessions and sessions unused for 31 days; `prunable()` takes exactly those
- [X] T006 Create the migration `database/migrations/…_provision_the_mobile_oauth_client.php`: `insertOrIgnore` of the row in data-model.md `oauth_clients` (`id`, `name`, `secret`, `redirect_uris`, `grant_types`, `revoked`) (research R4)
- [X] T007 [P] Create `tests/Feature/MobileClientProvisioningTest.php`: after migrating there is one client with id `MobileSession::CLIENT_ID`, public, with the exact redirect URI; running the migration's `up()` again leaves one row
- [X] T008 [P] Add `MobileSessionCreation` and `MobileSessionEnd` to `app/Enums/UserActionEnum.php` (data-model.md `UserActionEnum`), with their labels and translation keys
- [X] T009 Create `app/Actions/EndMobileSession.php` (research R8): it takes `User $user`, `MobileSession $session` and `bool $fromPhone`. It refuses with `ModelNotFoundException` a session of another user or one already ended. It revokes the Passport access token `access_token_id` and its refresh token, sets data-model.md `MobileSession.ended_at`, and dispatches `LogUserAction(MobileSessionEnd)` with the device name
- [X] T010 Create `tests/Unit/Actions/EndMobileSessionTest.php`: the access and refresh tokens are revoked and `ended_at` is set; another session of the same user is untouched; another user's session is refused; an ended session is refused; the log is written (depends on T009)

## Phase 3: User Story 1, sign in from the app on any instance (P1) 🎯 MVP

**Goal**: The app signs in with PKCE and calls the API. **Independent test**: run a full PKCE flow for the mobile client, then `GET /api/v1/me` with the token.

- [X] T011 [US1] In `app/Providers/AppServiceProvider.php` (research R1, R5, R6):
  - `Passport::tokensCan()` gains `api`;
  - `tokensExpireIn` becomes one hour, with its comment updated;
  - `authorizationView` skips the `mcp-server` flag for `MobileSession::CLIENT_ID`.
- [X] T012 [US1] In `app/ViewModels/Auth/OAuthAuthorizationViewModel.php` and `resources/views/app/auth/oauth-authorize.blade.php`, give the mobile client its own title and sentence (app name, `config('monica.instance')`, what it can do). Extend `tests/Unit/ViewModels/Auth/OAuthAuthorizationViewModelTest.php` for the mobile and the assistant wordings
- [X] T013 [US1] Create `app/Http/Middleware/BindOAuthScopes.php` (research R2) and list it with `throttle:oauth` in `config/passport.php`:
  - authorize and token refuse `api` for any other client and `mcp:use` for the mobile client, redirecting or answering with `invalid_scope` (contracts/oauth.md);
  - for the mobile client, `consent` is added to `prompt`.
- [X] T014 [US1] Create `tests/Feature/Middleware/BindOAuthScopesTest.php` (depends on T013):
  - an assistant client asking for `api` gets `invalid_scope`;
  - the mobile client asking for `mcp:use` gets `invalid_scope`;
  - the mobile client sees the consent page even after a previous approval;
  - an assistant's flow is unchanged.
- [X] T015 [P] [US1] Create `app/Actions/StartMobileSession.php` (research R7). It stores data-model.md `MobileSession.access_token_id`, `.refresh_token_hash` and `.authorization_code_hash`, cleans `.device_name` with `TextSanitizer::plainText` (empty becomes null), and sets `.last_used_at`. It logs `MobileSessionCreation`. Test it in `tests/Unit/Actions/StartMobileSessionTest.php`: only hashes are stored, a device name holding markup is cleaned, the log is written
- [X] T016 [US1] Create `app/Http/Middleware/TrackMobileSessions.php` (code exchange only), listed in `config/passport.php`. After a successful `authorization_code` answer to the mobile client, it reads the `jti` from the access token payload and calls `StartMobileSession` with the request's `device_name`. It does nothing for other clients (depends on T015)
- [X] T017 [US1] Create `app/Http/Middleware/EnsureApiTokenIsAccepted.php` (403 for a Passport token without `api`). In `routes/api.php`, move the protected group to `auth:sanctum,api` plus this middleware (contracts/api.md)
- [X] T018 [US1] In `app/Actions/CreateApiKey.php`, refuse any scope when the caller is not a key holding it (research R3). Extend `tests/Unit/Actions/CreateApiKeyTest.php`: a mobile token cannot give `sensitive-fields`
- [X] T019 [US1] Create `tests/Feature/OAuth/MobileSignInTest.php`, which runs the whole flow with PKCE S256 as Pam Beesly:
  - the code is exchanged, the token reaches `GET /api/v1/me`, and one `MobileSession` exists;
  - a wrong verifier or a redirect URI off by one character gets `invalid_grant`;
  - "Deny" returns `access_denied` and creates nothing;
  - the mobile token is refused on `/mcp`, and an assistant token gets 403 on `/api/v1/me`;
  - a mobile token does not reach sensitive fields.

## Phase 4: User Story 2, an instance tells the app how to sign in (P1)

**Goal**: A public description of the instance for the app. **Independent test**: read the status and the metadata, at the root and under a path.

- [X] T020 [P] [US2] Add `mobile.minimum_android_version` (`MONICA_MINIMUM_ANDROID_VERSION`, default null) to `config/monica.php` and `.env.example` (`config` skill)
- [X] T021 [US2] Add the `mobile` block to `app/Http/Controllers/Api/StatusController.php` (contracts/api.md), and extend its test in `tests/Feature/Controllers/Api/StatusControllerTest.php`: client id, scope, `registration_enabled` following `monica.registration_enabled`, and the minimum version (depends on T020)
- [X] T022 [US2] Create `app/Http/Controllers/OAuth/AuthorizationServerMetadataController.php` and its route in `routes/ai.php`, before `Mcp::oauthRoutes()`, with `throttle:60,1` (research R10, contracts/oauth.md)
- [X] T023 [US2] Create `tests/Feature/Controllers/OAuth/AuthorizationServerMetadataControllerTest.php`: every member MCP served keeps its value; the revocation endpoint, the scopes and the prompt values are added; with `APP_URL` under `/monica`, every address starts with it (depends on T022)

## Phase 5: User Story 3, stay signed in and sign out one phone (P2)

**Goal**: Renewal with rotation, replay detection, and signing out one phone. **Independent test**: two phones, renew both, sign out one, the other still works.

- [X] T024 [P] [US3] Create `app/Actions/RenewMobileSession.php` (research R7): it moves data-model.md `MobileSession.refresh_token_hash` into `.previous_refresh_token_hash`, then stores the new `.refresh_token_hash`, `.access_token_id` and `.last_used_at`. Test it in `tests/Unit/Actions/RenewMobileSessionTest.php`
- [X] T025 [US3] Extend `app/Http/Middleware/TrackMobileSessions.php`. A successful `refresh_token` answer calls `RenewMobileSession`. Before Passport answers, a presented code matching an `authorization_code_hash`, or a refresh token matching a `previous_refresh_token_hash`, calls `EndMobileSession` (depends on T024, T009)
- [X] T026 [US3] Create `app/Http/Controllers/OAuth/RevocationController.php` and its route `POST oauth/revoke` in `routes/ai.php` with `throttle:oauth` (contracts/oauth.md, research R9)
- [X] T027 [US3] Create `tests/Feature/Controllers/OAuth/RevocationControllerTest.php` (depends on T026):
  - it ends only the matching session;
  - an unknown or already revoked token gets 200 and nothing changes;
  - a missing `token` gets 400 `invalid_request`;
  - a matching token of another client changes nothing.
- [X] T028 [US3] In `app/Http/Controllers/Api/Auth/LoginController.php`, `destroy()` ends the session matching the token's `jti` when the token is a Passport token. Extend `tests/Feature/Controllers/Api/Auth/LoginControllerTest.php`; keys behave as before
- [X] T029 [US3] Create `tests/Feature/OAuth/MobileSessionLifecycleTest.php` with two phones, Jim and Dwight's devices for one user:
  - renewal rotates, and the old refresh token gets `invalid_grant`;
  - an old refresh token presented again ends its session;
  - an exchanged code presented again ends its session;
  - signing out one phone through revoke leaves the other phone, an assistant and an API key working.

## Phase 6: User Story 4, see and end phone sessions from the web or the API (P2)

**Goal**: The "Mobile apps" section and its API. **Independent test**: sign in from two phones, end one from settings and the other through the API.

- [X] T030 [US4] In `app/ViewModels/Settings/Account/Security/SecurityViewModel.php`, add `mobileSessions()`: active sessions with device name (data-model.md `MobileSession.device_name`, "Unnamed phone" when null), signed in and last used. Make `connectedAssistants()` leave out `MobileSession::CLIENT_ID`. Extend `tests/Unit/ViewModels/Settings/Account/Security/SecurityViewModelTest.php`
- [X] T031 [US4] Create `app/Http/Controllers/App/Settings/Account/Security/MobileSessionController.php` (`destroy`, which calls `EndMobileSession` from the web) and its route `settings.mobileSessions.destroy` in `routes/web.php` (contracts/api.md). Add `tests/Feature/Controllers/App/Settings/Account/Security/MobileSessionControllerTest.php` and a `RouteProtectionTest` entry
- [X] T032 [US4] Create `resources/views/app/settings/account/security/_mobile-apps.blade.php`, modelled on `_connected-assistants.blade.php`:
  - a list of the sessions;
  - "End session" behind an `x-modal.warning`;
  - an empty state when no phone is signed in.

  Include it in `index.blade.php` next to connected assistants.
- [X] T033 [US4] Create `app/Http/Controllers/Api/MobileSessionController.php` (`index`, `destroy`) and the routes `api.mobileSessions` and `api.mobileSessions.destroy` in `routes/api.php`, with the `api-writer` skill (contracts/api.md). `is_current` is computed from the calling token's `jti`
- [X] T034 [US4] Create `tests/Feature/Controllers/Api/MobileSessionControllerTest.php` (depends on T033):
  - the list shape, newest first, with `is_current`;
  - ending a session answers 204;
  - another user's session answers 404;
  - an ended session answers 404.

## Phase 7: User Story 5, sign up from the app (P3)

**Goal**: `prompt=create` leads a guest to registration, then back to consent. **Independent test**: as a guest, register, confirm the email, reach the consent page.

- [X] T035 [US5] In `app/Http/Controllers/App/Auth/LoginController.php`, `new()` handles an intended authorize URL whose `prompt` holds `create` (research R11): redirect to `auth.register.new` when registration is open; otherwise show the login page with `__('This instance does not accept new accounts.')` (`resources/views/app/auth/login.blade.php`)
- [X] T036 [US5] In `app/Http/Controllers/App/Auth/EmailVerificationController.php`, use `redirect()->intended(route('home.index'))`
- [X] T037 [US5] Extend the login and verification controller tests (`tests/Feature/Controllers/App/Auth/`):
  - with registration open, the guest lands on registration;
  - with registration closed, the notice shows;
  - after verification, the person lands on the authorize URL;
  - without an intended URL, the flow is unchanged.

## Phase 8: Polish

- [X] T038 [P] With the `api-docs-writer` skill:
  - document the `mobile` block in `resources/docs/api/01-getting-started.php`;
  - document sign-in for apps (OAuth, PKCE, the API scope, revoke) in `02-authentication.php`;
  - document the mobile session endpoints next to API keys in `04-api-keys.php`;
  - add entries to `resources/docs/api-changelog/2026-11-26.php`.
- [X] T039 [P] Add a product guide page under `docs/portal/en/` (`writer` skill) on signing in from the Android app and ending a phone's session. Update any page stating that assistant tokens last a day
- [X] T040 [P] Write `changelog/2026-11-26-<ulid>-mobile-app-sign-in.md` with a screenshot of the "Mobile apps" section (`changelog` skill)
- [X] T041 Run `php artisan monica:localize`, then translate every new string in every locale through Sonnet subagents (`translations` skill)
- [X] T042 Run the `accessibility-reviewer` and `rules-reviewer` agents on the diff; fix what they confirm
- [X] T043 Run the `definition-of-done` skill. The existing MCP OAuth, API key and web sign-in tests must pass unchanged (SC-003)

## Dependencies

- T002 blocks T013, T016, T026. Phase 2 (T003 to T010) blocks every story.
- US1 blocks US3: renewal and revocation need sessions created at sign-in.
- US2, US4 and US5 only need Phase 2 and T011.
- Phase 8 comes after the stories. T041 comes after every wording exists, and T043 comes last.

## Parallel examples

- In Phase 2: T005, T007 and T008 together.
- After Phase 2: T015, T020, T024 and T030 together, then T022, T033 and T035.
- In Phase 8: T038, T039 and T040 together.

## Strategy

The MVP is US1 plus US2: signing in and discovery, enough for the Android app to start. US3 then
adds renewal and signing out one phone, US4 the "Mobile apps" section, and US5 sign-up from the app.
