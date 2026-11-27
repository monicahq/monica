# Tasks: One OAuth sign-in for every client

**Input**: `specs/166-unified-oauth-login/` (spec.md, plan.md, research.md, data-model.md, contracts/api.md)

**Tests**: Required by constitution principle III, one layer each (`.claude/rules/testing.md`). Data comes from The Office. A test file is renamed with the class it tests.

## Phase 1: Setup

- [X] T001 Read the skills (`actions`, `controllers`, `routes`, `migrations`, `models`, `middlewares`, `view-models`, `views`, `components`, `api-writer`, `api-docs-writer`, `passport-development`, `writer`, `changelog`, `translations`, `help-bubbles`, `definition-of-done`) and `.claude/rules/`

## Phase 2: Foundational (the rename, with behaviour unchanged)

**Goal**: one name for the record and its code, and the suite green before any rule changes.

- [X] T002 Create `database/migrations/…_rename_mobile_sessions_to_oauth_connections.php` with `make:migration`: rename the table, rename `device_name` to data-model.md `oauth_connections.label`, and make data-model.md `oauth_connections.access_token_id`, `oauth_connections.refresh_token_hash` and `oauth_connections.authorization_code_hash` nullable (research R1, R4, R5)
- [X] T003 Create `database/migrations/…_add_scopes_to_oauth_connections_table.php` adding data-model.md `oauth_connections.scopes`, with its `->comment()` (research R3)
- [X] T004 Rename `app/Models/MobileSession.php` to `app/Models/OAuthConnection.php` and its factory to `database/factories/OAuthConnectionFactory.php`: constants `MOBILE_CLIENT_ID`, `MOBILE_REDIRECT_URI`, `API_SCOPE` with the same values (research R14), `scopes` cast to array, `label` fillable, a `name()` method per data-model.md, the docblock rewritten for every client; rename `User::mobileSessions()` to `oauthConnections()` in `app/Models/User.php`
- [X] T005 Rename the six actions in `app/Actions/` (`Start`, `Renew`, `End`, `EndReplayed`, `Revoke` `…MobileSession` to `…OAuthConnection`, and `RevokeMobileAccessToken` to `RevokeOAuthAccessToken`) and their tests in `tests/Unit/Actions/`, with bodies unchanged apart from the names
- [X] T006 Rename `app/Http/Middleware/TrackMobileSessions.php` to `TrackOAuthConnections.php`, its alias to `oauth.connections` in `bootstrap/app.php` and `config/passport.php`
- [X] T007 Replace every remaining `MobileSession` reference found by `grep -rn "MobileSession\|mobileSessions" app routes tests database/factories` (never a migration already in `main`; none names the model) with names only, including in files a later phase rewrites or deletes (`SecurityViewModel`, the two `MobileSessionController`, `MobileSessionResource`, `BindOAuthScopes`, `EnsureApiTokenIsAccepted`, `StatusController`, `Api/Auth/LoginController`, `RevocationController`, `AppServiceProvider`, `tests/TestCase.php`, `RouteProtectionTest`, `MobileClientProvisioningTest`)
- [X] T008 Rename `tests/Unit/Models/MobileSessionTest.php` to `OAuthConnectionTest.php` and add `name()`: the label, or else the client's name; run `PAO_DISABLE=1 php artisan test --parallel` and expect green

## Phase 3: User Story 1 - Any client signs in the same way (P1)

**Goal**: no rule except the scope binding depends on the client. **Independent test**: the PKCE flow run as the mobile client and as a registered assistant does the same at every step.

