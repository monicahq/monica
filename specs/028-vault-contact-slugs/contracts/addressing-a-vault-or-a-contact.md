# Contract: addressing a vault or a contact

What changes for somebody holding an API token. Nothing here breaks a client that exists.

## Either form is accepted

Every method naming a vault or a contact takes the readable name or the uuid in that position. The 17 route definitions under `vaults/{vault}` currently constrain both to `[0-9a-fA-F-]{36}`; that constraint goes, and the record is found by either column inside the same account and vault checks as today.

```http
GET /api/v1/vaults/9f3c8b1e-...-4a2d/contacts/1a7e5c90-...-8b31
GET /api/v1/vaults/famille/contacts/jim-halpert
GET /api/v1/vaults/家族/contacts/田中太郎
```

The three reach the same record. A readable name written in a script of its own may be sent as its own characters or percent encoded; both are the same request.

## The readable name is reported

`VaultResource` and `ContactResource` gain one attribute. `id` keeps reporting the uuid, so nothing a client reads today changes meaning.

```json
{
  "type": "contact",
  "id": "1a7e5c90-...-8b31",
  "attributes": { "slug": "jim-halpert", "name": "Jim Halpert" },
  "links": { "self": "https://.../api/v1/vaults/famille/contacts/jim-halpert" }
}
```

`links.self` follows the account's preference, so it carries the uuid when readable addresses are off. A client that follows `links.self` rather than building its own is correct either way.

## What a client must not assume

- **A readable name is not stable.** It is rebuilt whenever the record is renamed, and the previous one stops answering with a 404. A client storing a reference stores the `id`.
- **A readable name is not reusable.** A name a record has released is never given to another, so a stale one fails rather than reaching somebody else.
- **A contact merged into another answers 404 here.** The explanation screen is a web screen. The API reports the merge through the methods under `vaults/{vault}/merges` that already exist.

## What is unchanged

Authentication, scopes, rate limits, pagination, error shapes and every other attribute of both resources. No method is added, removed or renamed, and the version stays `v1`: the change is additive on the way out and wider on the way in.

## Documented in

`resources/docs/api/05-vaults.php` and `resources/docs/api/06-contacts.php`, which the portal renders at `/docs`.
