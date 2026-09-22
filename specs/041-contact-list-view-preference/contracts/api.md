# Contract: the JSON API

`routes/api.php`, version 1, authenticated by Sanctum like every other endpoint here.

## `GET /api/v1/me`

`UserResource` gains three attributes, beside `number_format`. Each is the recorded value, or `null`
when nothing has been recorded.

```json
{
  "type": "user",
  "id": "1",
  "attributes": {
    "number_format": null,
    "contact_list_grouping": "favourites",
    "contact_list_sort": "last-spoke",
    "contact_list_direction": "desc"
  }
}
```

| Attribute | Type | Values |
| --- | --- | --- |
| `contact_list_grouping` | string or null | `all`, `favourites`, `archived`, `names-only` |
| `contact_list_sort` | string or null | `name`, `nickname`, `last-spoke`, `added` |
| `contact_list_direction` | string or null | `asc`, `desc` |

## `PUT /api/v1/me/contact-list-view`

New, handled by `MeContactListViewController@update`, declared beside `PUT me/timezone` which is its
model. It returns `UserResource`, so the answer is the whole user and there is one place the
preference is read.

**Body**: all three required.

```json
{
  "grouping": "favourites",
  "sort": "last-spoke",
  "direction": "desc"
}
```

| Field | Rules |
| --- | --- |
| `grouping` | required, string, `Rule::enum(ContactGroupingEnum::class)` |
| `sort` | required, string, `Rule::enum(ContactSortEnum::class)` |
| `direction` | required, string, `Rule::enum(SortDirectionEnum::class)` |

**Answers**: `200` with the user, or `422` naming the fields that failed, or `401` unauthenticated.

All three are required rather than optional. The endpoint writes the view somebody wants remembered,
which is three choices together; a partial write would make "sort by nickname" mean "and keep whatever
grouping happened to be there", which is a different capability.

## Reading the list itself

`GET /api/v1/vaults/{vault}/contacts` is unchanged. It keeps taking `filter`, `query`, `sort` and
`direction` in the query string, and it neither reads nor writes the recorded view: an API client asks
for what it wants. Its `Rule::in(['asc', 'desc'])` on `direction` becomes
`Rule::enum(SortDirectionEnum::class)`, which accepts exactly the same two values.

## The MCP tool

`App\Mcp\Tools\ListContacts` holds the same rule and the same loose direction string, and follows the
same change. It exposes the API rather than holding rules of its own, so its behaviour is unchanged:
it neither reads nor writes the recorded view.

## Documentation

`resources/docs/api/03-profile.php`, which already documents `me` and `me/timezone`. The three
attributes join its `$user` sample and its `$userAttributes` table, and the endpoint gets a section
beside the time zone one. No new numbered page, so the portal navigation does not move.