- [X] T009 [US1] Add `OAuthConnectionCreation` and `OAuthConnectionEnd` to `app/Enums/UserActionEnum.php` per data-model.md `UserActionEnum`, keeping the three earlier cases and their labels (research R13)
- [X] T010 [US1] In `app/Actions/StartOAuthConnection.php`, read the client from the access token instead of `MOBILE_CLIENT_ID`, store data-model.md `oauth_connections.scopes` from that token, accept an answer with no refresh token, and log `OAuthConnectionCreation`; extend `tests/Unit/Actions/StartOAuthConnectionTest.php` with an assistant's token (research R2, R3)
- [X] T011 [US1] In `app/Http/Middleware/TrackOAuthConnections.php`, remove the client check so every token request is tracked, and start a connection when the answer holds an access token without a refresh token (research R2)
- [X] T012 [US1] In `app/Http/Middleware/BindOAuthScopes.php`, rewrite `prompt` for every client (`none` dropped, `consent` added) and send a refused scope back only to an address in the client's own `redirect_uris` (research R6, R7); extend `tests/Feature/BindOAuthScopesTest.php`: an assistant already allowed sees consent again, `prompt=none` shows consent, the scope rule is unchanged
- [X] T013 [US1] In `app/Providers/AppServiceProvider.php`, make `authorizationView` answer not found when the scopes asked hold `mcp:use` and the flag is off, with no client check (research R8); `it_answers_not_found_when_the_mcp_server_is_switched_off_for_the_person` in `tests/Feature/Mcp/McpOAuthTest.php` covers it, and add next to it that a request with no scope reaches the consent page
- [X] T014 [US1] In `app/ViewModels/Auth/OAuthAuthorizationViewModel.php` and `resources/views/app/auth/oauth-authorize.blade.php`, remove `isMobileApp()` and its branches: one title (`Connect :name`, kept), description, caution and notice naming the client and `config('monica.instance')`, and the return address shown as its host or else whole (research R9); rewrite `tests/Unit/ViewModels/Auth/OAuthAuthorizationViewModelTest.php` for the one wording, and the two `assertSee('Allow Monica for Android…')` of `tests/Feature/OAuth/MobileSignInTest.php`
- [X] T015 [US1] In `app/Http/Controllers/Api/OAuth/AuthorizationServerMetadataController.php`, remove `none` from `prompt_values_supported`; update its test in `tests/Feature/Controllers/Api/OAuth/` (contracts/api.md)
- [X] T016 [US1] In `app/Actions/RenewOAuthConnection.php`, when no connection matches the presented refresh token, move `last_used_at` of the earlier connection (null data-model.md `oauth_connections.authorization_code_hash`) of the token's person and client, and throw as today when there is none; extend its test (research R5)
- [X] T017 [US1] In `app/Actions/EndOAuthConnection.php`, log `OAuthConnectionEnd` with `name()`, and for an earlier connection revoke every token of that person and client that is not the current token of another active connection; extend its test with both cases (research R5, R10)
- [X] T018 [US1] Create `database/migrations/…_backfill_oauth_connections.php` with the query builder, idempotent: `["api"]` into `scopes` of rows that have none, then one earlier connection per person and assistant per data-model.md (research R5)
- [X] T019 [US1] Create `tests/Feature/OAuthConnectionBackfillTest.php`: seed a phone row without scopes and two live tokens of one assistant for Pam Beesly, run the migration's `up()` twice, expect `["api"]` on the phone row and exactly one earlier connection (depends on T018)
- [X] T020 [US1] Rename `tests/Feature/OAuth/MobileSignInTest.php` and `MobileSessionLifecycleTest.php` to `OAuthSignInTest.php` and `OAuthConnectionLifecycleTest.php`, and run sign-in, renewal and replay through a `#[DataProvider]` once as the mobile client and once as a registered assistant (SC-001); confirm the existing cases of `tests/Feature/Mcp/McpOAuthTest.php` pass unchanged

## Phase 4: User Story 2 - One list of what is connected (P1)

**Goal**: one section and one API resource. **Independent test**: two phones and one assistant listed together, one ended, the others untouched.

