# Contract: notification channel setup and proof codes

Both routes sit in the authenticated `api/v1` group. `{channel}` is `in_app`, `email`, `telegram` or `sms`.
Responses carry the existing `NotificationChannelResource`, with two new attributes.

## New attributes on every channel

| Attribute | Type | Meaning |
| --- | --- | --- |
| `can_send_proof_code` | bool | `NotificationChannelPolicy::sendProofCode` allows a send now |
| `proof_code_refusal` | string or null | The policy's reason when it does not, as the screen prints it |

## `DELETE /notification-channels/{channel}/setup`

Removes what the account supplied to the channel ([research R1, R2](../research.md)).

| Case | Status | Body |
| --- | --- | --- |
| Telegram or text message, set up | 200 | The channel: `destination` null, `is_enabled` false |
| Telegram or text message, nothing set up | 200 | The channel, unchanged; nothing journalled |
| Text message withdrawn for the account | 200 | The channel cleared; the only route that reaches it |
| `in_app` or `email` | 422 | `errors.channel`: "This channel has nothing to remove." |
| Unknown name, or no row in the account | 404 | |

## `POST /notification-channels/{channel}/proof` (existing)

Sends a code, the first or another one. Unchanged on success: 201 with the channel. New 422 refusals,
on `errors.channel`, carrying the policy's wording:

| Refusal | When |
| --- | --- |
| Wait | Last send less than `notification_proof_resend_seconds` ago, two requests racing included |
| Limit | `notification_proof_maximum_sends` codes already sent to this setup |
| Not provable | A channel other than `sms` |
| Not configured | The instance has no text message provider |

A previous code stops being accepted once another is sent: `PUT .../proof` with it answers 422 on `code`.

## Web routes (same outcomes)

| Route | Name | Answer |
| --- | --- | --- |
| `DELETE settings/account/notifications/{channel}/setup` | `settings.notifications.setup.destroy` | Redirect to the index with a status, or `errors.channel` |
| `POST settings/account/notifications/{channel}/proof` (existing) | `settings.notifications.proof.store` | Refusals as `errors.proof` |

Removed: `POST settings/account/notifications/{channel}/reset` (`settings.notifications.reset`).
