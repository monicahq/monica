# 072. KnowledgeListColumn

**Status:** in progress (`app/Models/KnowledgeListColumn.php`)
**Spec:** specs/104-knowledge

## What it is

One column of a [KnowledgeList](071-knowledge-list-in-progress.md), named by its owner. It is the second place user defined fields live outside templates, after the possession type's fields, and it holds text only: a column is a row, never a database column.

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account of the vault
vault_id                    int                             vault of the list
knowledge_list_id           int                             list it belongs to
name                        string (255)                    required
position                    int                             1 upward, no gap within the list
```

## Rules

- The last column of a list cannot be removed.
- Removing a column deletes its cells, and the screen warns first.
