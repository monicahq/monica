# Implementation Plan: Sign in from the mobile apps

**Branch**: `2026-11-26-mobile-app-sign-in` | **Date**: 2026-11-26 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `specs/165-mobile-app-sign-in/spec.md`
## Summary

Monica already runs a Passport authorization server for its MCP assistants (PKCE, rotating refresh
tokens, discovery). It is opened to the mobile apps rather than replaced:
- **Client and scope**: an `api` scope, bound to a first-party public client that a migration
  provisions with a fixed id.
- **Middlewares on Passport's routes**: they bind scopes to clients, force consent, and keep one
  `MobileSession` per phone.
- **Revocation and discovery**: an RFC 7009 revocation endpoint, discovery through `/api/v1/status`,
  and Monica's own authorization server metadata.
- **Token lifetime**: one hour.
- **Ending a session**: a "Mobile apps" section in Security and its API, both ending one phone
  through one action.

Decisions are in [research.md](research.md); there is no feature flag.

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13
**Primary Dependencies**: Passport 13, Sanctum, Laravel MCP, Blade, Alpine.js; no new dependency
**Storage**: one new table (`mobile_sessions`) and one `oauth_clients` row ([data-model.md](data-model.md))
**Testing**: PHPUnit (`tests/Unit/Actions`, `tests/Unit/ViewModels`, `tests/Feature/Controllers`, `tests/Feature`)
**Target Platform**: web application, API v1, OAuth endpoints
**Project Type**: web application (Laravel monolith)
**Performance Goals**: one extra indexed query per token request; nothing added to other API requests
**Constraints**: no token, code or verifier stored readable; assistants, API keys and web sign-in unchanged
**Scale/Scope**: 4 actions, 2 middlewares, 4 controllers, 5 routes, 1 migration pair, 1 settings partial

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. `StartMobileSession`, `RenewMobileSession`, `EndMobileSession`, plus a fix to `CreateApiKey`. The middlewares and controllers only read the request and delegate. |
| II. Simplicity | Pass. Reuses Passport (R1); no new package, no change to Passport's own tables |
| III. Tested before merged | Pass. Tests below |
| IV. Laravel conventions | Pass. Passport's `middleware` config key, named routes, inline validation, `Prunable` |
| V. Localized and safe | Pass. Consent and settings wording translated; ending a session needs confirmation |
| VI. Nothing changes under anybody's feet | Pass. Existing tokens, keys and assistants keep working; an assistant renews more often, unnoticed |
| VII. Stable core | Pass. Nothing about records or templates |
| VIII. Nothing disappears silently | Pass. Ending a session is logged; a replayed code or token ends its session, and the account log says so |
| IX. Accessible | Pass. The settings partial follows `_connected-assistants`; the consent page keeps its structure |
| X. Everything through the API | Pass. List and end sessions in the API. No MCP tool: none exists for keys or assistants (spec) |
| XI. Short record | Pass. No quickstart: the tests are the walkthrough |

Re-checked after Phase 1: unchanged.
## Project Structure
### Documentation (this feature)
```text
specs/165-mobile-app-sign-in/
├── plan.md
├── research.md
├── data-model.md
├── contracts/
│   ├── oauth.md
│   └── api.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)
```text
config/passport.php                                         # new: the two middlewares on Passport's routes (R2)
app/Http/Middleware/{EnsureApiTokenIsAccepted,BindOAuthScopes,TrackMobileSessions}.php  # new (R1, R2, R7)
app/Models/MobileSession.php                                # new, CLIENT_ID, REDIRECT_URI, scopeActive, Prunable
app/Actions/{Start,Renew,End}MobileSession.php              # new (R7, R8)
app/Actions/CreateApiKey.php                                # scopes refused to a non-key caller (R3)
app/Http/Controllers/OAuth/{Revocation,AuthorizationServerMetadata}Controller.php  # new (R9, R10)
app/Http/Controllers/Api/MobileSessionController.php        # new, index and destroy (R12)
app/Http/Controllers/Api/Auth/LoginController.php           # destroy ends a mobile session (R9)
app/Http/Controllers/Api/StatusController.php               # `mobile` block (R10)
app/Http/Controllers/App/Settings/Account/Security/MobileSessionController.php  # new, destroy
app/Http/Controllers/App/Auth/{Login,EmailVerification}Controller.php  # prompt=create, intended (R11)
app/ViewModels/Settings/Account/Security/SecurityViewModel.php  # mobileSessions(); assistants leave out the mobile client
app/ViewModels/Auth/OAuthAuthorizationViewModel.php         # mobile wording (R5)
app/Providers/AppServiceProvider.php                        # `api` scope, one-hour tokens, flag check skipped for mobile
app/Enums/UserActionEnum.php                                # two cases (R13)
config/monica.php                                           # mobile.minimum_android_version
database/migrations/…_create_mobile_sessions_table.php, …_provision_the_mobile_oauth_client.php
routes/api.php (guard, 2 routes), routes/web.php (1 route), routes/ai.php (metadata and revoke, next to the OAuth routes)
resources/views/app/settings/account/security/_mobile-apps.blade.php, index.blade.php
resources/views/app/auth/oauth-authorize.blade.php, login.blade.php
resources/docs/api/01-getting-started.php (status), 02-authentication.php, 04-api-keys.php, api-changelog/2026-11-26.php
docs/portal/en/…                                            # signing in from the app, ending a phone session
changelog/2026-11-26-<ulid>-mobile-app-sign-in.md
lang/*.json                                                 # through monica:localize, Sonnet subagents
```

**Tests**: one per new action, middleware and controller; additions to `CreateApiKeyTest`,
`SecurityViewModelTest`, the status, login and verification controller tests and `RouteProtectionTest`;
existing MCP OAuth, API key and web sign-in tests unchanged (SC-003).

## Complexity Tracking

| Addition | Why | Simpler alternative rejected |
| --- | --- | --- |
| Middlewares wired through `passport.middleware` and filtering by route name | Passport declares its routes in vendor; its own `middleware` key is the documented way to add to them (departs from the middlewares skill: group in a routes file) | `Passport::ignoreRoutes()` and redeclaring the routes copies vendor routes that change with each Passport release (R2) |
| `mobile_sessions` table | A stable handle per phone, for ending one phone and detecting replay | Passport tables: a new row at every renewal, and nothing links a renewal to its session |
