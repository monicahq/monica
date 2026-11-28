# Implementation Plan: One OAuth sign-in for every client

**Branch**: `2026-11-26-unified-oauth-login` | **Date**: 2026-11-26 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `specs/166-unified-oauth-login/spec.md`

## Summary

Spec 165 added the mobile sign-in next to the assistants' one, with a client check in each place the
two differ. This change removes those checks instead of adding code:

- **One record**: `MobileSession` becomes `OAuthConnection`, kept for every client ([R1](research.md), R2, R3).
- **One set of rules**: consent every time, the replay rule and revocation apply to every client (R6, R10).
- **One list**: "Connected apps" in Security, and `oauth-connections` in the API (R11, R12).
- **One rule left about the client**: the scope binding, unchanged (R7).
- **Earlier assistants**: one connection each, written by the migration (R5).

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13
**Primary Dependencies**: Passport 13, Sanctum, Laravel MCP, Blade, Alpine.js; no new dependency
**Storage**: one table renamed and widened, no new table ([data-model.md](data-model.md))
**Testing**: PHPUnit (`tests/Unit/Actions`, `tests/Unit/ViewModels`, `tests/Feature/Controllers`, `tests/Feature/OAuth`)
**Target Platform**: web application, API v1, OAuth endpoints
**Project Type**: web application (Laravel monolith)
**Performance Goals**: one indexed query per token request for every client, as the mobile client has today
**Constraints**: no token, code or verifier stored readable; the Android app and MCP clients work unchanged (FR-015)
**Scale/Scope**: 3 migrations; 1 model, 6 actions and 1 middleware renamed; 1 action deleted; 2 controllers replace 3, 1 partial replaces 2

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. The five session actions are renamed and lose the client check; `EndOAuthConnection` gains the earlier-connection branch (R5). No rule moves into a middleware or a controller. |
| II. Simplicity | Pass. Fewer branches, files and routes than before; no new package |
| III. Tested before merged | Pass. Tests below |
| IV. Laravel conventions | Pass. `Schema::rename`, `Prunable`, named routes, inline validation |
| V. Localized and safe | Pass. New wording through `monica:localize`; ending a connection asks first and says it is final |
| VI. Nothing changes under anybody's feet | Pass, with one decided exception: the `mobile-sessions` API addresses of 2026-11-26 are replaced (spec FR-010). Every existing token, phone and assistant keeps working (FR-013). |
| VII. Stable core | Pass. Nothing about records or templates |
| VIII. Nothing disappears silently | Pass. Starting and ending are logged for every client; earlier log entries still read (R13) |
| IX. Accessible | Pass. The list is `x-list-panel` with `x-delete-action`, the contract the two partials already follow |
| X. Everything through the API | Pass. Assistants become listable and endable through the API, which they were not. No MCP tool (spec) |
| XI. Short record | Pass. No quickstart: the tests are the walkthrough |

Re-checked after Phase 1: unchanged.

## Project Structure

Documents: this plan, [research.md](research.md), [data-model.md](data-model.md), [contracts/api.md](contracts/api.md), and `tasks.md` from `/speckit-tasks`.

### Source Code (repository root)

```text
database/migrations/…_{rename_mobile_sessions_to_oauth_connections,add_scopes_to_oauth_connections_table,backfill_oauth_connections}.php  # new (R1, R3, R4, R5)
app/Models/OAuthConnection.php, database/factories/OAuthConnectionFactory.php  # renamed; constants (R14), name()
app/Models/User.php                                          # oauthConnections()
app/Actions/{Start,Renew,End,EndReplayed,Revoke}OAuthConnection.php  # renamed from …MobileSession (R2, R5, R10)
app/Actions/RevokeOAuthAccessToken.php                       # renamed from RevokeMobileAccessToken
app/Actions/DisconnectAssistant.php                          # deleted (R10)
app/Http/Middleware/TrackOAuthConnections.php                # renamed, no client check (R2); alias `oauth.connections`
app/Http/Middleware/BindOAuthScopes.php                      # consent for every client (R6), refusal by registered address (R7)
app/Http/Middleware/EnsureApiTokenIsAccepted.php             # constant only (R14)
app/Providers/AppServiceProvider.php                         # flag reads the scope (R8); constants
app/ViewModels/Auth/OAuthAuthorizationViewModel.php          # one wording (R9)
resources/views/app/auth/oauth-authorize.blade.php           # return address for every client (R9)
app/Http/Controllers/Api/OAuth/{Revocation,AuthorizationServerMetadata}Controller.php  # renamed action; `none` leaves (R6)
app/Http/Controllers/Api/OAuthConnectionController.php, app/Http/Resources/OAuthConnectionResource.php  # replace MobileSession… (R12)
app/Http/Controllers/Api/Auth/LoginController.php, Api/StatusController.php  # renamed action and constants
app/Http/Controllers/App/Settings/Account/Security/OAuthConnectionController.php  # replaces MobileSession… and ConnectedAssistant…
app/ViewModels/Settings/Account/Security/SecurityViewModel.php  # connections(), the notice (R11)
resources/views/app/settings/account/security/_connected-apps.blade.php, index.blade.php  # one section replaces two (R11)
app/Enums/UserActionEnum.php                                 # two cases (R13)
bootstrap/app.php, config/passport.php                       # middleware alias
routes/api.php (3 routes replace 3), routes/web.php (1 route replaces 2)
resources/docs/api/04-api-keys.php, 10-health.php, api-changelog/2026-11-26.php
docs/portal/en/10-your-account-and-security/{12-connected-assistants,20-mobile-app}.md  # one list, new wording
changelog/2026-11-26-<ulid>-connected-apps.md
lang/*.json, .claude/rules/translations.md                   # monica:localize, Sonnet subagents; glossary row "connection"
```

**Structure Decision**: the existing Laravel monolith. Every file above already exists under its old
name except the migration, the partial and the changelog entry.

**Tests**:

| Layer | Proves |
| --- | --- |
| `tests/Unit/Actions/*OAuthConnectionTest` (renamed) | every client gets a connection; replay ends it; an earlier connection is renewed and ended as a whole (R5); the log entries |
| `tests/Unit/Models/OAuthConnectionTest` | `active`, `prunable`, `name()` |
| `tests/Feature/OAuthConnectionBackfillTest` | seeds tokens, runs the backfill's `up()` twice: phone rows get `["api"]`, one earlier connection per person and assistant |
| `tests/Feature/BindOAuthScopesTest` | consent forced for an assistant; `none` dropped; the scope rule unchanged |
| `tests/Feature/OAuth/*` (renamed from `Mobile…`) | the same sign-in, renewal, replay and revocation run once as the mobile client and once as an assistant (SC-001) |
| `tests/Unit/ViewModels/…` | one consent wording; `connections()` lists phones and assistants; the notice |
| `tests/Feature/Controllers/…` | the three API routes, the settings route, logout, revocation for an assistant, the metadata without `none` |
| `RouteProtectionTest`, `ApiDocsReturnFieldsTest` | renamed model and routes |
| `tests/Feature/Mcp/McpOAuthTest` | existing cases unchanged (they ask for `mcp:use`, so the flag-off not found holds); one case added for a request with no scope (R8) |
