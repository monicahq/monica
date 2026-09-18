# Contract: the contact vCard endpoint

The first endpoint in the `Api` namespace that does not answer in JSON. Everything it does that departs from the surrounding endpoints is listed here, so the departure is deliberate and reviewable.

## Request

```text
GET /vaults/{vault}/contacts/{contact}/vcard
```

`{vault}` and `{contact}` are addressed the way every other contact endpoint addresses them, by the readable name the record reports as `slug` or by its `id`. Both answer. There is no request body, no query string, and no way to ask for a different card format.

Authenticated by a Sanctum token, as every endpoint on `routes/api.php` is.

## Response

| | |
| --- | --- |
| Status | `200` |
| `Content-Type` | `text/vcard; charset=utf-8` |
| `Content-Disposition` | `attachment; filename="{contact-slug}.vcf"` |
| Body | the card itself, vCard 4.0, not wrapped in an object |

The body is byte for byte what the interface downloads for the same contact at the same reach (SC-007). No `data` envelope and no resource class, so there is no `$jsonStructure` to assert: the tests compare the body and the headers instead.

## What the token's scopes change

| Token | Body |
| --- | --- |
| Carries `sensitive-fields` | Every field value, including those the structure marks sensitive |
| Does not carry it | The same card with sensitive values absent. Not masked, not emptied, absent |

This is what `VCardMerger` already does for CardDAV. The endpoint chooses the flag. It does not implement the filtering.

## Refusals

| Case | Answer |
| --- | --- |
| Contact in another account | `404` |
| Contact reached through the wrong vault | `404` |
| Contact absorbed by a merge | `404`, as `contacts.show` already answers |
| No token, or a revoked one | `401` |

A contact belonging to somebody else and a contact that never existed answer identically. Nothing in the refusal says which it was.

## The published document

`BuildOpenApiDocument::answer()` hardcodes `application/json` for every status but 204, so this endpoint needs a `responseMediaType` key on its docs section, defaulting to the present behaviour. Without it, the published document tells clients to expect JSON here, which is worse than documenting nothing at all.

## The interface counterpart

`GET /vaults/{vault}/contacts/{contact}/vcard` on `routes/web.php`, same response shape, always at full reach because the reader is the account holder. It MUST be declared above the `{page?}` catch-all.
