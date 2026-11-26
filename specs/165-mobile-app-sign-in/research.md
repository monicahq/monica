# Research: Sign in from the mobile apps

The app's own decisions are in `monicahq/android`, `specs/002-multi-instance-auth/research.md`. Here:
what Monica does, checked against the code on 2026-11-26.

**R1. Reuse Passport.** `/api/v1` moves from `auth:sanctum` to `auth:sanctum,api`, as `/mcp` already
does, plus `EnsureApiTokenIsAccepted`, which answers 403 to a Passport token without the `api` scope
(the mirror of `EnsureMcpConnectionIsAccepted`). `Passport::tokensCan()` gains `api`.
Rejected: a flow ending in a Sanctum key (never expires); the password login (forbidden by RFC 9700).

**R2. Two middlewares on Passport's route group**, set through a new `config/passport.php`
(`'middleware' => [...]`, Passport's own key). Each acts only on its routes and only for the mobile
client.
- `BindOAuthScopes`, on authorize and token: `api` is only for the mobile client and `mcp:use` never
  is (`invalid_scope`). For the mobile client it adds `consent` to `prompt`, because Passport otherwise
  approves silently through `hasGrantedScopes`.
- `TrackMobileSessions`, on token: it creates, rotates and ends sessions (R7).
Rejected: `Passport::ignoreRoutes()` with the routes declared again, which copies vendor routes.

**R3. Rights of a mobile token.** It is not a `PersonalAccessToken`, so every existing
`instanceof PersonalAccessToken` check already denies it sensitive fields and administration.
Two call sites change: `CreateApiKey` refuses any scope to a non-key caller (today it skips the
check, so a mobile token could mint a key with sensitive fields), and `Api\Auth\LoginController::destroy`
ends the mobile session (R9). `AgentFeedbackController` already handles both kinds of token.

**R4. The mobile client.** A migration does `insertOrIgnore` with a fixed UUID,
`MobileSession::CLIENT_ID`. Its name is "Monica for mobile", it is public with no secret, its grants
are `authorization_code` and `refresh_token`, and its redirect URI is `MobileSession::REDIRECT_URI`.
Migrations run on install and on every upgrade through `monica:setup`, so this is idempotent by
construction. Rejected: dynamic registration per phone, which leaves one client per install; an
Artisan command, which is a manual step.

**R5. Consent page.** `Passport::authorizationView` keeps the `mcp-server` flag check for every client
except the mobile one. `OAuthAuthorizationViewModel` gives the mobile client its own title and
sentence: the app, the instance (`config('monica.instance')`), and "everything in your account, like
the web app".

**R6. One-hour access tokens for every client.** Passport has one lifetime per grant. Assistants
already renew. The comment in `configurePassport` and any documentation stating "a day" change with it.

**R7. Mobile sessions.** `TrackMobileSessions` reads the request and Passport's JSON answer:
- **Code exchanged** → `StartMobileSession`: `jti` from the access token's payload (our own answer),
  SHA-256 of the refresh token and of the code, `device_name` through `TextSanitizer::plainText`.
- **Renewal** → `RenewMobileSession`: found by the presented refresh-token hash, which becomes the
  previous hash; new hash, new `jti`, `last_used_at`.
- **Code or previous refresh token again** → `EndMobileSession`, then Passport refuses (RFC 9700).

`access_token_id` is a plain string, because `passport:purge` deletes token rows. `MobileSession` is
`Prunable` (ended, or unused for 30 days), so the scheduled `model:prune` removes it.

**R8. `EndMobileSession` action** (user, session): it revokes the access token and its refresh token,
sets `ended_at`, and logs it. Every way a session ends goes through it, through `RevokeMobileSession`
(revoke) and `EndReplayedMobileSession` (replay) when only a token is known.

**R9. Revocation and logout.** `POST /oauth/revoke` (RFC 7009) is handled by `OAuth\RevocationController`,
outside the Passport group and throttled at 30 per minute. It looks the session up by refresh-token
hash, and answers 200 to anything well formed. `DELETE /api/v1/logout` with a Passport token finds
the session by `jti`.

**R10. Discovery.**
- `/api/v1/status` gains `mobile`, built from `MobileSession` and config. The minimum Android version
  comes from `config('monica.mobile.minimum_android_version')`, set by `MONICA_MINIMUM_ANDROID_VERSION`.
- `/.well-known/oauth-authorization-server` is served by `OAuth\AuthorizationServerMetadataController`,
  registered in `routes/ai.php` before `Mcp::oauthRoutes()`, which then skips its own route (it checks
  with `hasGetRoute`). Throttled at 60 per minute.

**R11. Sign up from the app.** A guest on `/oauth/authorize` is sent to the login page, and Laravel
keeps the authorize URL as the intended one. `LoginController::new` sees an intended authorize URL
with `create` in its `prompt` and redirects to registration when it is open, or shows the login page
with a "this instance does not accept new accounts" notice when it is not. `EmailVerificationController`
then uses `redirect()->intended()`. Done here rather than in R2, because the group middleware runs
before the session starts.

**R12. Web and API.**
- **Settings**: a `_mobile-apps` partial fed by `SecurityViewModel::mobileSessions()`, ended through
  `MobileSessionController::destroy` with `x-modal.warning`; `connectedAssistants()` leaves out the client.
- **API**: `Api\MobileSessionController` (`index`, `destroy`), built with `api-writer`. No MCP tool, no webhook.

**R13. Account log.** New `UserActionEnum` cases `MobileSessionCreation` and `MobileSessionEnd`,
holding the device name.
