# Data model: Relationships empty state

No table, column or migration changes. The feature reads two existing models.

## RelationshipCategory (read)

| Field | Used for |
| --- | --- |
| `key` | Choosing the icon; `null` for a category the account added |
| `name` | The row title, translated for a supplied category |
| `is_active` | Only active categories are shown |
| `position` | Row order |

## RelationshipType (read)

| Field | Used for |
| --- | --- |
| `id` | The `type` the Add link carries |
| `name` | The line under the category |
| `is_active` | Only active types count and are named |
| `position` | Which type is first, and the order of the names |

## Shapes the view models return

`RelationshipTypeChoices::groups()`, extended:

```text
{ key: ?string, label: string, types: [{ value: int, label: string, name: string, roleA, roleB, isSymmetric }] }
```

`RelationshipsBlock::emptyCategories()`:

```text
[{ label: string, icon: string, line: string, addUrl: string, addLabel: string }]
```

## Rules

- A category appears only when it is active and holds at least one active type.
- The line names at most four types.
- `addUrl` carries the id of the category's first active type.
- `RelationshipFormViewModel` uses a preselected type only when `typeMap()` holds it; otherwise the
  form opens with no type chosen.
