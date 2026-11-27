# Research: One OAuth sign-in for every client

Decisions R1 to R16 of `specs/165-mobile-app-sign-in/research.md` stand unless one below replaces it.

**R1. Rename, do not rebuild.** `mobile_sessions` becomes `oauth_connections` through a new migration
(`Schema::rename`), and `MobileSession` becomes `OAuthConnection`. The rows of phones already signed in
stay where they are. Rejected: a second table for assistants, which is the duplication being removed;
editing the migration of spec 165, which is already in `main`.

**R2. The client check leaves the middleware.** `TrackMobileSessions` becomes `TrackOAuthConnections`
and acts on every token request. `StartOAuthConnection` reads the client from the access token it was
given instead of naming the mobile one, and records a connection even when the answer holds no
refresh token. The five session actions are renamed and keep their bodies.

**R3. Scopes are stored on the connection.** A `scopes` JSON column, written at the code exchange from
the access token. Rejected: reading them from the current access token, which `passport:purge` removes
while the connection is still renewable; deriving them from the client, which is a rule about the client.

**R4. The label.** `device_name` stays the request parameter (FR-015) and the column is renamed
`label`. A connection without one shows its client's name, which replaces "Unnamed phone".

**R5. Assistants connected before this change (FR-013).** A backfill migration of its own, idempotent
so a test can seed tokens and run it, inserts one row per person and
client holding a live access or refresh token, with no fingerprints and no current token: the three
hash columns and `access_token_id` become nullable, and a row without a code fingerprint is an
"earlier" connection.
- A renewal whose refresh token matches no connection, made by a person and client holding an earlier
  connection, moves its `last_used_at` and nothing else. Without one, it is reported as today.
- Ending an earlier connection revokes every token of that person and client that is not the current
  token of another active connection, which is what `DisconnectAssistant` did.
- Two limits follow from having no fingerprint, and both are today's behaviour: it has no replay rule
  (FR-007), and the revocation address cannot find it (FR-008). It leaves the list when ended, or 30
  days after its last renewal.
Rejected: one row per refresh token (the person sees the same assistant several times, decided
2026-11-26); ending them all.

**R6. Consent every time (FR-003).** `BindOAuthScopes` rewrites `prompt` for every client: `none` goes
and `consent` is added. `none` leaves `prompt_values_supported`. Rejected: answering
`consent_required` (decided 2026-11-26).

**R7. The scope rule is the one client rule left (FR-002).** `BindOAuthScopes` keeps it. Its refusal is
sent back to the client when the return address is one the client registered, and answered as JSON
otherwise, read from the client's own `redirect_uris` instead of the mobile constant.

**R8. The flag reads the scope (FR-004).** `Passport::authorizationView` answers not found when the
scopes asked hold `mcp:use` and the flag is off for the account, and never looks at the client. A
request with no scope now reaches the consent page there, and its token reaches nothing.
`EnsureMcpConnectionIsAccepted` and the `flag:mcp-server` middleware on `/mcp` are untouched.

**R9. One consent wording (FR-005).** `OAuthAuthorizationViewModel` loses `isMobileApp()` and its four
branches. The page says the client's name, the instance, the permissions asked, and the return
address: its host, or the whole address when it has none (a private scheme).

**R10. Ending a connection.** `EndOAuthConnection` is the one way: settings, API, `DELETE
/api/v1/logout`, revocation and replay all reach it. `RevokeOAuthConnection` needs no change beyond its name: it already matches
the client the request names, and every client now has a connection to find.
`DisconnectAssistant`, `ConnectedAssistantController` and their route are deleted.

**R11. Settings (FR-009, FR-012).** One partial, `_connected-apps`, fed by
`SecurityViewModel::connections()`, replaces `_mobile-apps` and `_connected-assistants`. Each row shows
the name, the label, the permissions, when it connected and when it was last used. Ending one goes
through `x-delete-action`. The notice of FR-012 is `assistantsNotice()` renamed, asking whether an
active connection holds `mcp:use` while the flag is off.

**R12. API (FR-010).** `oauth-connections` (index, show, destroy) replaces `mobile-sessions`, built
with `api-writer`. No MCP tool, as in spec 165.

**R13. Account log (FR-011).** Two new cases, written for every client. `MobileSessionCreation`,
`MobileSessionEnd` and `AssistantDisconnection` stay in the enum with their labels, so earlier entries
still read, and nothing writes them any more.

**R14. Constants.** `CLIENT_ID`, `REDIRECT_URI` and `SCOPE` move to `OAuthConnection` as
`MOBILE_CLIENT_ID`, `MOBILE_REDIRECT_URI` and `API_SCOPE`, with the same values. The `mobile` member of
`/api/v1/status` reads them there and answers as before.

**R15. Untouched.** Token lifetimes, the `oauth` rate limiter, `prompt=create`, the mobile client row,
dynamic registration, pruning (`Prunable`, same rule), `EnsureApiTokenIsAccepted`.
