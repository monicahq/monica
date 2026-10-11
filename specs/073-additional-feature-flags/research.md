# Research: Additional feature flags

Read in the code on 2026-10-10. Specification 068's research holds the mechanism; only what differs is here.

## R1. Five classes, one shape, one exception

- **Decision**: `app/Features/{SmsNotifications,CardDav,VCardExport,FileUpload,McpServer}.php`, named
  `sms-notifications`, `carddav`, `vcard-export`, `file-upload`, `mcp-server`. The four shipping off
  copy `Marketplace`. `McpServer::resolve(?Account $account)` returns `true` for the null scope and
  `Feature::for(null)->active(self::class)` otherwise.
- **Rationale**: Pennant calls a resolver for the null scope only when its parameter allows null
  (`Decorator::canHandleNullScope()`), so the "every account" row resolves to on with nothing stored,
  and `DeactivateFlagForEveryAccount` still turns it off. No migration, no deploy step.
- **Rejected**: a migration writing the null scope row; a deploy note asking to switch MCP on.

## R2. CardDAV: after authentication, before Sabre

- **Decision**: append `'flag:carddav'` to `config/laravelsabre.php` `middleware`, after
  `AuthenticateWithApiKeyOnBasicAuth`, which calls `Auth::setUser()`. The DAV address block of
  `security/_api-keys.blade.php` is drawn only when the flag is on.
- **Rationale**: a client first asks without credentials and must receive the 401 challenge; gating
  before authentication would answer 404 to it and the device would never sign in. One middleware
  covers address books, the calendar and the whole book export (`VCFExportPlugin`).
- **Rejected**: no nodes from `nodes()`: an empty 207, synced as deletions.

## R3. MCP: the endpoint and the authorisation screen

- **Decision**: add `'flag:mcp-server'` to `Mcp::web()` in `routes/ai.php`, after `auth:sanctum,api`.
  The `Passport::authorizationView()` closure aborts 404 unless the flag is on for
  `$parameters['user']->account`.
- **Rationale**: approving and denying act on the request the screen stored in the session, so
  refusing the screen refuses both. Tokens stay valid (FR-006); the endpoint refuses them.

## R4. SMS: one gate in the model, the binding refuses the route

- **Decision**: `NotificationChannel::isOffered()` is false for the SMS row when the flag is off.
  `mayCarry()` requires it, which covers writing a notification, digests and delivery.
  `ResolvesModelInAccount` asks `isReachableThroughAnAddress()` (true by default) of what it finds;
  the channel answers `isOffered()`, so every `{channel}` route answers 404. The API index and
  `NotificationChannelsViewModel::rows()` skip it.
- **Decision**: `DeliverNotification` abandons a notification whose channel is not offered before
  calling `hand()`, the way it abandons one no longer warranted.
- **Rationale**: today a channel that cannot carry throws `ChannelCannotCarryIt`, and `stumble()`
  records it through `RecordChannelFailure`, which counts towards disabling the channel. FR-005
  forbids touching the channel.
- **Rejected**: an `abort_unless` in eight controllers (a ninth route forgets it).

## R5. vCard export

`flag:vcard-export` on both `contacts.vcard.show` routes; `ContactViewModel::vCardUrl()` is null when off.

## R6. File upload

- **Decision**: `flag:file-upload` on `settings.photo.update` and both `contacts.avatar.update`
  routes; the destroy routes stay open (FR-010). `FieldValueRules::fileRules()` takes the account's
  answer and, when off, gives every file, image and avatar field `prohibited` with the message
  "Uploads are unavailable on this account for now.", required or not. `ImportVCard::writeThePhoto()`
  returns early, leaving the avatar as it is.
- **Rationale**: validation is all or nothing (clarification 5). `FieldValueRules` is the one place
  the three forms holding files (sections, life events, entities) and the entity API build rules.
- **Decision**: the form components take an `uploadsAreOn` value from their view model: the file
  input is not drawn, a stored file stays shown and removable, and the notice of FR-014 replaces the
  input when a file is held or the field is required.

## R7. Documentation

- **Decision**: `flag:carddav` on `docs.carddav.*` and `flag:mcp-server` on `docs.mcp.*`.
  `DocsViewModel::areas()`, `llmsText()` and the "Your addresses" box skip a capability that is off.
  The vCard entry of `resources/docs/api/06-contacts.php` carries `'flag' => 'vcard-export'`, and the
  API docs, the OpenAPI document and `llms.txt` skip an entry whose flag is off.
- **Not covered**: the portal guide pages (`docs/portal/en/8-sharing-export-and-sync/*`) keep their
  prose and their links, which answer 404 while the flag is off for every account. Gating a portal
  page is a mechanism the portal lacks; noted as a follow up.

## R8. The suite runs with the capabilities on

- **Decision**: `TestCase::setUp()` calls `Feature::define()` with `true` for the four flags shipping
  off. A test of a gate turns the flag off for its account with `$account->features()->deactivate()`.
  Each class's own unit test calls `resolve()` directly to prove the shipped default.
- **Rationale**: 78 test files use these capabilities. A flag read queries `features`: docs tests need a database.
