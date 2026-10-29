# Research: List every session of an account and remove any of them

## R1. The sessions table is the list

**Decision**: the list reads the `sessions` table the framework already writes. `user_id`, `ip_address`,
`user_agent` and `last_activity` are filled by `DatabaseSessionHandler::addUserInformation()` on every
save (`vendor/laravel/framework/src/Illuminate/Session/DatabaseSessionHandler.php:225-255`). A live
session is a row of the signed in user whose `last_activity` is within `config('session.lifetime')`
minutes, the boundary `gc()` deletes at (line 301). No migration and no model: the rows are read with the
query builder, since nothing in the application owns them.
**Rationale**: the spec records nothing new, and a model over a framework table invites relationships
and factories for rows the framework writes.
**Rejected**: an Eloquent `Session` model; a table of the application's own.

## R2. Another store cannot be listed

**Decision**: `SessionListViewModel::canList()` is `config('session.driver') === 'database'`. When false
the section shows a notice and no row, as `_passkeys-unavailable.blade.php` does. The action does not
check the driver: with no row to find it answers not found.
**Rationale**: FR-009. The file, cache and cookie drivers keep no index by user. The default is
`database`, in `.env.example` and on the hosted instance alike.

## R3. Removing a session deletes its row and retires the remembered credential

**Decision**: `DestroySession` deletes the row `where id = ? and user_id = ?`, throws
`ModelNotFoundException` when nothing was deleted, then sets a new `remember_token` the way
`UpdateUserPassword` does (`setRememberToken(Str::random(60))`, which is what
`SessionGuard::cycleRememberToken()` does at line 743), and journals.
**Rationale**: deleting the row ends the live session: the next request from that device starts empty
and the `auth` middleware sends it to sign in. Its remembered cookie carries the old token, which
`retrieveByToken()` no longer matches, so it is not signed back in (FR-005). One token per account (072)
means every other remembered cookie, the one in hand included, goes stale with it (FR-006, clarified on
2026-10-26). The device in hand carries on: `SessionGuard::user()` reads the live session before it
looks at the recaller, and `AuthenticateSession` compares password hashes, which do not change.
**Rejected**: `logoutOtherDevices()`, which rehashes the password, needs it typed, and ends every other
session rather than one; re-issuing the recaller cookie on the device in hand, which the clarification
declined.

## R4. The session in hand cannot be destroyed

**Decision**: the action takes `currentSessionId` and throws `CannotDeleteTheCurrentSession` when asked
for it; the controller redirects back with the refusal as the flash error, as
`RelationshipCategoryController` does. The screen never offers it (FR-002).
**Rationale**: deleting the row of the request in flight changes nothing, since the store writes it back
when the response is sent. Signing out is the way to end it, and the refusal says so.

## R5. The device in words

**Decision**: `App\Helpers\DeviceDescription::fromUserAgent(?string $userAgent): string` reads the
browser (Edge, Opera, Chrome, Firefox, Safari, in that order, since each later one also names the ones
before it) and the system (iPhone, iPad, Android, Windows, macOS, Linux) with one regular expression
each, and renders `__(':browser on :system')`, the known half alone, or `__('Unknown device')`. Product
names are not translated.
**Rationale**: FR-001 asks for words a person recognises; `ShareLinkViewModel::agentOf()` prints the raw
string, which is not that. **Rejected**: `jenssegers/agent`, a dependency change needing approval for six names.

## R6. Screen, route and confirmation

**Decision**: a section "Where you are signed in" on Security and access, after Two-factor
authentication: a header counting the sessions, one `x-row` per session with the device as title and the
address and last use as description, a "This device" `x-badge` on the one in hand, and an
`x-delete-action` with `confirms-password` on every other, whose body names the device and says every
device will ask to sign in again. `DELETE settings/account/security/sessions/{session}`, named
`settings.sessions.destroy`, `whereAlphaNumeric`, in the `password.confirm:auth.confirmPassword.new`
group with the API keys (FR-007). `SecurityController` hands `$request->session()->getId()` to the view
model, since a view model reads the database, configuration and copy, not the request.
**Rationale**: the pattern of the API keys and the passkeys on the same screen.

## R7. Tests on the array driver

**Decision**: `phpunit.xml` runs sessions on the `array` driver, so tests write rows into `sessions`
themselves and set `session.driver` to `database` where a view model or the screen is read. FR-005 is
proven end to end in `tests/Feature/Auth/RemovedDeviceSignOutTest.php`: a request carrying a recaller
cookie built as `PasswordlessRecallTest::recallerFor()` builds one is signed in before the removal and
sent to sign in after it, while a live session of the same person carries on.

## R8. Journal

**Decision**: `UserActionEnum::SessionRemoved = 'session_removed'`, "Removed the session on :device",
parameter `device` only (FR-008): no address and no identifier.
