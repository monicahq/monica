# Phase 1 data model: API keys and logs endpoints

No table is created and no column is added. Both concepts already exist, and this feature only
decides which parts of them the API says out loud. The two tables below are described as they ship
today, followed by the two envelopes built from them.

## API key

Stored in `personal_access_tokens`, created by
`database/migrations/2026_08_01_000000_create_personal_access_tokens_table.php`, and read through
Sanctum's `PersonalAccessToken` model. A user reaches their own through `$user->tokens()`.

| Column | Type | Meaning | Exposed |
| --- | --- | --- | --- |
| `id` | big integer | Primary key. | Yes, as a string. |
| `tokenable_type`, `tokenable_id` | string, big integer | The model the key acts as, always the user today. | No. It is always the caller, so saying it adds nothing. |
| `name` | string | What the person called the key. | Yes. |
| `token` | string, unique | The hash of the key. | Never. Not in any response, in any form. |
| `abilities` | text, nullable | What the key may do. Unused: every key today grants everything. | No. It becomes an attribute the day scopes exist, which is additive. |
| `last_used_at` | timestamp, nullable | When the key last authenticated a request. Null while it never has. | Yes, as a Unix integer or null. |
| `expires_at` | timestamp, nullable | When the key stops working. Null when it never does. | Yes, as a Unix integer or null. |
| `created_at`, `updated_at` | timestamps | | Yes, as Unix integers. |

**Rules**

- A name is required, at least three characters, at most two hundred and fifty five. This is the
  rule the security screen already applies, and it moves to the API unchanged.
- A name is not unique. Two keys may share one, and the id is what tells them apart.
- The plain text value exists once, in the response that created it. Only the hash is written down.
- Creation and revocation each write a journal entry, through `CreateApiKey` and `DestroyApiKey`.

**Lifecycle**

```text
minted ──────────────► in force ──────────────► revoked (row deleted)
         used                       revoked by its holder,
    (last_used_at set)              by the logout endpoint,
                                    or by the security screen
```

Revocation is a real delete, not a flag, and it takes effect on the next request with no cache to
wait out. Revoking the key the current request is authenticating with is allowed and is how a key is
rotated from a script.

## Log entry

Stored in `logs`, created by `database/migrations/2026_07_31_000000_create_logs_table.php`, read
through `App\Models\Log`. An account reaches its own through `$account->logs()`.

| Column or accessor | Type | Meaning | Exposed |
| --- | --- | --- | --- |
| `id` | big integer | Primary key. | Yes, as a string. |
| `account_id` | big integer | The account the action happened in. | No. It is always the caller's. |
| `vault_id` | big integer, nullable | The vault touched, null for account level actions. | No, for now. There is no vault endpoint yet for it to point at. |
| `user_id` | big integer, nullable | Who acted. Null once that user is deleted, and null when the application acted alone. | No. `author` answers the question better. |
| `user_email` | string | The address that person had at the time. | No, but it is what `author` falls back to. |
| `action` | string | The name the code gave the event, also the translation key. | Yes, raw. |
| `parameters` | json, nullable | What the sentence interpolates. | Yes. |
| `loggable_type`, `loggable_id` | string, big integer, nullable | The object acted on, which may no longer exist. | No, for now. It carries no foreign key and may point at nothing. |
| `changes` | json, nullable | Each field that changed, with its value before and after. | No. See below. |
| `source` | string | Which interface the action came through: `web`, `api`, `mcp`, `import`, `automation`, `module`, `system`. | Yes. |
| `source_detail` | string, nullable | Which token, module or import run precisely. | No, for now. |
| `ip_address` | string, nullable | Where it came from. Null when the application acted alone. | Yes. |
| `created_at` | timestamp | When it happened. | Yes, as a Unix integer. |
| `author` (accessor) | string | The person's name, falling back to the address recorded at the time, and to a name for the application itself when nobody was behind it. | Yes. |
| `description` (accessor) | string | The sentence, translated into the caller's language, with the parameters interpolated and the right wording chosen for any number in it. | Yes. |

**Rules**

- A row is written once. The model throws `LogIsAppendOnly` on update and on delete, so the API
  offers no way to write, change or remove one. This is Principle VIII expressed in code.
- The journal belongs to the account, not to the person, so entries the application wrote on its own
  are visible rather than hidden.
- `changes` is deliberately withheld until the product specification settles how a value somebody
  marked sensitive may be kept. Adding it later is additive.

## Response envelopes

Both follow the envelope `UserResource` established: a `type`, a string `id`, the `attributes`, and
a `links.self` pointing at the endpoint that returns this one thing. Times are Unix integers, because
they carry no zone and the reader puts them in whichever one their audience sits in.

### `api_key`

```json
{
  "type": "api_key",
  "id": "3",
  "attributes": {
    "name": "Dundie awards bot",
    "last_used_at": 1756339200,
    "expires_at": null,
    "created_at": 1756252800,
    "updated_at": 1756252800
  },
  "links": { "self": "https://app.monicahq.com/api/v1/api-keys/3" }
}
```

### `log`

```json
{
  "type": "log",
  "id": "12",
  "attributes": {
    "author": "Michael Scott",
    "action": "api_key_creation",
    "parameters": { "name": "Dundie awards bot" },
    "description": "Created the API key Dundie awards bot.",
    "source": "web",
    "ip_address": "192.168.1.24",
    "created_at": 1756339200
  },
  "links": { "self": "https://app.monicahq.com/api/v1/logs/12" }
}
```

### A page of either

The envelope the reference promises under Getting started, and the same one for both lists: the rows
under `data`, and `page`, `per_page` and `total` under `meta`, with nothing else beside them.

```json
{
  "data": [],
  "meta": {
    "page": 1,
    "per_page": 25,
    "total": 0
  }
}
```

`total` is how many rows the caller can reach in all, not how many are on this page, so a client can
work out how many pages there are without walking them.
