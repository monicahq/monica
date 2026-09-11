# HTTP contract

Route names are camelCase, URLs kebab-case, verbs first, no query strings. Principle X requires the
JSON API to reach everything the screens reach, so each capability appears in both tables.

## Signed-in screens (`routes/web.php`)

| Verb | URL | Name | Purpose |
| --- | --- | --- | --- |
| GET | `notifications` | `notifications.index` | The bar's list, a bounded page of it (FR-031, FR-035) |
| PUT | `notifications/{notification}/read` | `notifications.read.update` | Mark one read (FR-032) |
| PUT | `notifications/read` | `notifications.readAll.update` | Mark every one read, after confirming |
| GET | `settings/account/notifications` | `settings.notifications.index` | Every channel, its state, its setup steps (FR-015, FR-039) |
| PUT | `settings/account/notifications/{channel}` | `settings.notifications.update` | Switch on or off, save destination and credential |
| DELETE | `settings/account/notifications/{channel}` | `settings.notifications.destroy` | Remove the credential, which switches the channel off (FR-040) |
| POST | `settings/account/notifications/{channel}/proof` | `settings.notifications.proof.store` | Send a code to the destination (FR-042) |
| PUT | `settings/account/notifications/{channel}/proof` | `settings.notifications.proof.update` | Type the code back |
| POST | `settings/account/notifications/{channel}/test` | `settings.notifications.test.store` | Send a test and report the outcome (FR-017) |

The bar itself renders inside `x-bar-links`, so it needs no route of its own until it is opened.
`{channel}` is a `NotificationChannelEnum` value, resolved as a route binding and 404 on anything
else.

## JSON API (`routes/api.php`)

| Verb | URL | Name | Purpose |
| --- | --- | --- | --- |
| GET | `notifications` | `api.notifications.index` | Paginated, filterable by read state |
| GET | `notifications/{notification}` | `api.notifications.show` | One, by uuid |
| PUT | `notifications/{notification}/read` | `api.notifications.read.update` | Mark one read |
| PUT | `notifications/read` | `api.notifications.readAll.update` | Mark every one read |
| GET | `notification-channels` | `api.notificationChannels.index` | Every channel and its state |
| GET | `notification-channels/{channel}` | `api.notificationChannels.show` | One |
| PUT | `notification-channels/{channel}` | `api.notificationChannels.update` | Switch on or off, set destination and credential |
| DELETE | `notification-channels/{channel}` | `api.notificationChannels.destroy` | Remove the credential |
| POST | `notification-channels/{channel}/proof` | `api.notificationChannels.proof.store` | Send a code |
| PUT | `notification-channels/{channel}/proof` | `api.notificationChannels.proof.update` | Confirm a code |
| POST | `notification-channels/{channel}/test` | `api.notificationChannels.test.store` | Send a test |
| GET | `notification-preferences` | `api.notificationPreferences.show` | Quiet hours, delivery mode, digest, timezone |
| PUT | `notification-preferences` | `api.notificationPreferences.update` | Change them |

## Shapes

A channel never returns its credential, in either surface. It returns `channel`, `label`,
`is_enabled`, `destination`, `needs_destination`, `needs_credential`, `has_credential`,
`requires_proof`, `is_proven`, `disabled_reason`.

A notification returns `uuid`, `type`, `channel`, `title`, `body`, `scheduled_for`, `sent_at`,
`read_at`, `failure_reason`, and a link to what it is about.

## Refusals

| Case | Response |
| --- | --- |
| A notification or channel in another account | 404, through `resolveRouteBinding()` |
| Switching on a channel with no destination | 422, naming what is missing (FR-010) |
| Switching on an unproven channel that proves silently | 422, saying the code is still needed |
| A wrong, expired or over-attempted code | 422, saying which of the three (FR-045) |
| An unknown `{channel}` value | 404 |
