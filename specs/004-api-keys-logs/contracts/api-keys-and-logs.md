# Contract: API keys and logs endpoints

Six endpoints under the version one prefix. Every one of them requires a bearer token, answers 401
without one, and is limited to sixty requests per minute per token. The shapes referenced here are
in [data-model.md](../data-model.md).

Base URL: `{APP_URL}/api/v1`.

| Verb | Path | Route name | Controller method |
| --- | --- | --- | --- |
| GET | `/api-keys` | `api.v1.apiKeys` | `ApiKeyController@index` |
| GET | `/api-keys/{apiKey}` | `api.v1.apiKeys.show` | `ApiKeyController@show` |
| POST | `/api-keys` | `api.v1.apiKeys.create` | `ApiKeyController@create` |
| DELETE | `/api-keys/{apiKey}` | `api.v1.apiKeys.destroy` | `ApiKeyController@destroy` |
| GET | `/logs` | `api.v1.logs` | `LogController@index` |
| GET | `/logs/{log}` | `api.v1.logs.show` | `LogController@show` |

Both `{apiKey}` and `{log}` are constrained to a positive integer, so a path that is not one never
reaches a controller.

The reference documents them under these section ids, which are the anchors on `/docs/api` and the
names the markdown views answer to (`/docs/api/{id}.md`): `api-keys-index`, `api-keys-show`,
`api-keys-create`, `api-keys-destroy`, `logs-index`, `logs-show`.

## GET /api-keys

Lists the keys of the caller, newest first.

**Query parameters**

| Name | Type | Required | Notes |
| --- | --- | --- | --- |
| `page` | integer | No | Which page to answer with. Defaults to one. |
| `per_page` | integer | No | Defaults to twenty five. Clamped between one and one hundred, rather than refused. |

**Response** 200, a page of `api_key` objects under `data`, with `page`, `per_page` and `total` under
`meta`. No secret value appears, in any of them, ever.

## GET /api-keys/{apiKey}

**Response** 200, `{"data": {api_key}}`.

**404** when the key belongs to somebody else, or does not exist. The two are indistinguishable on
purpose.

## POST /api-keys

**Body**

| Name | Type | Required | Rules |
| --- | --- | --- | --- |
| `name` | string | Yes | At least three characters, at most two hundred and fifty five. |

**Response** 201:

```json
{
  "data": { "type": "api_key", "id": "4", "attributes": {}, "links": {} },
  "token": "4|f9aB2cD3eF4gH5iJ6kL7mN8oP9qR0sT1uV2w"
}
```

`token` is the only place the key ever appears in readable form. It is not recoverable afterwards
and it is not returned by any other endpoint.

**422** with `errors.name` when the name is missing, too short or too long. No key is minted.

**Side effect**: a journal entry, action `api_key_creation`, carrying the name.

## DELETE /api-keys/{apiKey}

**Response** 204, empty.

**404** when the key belongs to somebody else. That key keeps working.

**Side effect**: a journal entry, action `api_key_deletion`, carrying the name the key had, read
before the row goes.

**Note**: a caller may revoke the key they are calling with. The call succeeds and every later
request with it answers 401.

## GET /logs

Lists the journal of the caller's account, newest first, including the entries the application wrote
on its own with nobody behind them.

**Query parameters**: `page` and `per_page`, exactly as for the keys.

**Response** 200, a page of `log` objects in the same envelope. The `description` of each is
translated into the language on the caller's account.

## GET /logs/{log}

**Response** 200, `{"data": {log}}`.

**404** when the entry belongs to another account.

## Not offered, on purpose

There is no `POST /logs`, no `PUT /logs/{log}` and no `DELETE /logs/{log}`. The journal is written by
the application and never edited: the model refuses an update or a delete by throwing, and the API
does not offer the operation in the first place.

There is no `PUT /api-keys/{apiKey}`. A key's name is what it was called when it was minted, and a
key that could be renamed after the fact would make the journal entry naming it misleading.

## Error shapes

The ones the framework already produces, unchanged.

| Status | When | Body |
| --- | --- | --- |
| 401 | No token, or a revoked one. | `{"message": "Unauthenticated."}` |
| 404 | Anything outside what the caller can reach, and anything that does not exist. | `{"message": "..."}` |
| 422 | A body that fails validation. | `{"message": "...", "errors": {"name": ["..."]}}` |
| 429 | More than sixty requests in a minute. | `{"message": "Too Many Attempts."}` |
