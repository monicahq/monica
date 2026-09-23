# Contract: the empty Relationships tab and the add form

## `GET /vaults/{vault}/contacts/{contact}/relationships/new?type={id}`

| Input | Rule |
| --- | --- |
| `type` | Optional. Used as the chosen type when it is one the account offers. Anything else (missing, not a number, another account's, switched off, in a switched off category) is ignored: the form opens with nothing chosen and answers 200 |
| `query` | Unchanged |
| `old('relationship_type_id')` | Still wins over `type` after a failed submission |

## The empty tab (`GET /vaults/{vault}/contacts/{contact}/relationships`, no relationship)

- No panel title and no family tree.
- One row per active category holding an active type, in the account's order.
- Each row: the category name, a line of up to four type names, and a link whose visible text is
  "Add" and whose `aria-label` is "Add to {category}", pointing at the URL above.
- With no such category: the sentence "Nobody is written down as connected to {name} yet." and a
  link to the relationship types settings.

## Unchanged

- The filled tab.
- `POST .../relationships` and its validation.
