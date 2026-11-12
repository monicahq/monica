# 071. KnowledgeList

**Status:** in progress (`app/Models/KnowledgeList.php`)
**Spec:** specs/104-knowledge

## What it is

A table somebody keeps, inside one [KnowledgeFolder](069-knowledge-folder-in-progress.md): the books they have read, the paint references of each room. Its columns are named by its owner, and each item holds one text per column.

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account of the vault
vault_id                    int                             vault it belongs to
knowledge_folder_id         int                             folder it sits in, of the same vault
title                       string (255)                    required, "Untitled list" when created without one
description                 text, nullable                  Markdown, at most 5,000 characters
```

## Rules

- A new list starts with the columns "Name" and "Note".
- It keeps at least one [column](072-knowledge-list-column-in-progress.md); its [items](073-knowledge-list-item-in-progress.md) read in their order.
- Reached through the API (`/api/v1/vaults/{vault}/knowledge/lists`) and the MCP server. Every change is written to the changelog.
