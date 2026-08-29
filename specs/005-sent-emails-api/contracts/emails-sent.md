# Contract: Sent emails endpoints

**Base URL**: `{APP_URL}/api/v1` | **Auth**: `Authorization: Bearer {token}` (Sanctum)
**Rate limit**: 60 requests per minute, per token | **Locale**: taken from the account, via `set.locale`

Both endpoints are reads. There is no POST, PUT or DELETE anywhere in this contract, and none will be
added: the record is written as mail leaves.

## `GET /emails-sent`

List the emails sent on behalf of the caller's account, most recently sent first.

### Query parameters

| Name | Type | Required | Default | Description |
| --- | --- | --- | --- | --- |
| `page` | integer | no | `1` | Which page to answer with. |
| `per_page` | integer | no | `25` | How many emails one page holds, between 1 and 100. A number outside the range is brought back into it rather than refused. |

### Response `200`

`body` is **not** present on list rows.

```json
{
  "data": [
    {
      "type": "email_sent",
      "id": "42",
      "attributes": {
        "email_type": "new_login",
        "subject": "A new sign in to your account",
        "email_address": "michael.scott@dundermifflin.com",
        "delivery_status": "delivered",
        "sent_at": 1756339200,
        "delivered_at": 1756339260,
        "bounced_at": null,
        "created_at": 1756339200
      },
      "links": {
        "self": "{APP_URL}/api/v1/emails-sent/42"
      }
    },
    {
      "type": "email_sent",
      "id": "41",
      "attributes": {
        "email_type": "magic_link_created",
        "subject": "Your sign in link",
        "email_address": "dwight.schrute@dundermifflin.com",
        "delivery_status": "bounced",
        "sent_at": 1756252800,
        "delivered_at": null,
        "bounced_at": 1756252860,
        "created_at": 1756252800
      },
      "links": {
        "self": "{APP_URL}/api/v1/emails-sent/41"
      }
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 25,
    "total": 2
  }
}
```

An account that has never sent an email answers `200` with `"data": []` and `"total": 0`.

## `GET /emails-sent/{emailSent}`

Read one sent email, including the copy that was sent.

### Path parameters

| Name | Type | Required | Description |
| --- | --- | --- | --- |
| `emailSent` | integer | yes | The identifier of the email. Constrained to a positive integer by the route. |

### Response `200`

Everything a list row carries, plus `body`.

```json
{
  "data": {
    "type": "email_sent",
    "id": "42",
    "attributes": {
      "email_type": "new_login",
      "subject": "A new sign in to your account",
      "email_address": "michael.scott@dundermifflin.com",
      "body": "Somebody just signed in to your account from a device we have not seen before.",
      "delivery_status": "delivered",
      "sent_at": 1756339200,
      "delivered_at": 1756339260,
      "bounced_at": null,
      "created_at": 1756339200
    },
    "links": {
      "self": "{APP_URL}/api/v1/emails-sent/42"
    }
  }
}
```

## Field reference

| Field | Type | Nullable | Notes |
| --- | --- | --- | --- |
| `type` | string | no | Always `email_sent`. |
| `id` | string | no | The identifier, as a string. |
| `email_type` | string | no | Which email it was, e.g. `new_login`, `magic_link_created`, `email_verification`. |
| `subject` | string | yes | Null on records predating our keeping it. |
| `email_address` | string | no | The address it went to. |
| `body` | string | yes | The copy that was sent. Present only on the single email endpoint. |
| `delivery_status` | string | no | One of `bounced`, `delivered`, `sent`. |
| `sent_at` | integer | yes | Unix timestamp. |
| `delivered_at` | integer | yes | Unix timestamp. Null unless delivery was confirmed. |
| `bounced_at` | integer | yes | Unix timestamp. Null unless it came back. |
| `created_at` | integer | no | Unix timestamp of when the record was written. |
| `links.self` | string | no | Absolute URL of the single email endpoint. |

### `delivery_status`

| Value | Means |
| --- | --- |
| `bounced` | It came back. |
| `delivered` | It arrived. |
| `sent` | It left and nothing has come back yet. |

`bounced` takes precedence over `delivered`. When a provider reports both, and it can report them out
of order, the email reads as bounced.

## Errors

| Status | When |
| --- | --- |
| `401` | No token, a malformed token, or a token that has been revoked. |
| `404` | The email belongs to another account, or no email carries that identifier. The two are indistinguishable, deliberately: a probe must not be able to tell one from the other. |
| `429` | More than 60 requests in a minute with the same token. |

There is no `422` on either endpoint. Neither takes a body, and an out-of-range page size is clamped
rather than rejected.

## What this contract does not offer

Filtering, searching or sorting by anything. Cursor paging. Any write. Reading another account's mail,
including as an instance administrator. Each is listed under Out of Scope in the spec.
