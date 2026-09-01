# Contract: Vault endpoints

**Feature**: [../spec.md](../spec.md) | **Date**: 2026-09-01

Base `/api/v1`. Every endpoint sits inside the existing `auth:sanctum` group, so all five inherit
`throttle:60,1` and `set.locale` without saying so. An unauthenticated or revoked credential answers
401 on all five.

## The vault object

```json
{
  "type": "vault",
  "id": "9d4f1c2e-7b3a-4f88-9a2d-6c1e5b0f7a31",
  "attributes": {
    "name": "Famille",
    "description": "Everybody at home, and the people we see because of them.",
    "colour": "#3f5fd0",
    "position": 1,
    "is_default": true,
    "contact_count": 42,
    "created_at": 1756252800,
    "updated_at": 1756339200
  },
  "links": {
    "self": "https://example.com/api/v1/vaults/9d4f1c2e-7b3a-4f88-9a2d-6c1e5b0f7a31"
  }
}
```

`id` is the vault's uuid, as a string. It is the only address the API accepts. `description` is `null`
when the vault has none. `colour` is one of eight values, lowercase:
`#3f5fd0`, `#4f8a49`, `#9a6a24`, `#9a3f2b`, `#6b4fa8`, `#2f7f86`, `#a8447a`, `#4a4a52`. Timestamps are
Unix integers, and `updated_at` may be `null`.

## GET /vaults

List the vaults of the caller's account, in the order the account arranged them.

**Query parameters**

| Name | Type | Required | Default | Notes |
| --- | --- | --- | --- | --- |
| `page` | integer | no | 1 | Which page to answer with. |
| `per_page` | integer | no | 25 | Between 1 and 100. A number outside the range is brought back into it rather than refused. |

**200**

```json
{
  "data": [ { "…the vault object…": null } ],
  "meta": { "page": 1, "per_page": 25, "total": 2 }
}
```

An account holding no vault answers 200 with an empty `data` and a `total` of 0. Deleted vaults are
never listed.

## GET /vaults/{vault}

**Path parameters**

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `vault` | uuid | yes | The vault's `id`. A path that is not a uuid answers 404 without a query being run. |

**200** `{ "data": { …the vault object… } }`

**404** A vault of another account, a vault already deleted, and a uuid that never named anything are
one answer, told apart by nothing.

## POST /vaults

Open a compartment. It goes at the end of the list. If the account held no vault, this one becomes the
vault records land in by default, whether or not `is_default` was sent, because exactly one vault has
to be that and there is nothing else to be it.

**Body parameters**

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `name` | string | yes | Up to 255 characters. Markup and surrounding blank space are stripped. |
| `description` | string | no | Up to 65535 characters. Absent, empty or null all mean the vault has none. |
| `colour` | string | no | Exactly one of the eight, in lower case. Absent means `#3f5fd0`. |
| `is_default` | boolean | no | True makes this the vault records land in by default, taking the title from whichever vault held it. False and absent change nothing. |
| `template_version_id` | integer | no | The published version of one of your active contact templates. New contacts in this vault start from it. Absent means the account's shipped one. |

**201** `{ "data": { …the vault object… } }`

**422** Names every field that is wrong. A colour outside the eight, a name over the limit or missing,
and a `template_version_id` that is not a published version of an active contact template of yours are
each refused, and nothing is created.

## PUT /vaults/{vault}

Replace the wording of the vault. What the vault holds is untouched, and so is its place in the list.

This endpoint replaces rather than patches, so all three fields must be stated. `colour` is required
and `description` must be present even when it is null, so that a caller renaming a vault cannot
silently reset its colour or erase what it is for.

**Path parameters** as `GET /vaults/{vault}`.

**Body parameters**

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `name` | string | yes | Up to 255 characters. |
| `description` | string or null | yes, may be null | Must be stated. Null clears it. |
| `colour` | string | yes | One of the eight. |
| `is_default` | boolean | no | As on creation. False and absent change nothing. |
| `template_version_id` | integer | no | As on creation. Contacts already created keep the version they were created on. |

**200** `{ "data": { …the vault object… } }`

**404** A vault of another account or one already deleted, told apart from a uuid that named nothing by
nothing. The vault is left as it was.

**422** As on creation, and the vault is left as it was.

## DELETE /vaults/{vault}

Delete the vault and everything it separates from the rest. There is no second step and no
confirmation: this call is the confirmation.

What goes with it:

- The contacts it holds, and the records of the kinds the account invented, marked as having left with
  the vault.
- Every private link onto the vault or onto the contacts it holds stops opening, permanently. Bringing
  the vault back does not reopen them.

What happens around it:

- If this vault was the one records land in by default, the first vault left takes the title. If none
  is left, the account has no default, which is the state every account starts in.
- The last vault of an account may be deleted. The account is then left holding nothing.

The deletion is soft, so nothing is destroyed on disk, but no endpoint brings a vault back today.
Treat it as final.

**Path parameters** as `GET /vaults/{vault}`.

**204** No body.

**404** A vault of another account or one already deleted. Nothing is deleted.

## Route table

| Method | URL | Route name |
| --- | --- | --- |
| GET | `/api/v1/vaults` | `api.v1.vaults` |
| GET | `/api/v1/vaults/{vault}` | `api.v1.vaults.show` |
| POST | `/api/v1/vaults` | `api.v1.vaults.create` |
| PUT | `/api/v1/vaults/{vault}` | `api.v1.vaults.update` |
| DELETE | `/api/v1/vaults/{vault}` | `api.v1.vaults.destroy` |

`{vault}` is constrained to `[0-9a-fA-F-]{36}`. The documented path stays `/vaults/{vault}`, which is
what `ApiDocsViewModel::registeredRoutes()` compares against.

## What the journal records

Every write leaves the same entry as the same operation performed in a browser, because the same
operation is what runs.

| Endpoint | Journal entries |
| --- | --- |
| POST | vault creation. Plus a default vault change, and a default template change, if either was asked for. |
| PUT | vault update, naming the values before and after. Plus the same two if either was asked for. |
| DELETE | vault deletion, naming the vault. |

Reading a vault or the list of them records nothing, matching how reading the journal records nothing.

## Not in this contract

- Reordering vaults.
- Bringing a deleted vault back.
- Reading which structure new records in a vault start from, which is held per vault and per kind of
  record and needs an endpoint shaped for that.
- Narrowing what a credential may do. Credentials with a limited reach do not exist yet, so any valid
  key can call `DELETE` as readily as `GET`.
