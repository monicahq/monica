# 073. KnowledgeListItem

**Status:** in progress (`app/Models/KnowledgeListItem.php`, `app/Models/KnowledgeListCell.php`)
**Spec:** specs/104-knowledge

## What it is

One row of a [KnowledgeList](071-knowledge-list-in-progress.md). Its texts are cells, one per [column](072-knowledge-list-column-in-progress.md), kept in their own table so removing a column takes its cells with it.

## Fields

```
knowledge_list_items
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account of the vault
vault_id                    int                             vault of the list
knowledge_list_id           int                             list it belongs to
position                    int                             appended

knowledge_list_cells
id                          int                             primary key
account_id                  int                             account of the vault
vault_id                    int                             vault of the list
knowledge_list_item_id      int                             item it belongs to
knowledge_list_column_id    int                             column it fills, of the item's list
value                       text                            Markdown, at most 5,000 characters
```

## Rules

- One cell per item and column at most. A blank cell is no row.
- An item whose cells are all blank is not saved.
- Items read in their position; reordering them is left for later.
