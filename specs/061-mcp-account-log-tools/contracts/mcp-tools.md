# Contract: MCP tools for reading the account logs

Every tool is registered on `MonicaServer`, uses `MirrorsAnApiCapability`, is marked `#[IsReadOnly]`,
and returns what the API returns: `LogResource` or `EmailSentResource` resolved, and for the lists the
API's envelope. An identifier outside the key's account, or not a positive whole number, answers the
validation error `That log entry does not exist.` or `That email does not exist.`
([R5](../research.md#r5-not-found)). No tool writes to the account's activity.

## list-logs

Mirrors `api.v1.logs`. Description: "List what has been done in the account, newest first, a page at a
time: who did it, what it was, where it came from and the address it came from."

| Input | Type | Rules | Description |
| --- | --- | --- | --- |
| `page` | integer | nullable, integer | The page to read, starting at 1. |
| `per_page` | integer | nullable, integer | How many entries a page holds, up to the maximum (default and maximum stated from config). |

Answer:

```json
{
  "data": [ { "type": "log", "id": "12", "attributes": { "author": "…", "action": "…", "parameters": {}, "description": "…", "source": "mcp", "ip_address": "…", "created_at": 1790394294 }, "links": { "self": "…/api/v1/logs/12" } } ],
  "meta": { "page": 1, "per_page": 25, "total": 1 }
}
```

Ordered by `id` descending. `per_page` clamped to `[1, api_maximum_items_per_page]`, `page` below 1
read as 1, a page past the last answers `data: []` with the total.

## get-log

Mirrors `api.v1.logs.show`. Description: "Read one entry of the account's activity."

| Input | Type | Rules | Description |
| --- | --- | --- | --- |
| `log` | string | required, string | The identifier of the entry, as list-logs gives it. |

Answer: `LogResource` resolved.

## list-emails-sent

Mirrors `api.v1.emailsSent`. Description: "List the emails sent to the account, newest first, a page at
a time, and whether each arrived. The copy of each email is left out; read one to see it."

| Input | Type | Rules | Description |
| --- | --- | --- | --- |
| `page` | integer | nullable, integer | As `list-logs`. |
| `per_page` | integer | nullable, integer | As `list-logs`. |

Answer: the API's envelope of `EmailSentResource` rows, without `body`. Ordered by `sent_at`
descending, then `id` descending.

## get-email-sent

Mirrors `api.v1.emailsSent.show`. Description: "Read one email sent to the account: its kind, subject,
address, what it said with links and web addresses removed, and whether it arrived."

| Input | Type | Rules | Description |
| --- | --- | --- | --- |
| `email_sent` | string | required, string | The identifier of the email, as list-emails-sent gives it. |

Answer: `new EmailSentResource($email, withBody: true)` resolved, `body` included (null for a record
written before bodies were kept).

## Changed outside the tools

| Where | Change |
| --- | --- |
| `EmailSentResource` | `withBody` constructor argument replaces the `routeIs()` condition ([R3](../research.md#r3-the-email-body-reaches-the-mcp-tool)) |
| `Api\EmailSentController::show` | `new EmailSentResource($email, withBody: true)` |
| `CreateEmailSent::sanitize()` | removes `https?://` addresses after the Purify pass ([R4](../research.md#r4-web-addresses-are-removed-from-the-body-when-it-is-recorded)) |
