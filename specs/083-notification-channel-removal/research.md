# Research: Remove a Telegram or text message setup, and ask for another code

## R1. Clear the row, keep it

**Decision**: `ResetNotificationChannel` is renamed `RemoveChannelSetup` and clears every field FR-003
names, plus `verification_token_hash`. It keeps the row. `NotificationChannel::holdsSetup()` (a
destination, a code hash or a connection token) decides whether there is anything to remove: the view
model asks it to show the control, and the action returns without writing or journalling when it is
false. In app and email throw `ChannelIsNotValid` ("This channel has nothing to remove.").
**Rationale**: the screen and the API list only channels with a row, so deleting it hides the channel.
The current reset nulls `proof_expires_at` and keeps the token, which makes `isAwaitingConnection()`
true with no expiry: an old Telegram link would still connect.
**Rejected**: deleting the row; keeping the name `Reset` (the spec and the copy say "remove").

## R2. One route per surface, with its own query

**Decision**: `DELETE settings/account/notifications/{channel}/setup` (`settings.notifications.setup.destroy`)
and `DELETE api/v1/notification-channels/{channel}/setup` (`api.v1.notificationChannels.setup.destroy`).
Both controllers take `string $channel`, map it with `NotificationChannelEnum::tryFrom()`, and query the
signed in account's row, answering 404 when either is missing. The old `POST .../reset` route,
`NotificationChannelController::reset()` and `reset_url` are deleted, and the unwarned "Cancel this
connection" button in `_channel.blade.php` that posts to it is replaced by `x-delete-action`.
**Rationale**: route model binding refuses a channel that is not offered, which is exactly the withdrawn
text message channel FR-008 must reach. A string parameter is outside `RouteProtectionTest`'s binding
check; the controller test proves the account scoping instead.
**Rejected**: a separate "kept number" route (two routes for one operation); a flag on the trait.

## R3. The removal is not a policy ability

**Decision**: no policy method for the removal. The control is drawn where `holdsSetup()` is true, the
way the connect button is drawn where Telegram is not connected.
**Rationale**: `.claude/rules/policies.md` keeps a refused control visible, but FR-001 and FR-006 make the
control absent. The rule that could be stated twice (in app and email have nothing) is a property of
the channel, read once in the action.

## R4. Asking for another code is a policy ability

**Decision**: `NotificationChannelPolicy::sendProofCode(User, NotificationChannel): Response` refuses, in
order: a channel that does not prove silently, a channel not configured on the instance, a setup that
has used `notification_proof_maximum_sends`, and a send inside `notification_proof_resend_seconds` of
`proof_last_sent_at`. The view model calls `$user->inspect()` and draws the control `aria-disabled` with
the reason; `SendChannelProofCode` calls `$user->authorize()`. Its remaining `isSetUp()` check stays in
the action, reworded "Type a number before asking for a code." (the old text names a credential).
**Wording**: the time left is `trans_choice('You can ask for another code in :count second.|You can ask
for another code in :count seconds.', $seconds)`, rounded up, computed when the screen renders.
**Rationale**: the wait and the limit are standing properties the screen must show before anybody
clicks, which is the duplication a policy exists to remove.
**Rejected**: a live countdown in Alpine (a reload is enough after one minute).

## R5. At most one send inside the wait

**Decision**: `SendChannelProofCode` writes the new code with one conditional update, `where id = ?` and
`where proof_last_sent_at is null or proof_last_sent_at <= now - wait`, and sends only if it changed one
row; otherwise it throws `AuthorizationException` with the policy's wording.
**Rationale**: two requests can both pass `authorize()`; the conditional update lets only one through,
on SQLite, MySQL and PostgreSQL alike.
**Rejected**: `lockForUpdate()` (no row lock on SQLite).

## R6. Controllers and refusals

**Decision**: the web proof controller catches `ChannelIsNotValid` and `AuthorizationException` into
`withErrors(['proof' => ...])`; the API proof controller turns both into a 422 on `channel`. The setup
controllers return `withErrors(['channel' => ...])` and 422 for in app and email.

## R7. Journal

**Decision**: new case `NotificationChannelSetupRemoved = 'notification_channel_setup_removed'`, wording
"Removed the setup of :name", parameters `name` only. `NotificationChannelCredentialRemoved` is left as is.
**Rationale**: stored journal rows may hold the old value; removing a case is a separate change.

## Follow-up, not in this feature

Removing the setup, or changing the number, restarts the count of codes, so the limit bounds one setup
rather than one number. Closing that means remembering a hash of erased numbers.