- [X] T021 [US2] In `app/ViewModels/Settings/Account/Security/SecurityViewModel.php`, replace `connectedAssistants()` and `mobileSessions()` with `connections()` (name, label, permissions, connected, last used) and rename `assistantsNotice()` to ask whether an active connection holds `mcp:use` while the flag is off (research R11); rewrite the matching cases of `tests/Unit/ViewModels/Settings/Account/Security/SecurityViewModelTest.php`
- [X] T022 [US2] Create `app/Http/Controllers/App/Settings/Account/Security/OAuthConnectionController.php` (`destroy`, through `EndOAuthConnection`) with the route `settings.oauthConnections.destroy` in `routes/web.php`; delete `MobileSessionController`, `ConnectedAssistantController`, their two routes, their tests and `app/Actions/DisconnectAssistant.php` with its test (research R10, contracts/api.md)
- [X] T023 [US2] Create `resources/views/app/settings/account/security/_connected-apps.blade.php` with `x-list-panel` and `x-delete-action`, include it once in `index.blade.php` under "Connected apps" with its `x-help`, and delete `_mobile-apps.blade.php` and `_connected-assistants.blade.php` (research R11)
- [X] T024 [US2] Create `tests/Feature/Controllers/App/Settings/Account/Security/OAuthConnectionControllerTest.php`: the section renders its copy, ending one redirects with its flash, another person's connection is not found; update the route list of `tests/Feature/RouteProtectionTest.php`
- [X] T025 [US2] With `api-writer`, replace `app/Http/Controllers/Api/MobileSessionController.php` and `app/Http/Resources/MobileSessionResource.php` by `OAuthConnectionController` and `OAuthConnectionResource`, and the three `mobile-sessions` routes of `routes/api.php` by `oauth-connections` (contracts/api.md)
- [X] T026 [US2] Replace `tests/Feature/Controllers/Api/MobileSessionControllerTest.php` by `OAuthConnectionControllerTest.php`: `$jsonStructure`, phones and assistants listed, another account's left out, `is_current`, delete answers 204 then 404
- [X] T027 [P] [US2] With `api-docs-writer`, document `oauth-connections` in `resources/docs/api/04-api-keys.php`, fix `10-health.php`, and state the replacement in `resources/docs/api-changelog/2026-11-26.php`; update `tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php`

## Phase 5: User Story 3 - A client signs itself out (P2)

**Goal**: revocation and logout end any client's connection. **Independent test**: revoke with an assistant's refresh token, then fail to renew and to call `/mcp`.

- [X] T028 [US3] Extend `tests/Feature/Controllers/Api/OAuth/RevocationControllerTest.php` and `tests/Unit/Actions/RevokeOAuthConnectionTest.php`: an assistant's refresh token ends its connection and nothing else; an unknown token answers 200 and changes nothing
- [X] T029 [US3] Update the comment of `app/Http/Controllers/Api/OAuth/RevocationController.php` and the logout case of `tests/Feature/Controllers/Api/Auth/LoginControllerTest.php` for the renamed actions, with behaviour unchanged

## Phase 6: Polish

- [X] T030 [P] With `writer`, merge `docs/portal/en/10-your-account-and-security/12-connected-assistants.md` and `20-mobile-app.md` around the one "Connected apps" list, and point the `x-help` guide at it
- [X] T031 [P] With `changelog`, write `changelog/2026-11-26-<ulid>-connected-apps.md`
- [X] T032 Add the glossary row "connection" to `.claude/rules/translations.md`, run `php artisan monica:localize`, translate with Sonnet subagents, and pass `php artisan monica:localize --check`
- [X] T033 Run the `definition-of-done` skill: `PAO_DISABLE=1 php artisan test --parallel`, PHPStan, `vendor/bin/pint --dirty --format agent`, and the `rules-reviewer` and `accessibility-reviewer` agents on the diff

## Dependencies

- Phase 2 blocks everything. T002 → T003 → T004 → T005 → T006 → T007 → T008.
- US1: T009 before T010 and T017; T010 before T011; T016, T017 before T018; T018 before T019; T020 last.
- US2 needs T017 (ending) and T010 (scopes). T021 → T023; T022 → T024; T025 → T026 → T027.
- US3 needs T011. Polish needs US2.
- Parallel: T012 to T015 touch different files; T027 with T024; T030 with T031.

## Strategy

Phases 2 to 4 are one increment: once every client is tracked (Phase 3), the two old lists show the wrong entries until Phase 4 replaces them. Phase 2 alone is a safe checkpoint, with behaviour unchanged. Phase 5 is tests over what Phase 3 already made true.
