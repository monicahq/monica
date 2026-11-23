# Contract: the reference entry of a QUERY request

> Superseded on 2026-11-23: QUERY requests no longer have pages of their own. Each is described on
> the page of its GET request. [research R12](../research.md#r12-one-page-for-get-and-query-2026-11-23) has the design, and the
> [spec](../spec.md) the requirements.

What a reader of the public portal can rely on. Example: the contact list of a vault, whose GET
entry id is written here as `contacts-index`.

## Addresses

| Address | Answers |
| --- | --- |
| `GET /docs/api/contacts-index` | the GET entry, unchanged apart from its sentence |
| `GET /docs/api/contacts-index-query` | the QUERY entry |
| `GET /docs/api/contacts-index-query.md` | the QUERY entry as `text/markdown` |
| `GET /docs/api/{unknown}-query` | 404, as any unknown id |

## The QUERY entry page

- Heading: "List contacts with QUERY", the GET title followed by "with QUERY".
- Method badge `QUERY`, the same address as the GET entry.
- One sentence: it is the QUERY form of the GET entry (linked), with the same parameters sent in the
  body, and a link to "How QUERY works" (the `query-method` guide).
- The GET description and body paragraphs.
- "Body parameters", or "Parameters" when path parameters stand beside them.
- Samples in cURL, JavaScript and PHP sending `QUERY` with a JSON body and
  `Content-Type: application/json`, or no body when there are no example values.
- The GET answer, return fields, status codes (with the 422 for a parameter sent twice when the
  endpoint takes parameters) and rate limit.

## The GET entry page

The sentence "This endpoint also answers QUERY…" links to its QUERY entry instead of the guide.

## Navigation

- Sidebar: under its resource, directly after its GET entry, label unchanged, badge `QUERY`.
- Previous and next: the QUERY entry sits between its GET entry and the entry after it.

## Markdown

- `/docs/api/{id}-query.md` holds the same content as the page.
- `/docs/api.md` prints every QUERY entry in full, directly after its GET entry.
- `/llms.txt` lists every QUERY entry by its title.

## Machine readable description

`/docs/api/v1/openapi`: unchanged paths and `operationId`s. The QUERY operation's `summary` becomes
the QUERY entry's title, and its 422 response says a parameter sent twice is refused.
