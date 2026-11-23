# Data Model: QUERY requests in the API reference

> Superseded on 2026-11-23: QUERY requests no longer have pages of their own. Each is described on
> the page of its GET request. [research R12](research.md#r12-one-page-for-get-and-query-2026-11-23) has the design, and the
> [spec](spec.md) the requirements.

No table and no migration. The entities are the section arrays in `resources/docs/api/*.php` and the
`ApiDocsSection` objects built from them.

## Section array (GET, as written today)

Unchanged in the files. After derivation it carries one more key:

| Key | Value |
| --- | --- |
| `querySectionId` | `"{id}-query"`, set only on a GET section in a group that is not a guide |

## Derived QUERY section array

Built from one GET section array by `ApiDocsViewModel::withQuerySections()`. Every key not listed is
copied from the GET array unchanged (description, body, response, returnFields, rateLimit, auth,
examplePath, flags, responseStatus, responseMediaType, responseIsFile).

| Key | Value |
| --- | --- |
| `id` | `"{GET id}-query"` |
| `method` | `"QUERY"` |
| `title` | `"{GET title} with QUERY"` |
| `label` | the GET label (or its title when it has no label) |
| `pathParams` | the GET `pathParams` |
| `queryParams` | none |
| `bodyParams` | the GET `queryParams` |
| `statusCodes` | the GET `statusCodes`, plus the 422 for a parameter sent twice when `bodyParams` is not empty ([R5](research.md#r5-status-codes)) |
| `derivedFromSectionId` | the GET `id` |

## ApiDocsSection

Two new nullable readonly properties, read from the array:

| Property | Set on | Used for |
| --- | --- | --- |
| `querySectionId` | a GET entry with a twin | the sentence linking to the QUERY entry |
| `derivedFromSectionId` | a derived QUERY entry | the sentence linking to the GET entry and the guide; skipping it in the return field test |

## Ordering

Within a group, each QUERY entry follows its GET entry directly. `sections()` keeps file order then
group order, so the sidebar, previous and next, and the whole reference Markdown follow it.

## Rules

- A GET section removed by `offeredToEveryAccount()` has no twin, because derivation runs after the
  filter.
- A guide group is never derived.
- Ids stay unique; the existing unique id test covers the derived ones.
