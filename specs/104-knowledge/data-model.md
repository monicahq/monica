# Data Model: Knowledge

Every table: `id`, `uuid` (route key, cells excepted), `account_id` and `vault_id` (cascade on delete), timestamps.

## knowledge_folders

| Column | Type | Rule |
| --- | --- | --- |
| name | string(255) | required, trimmed |
| position | unsigned integer | 1 upward, no gap within the vault |

## knowledge_notes

| Column | Type | Rule |
| --- | --- | --- |
| knowledge_folder_id | foreign key | a folder of the same vault, cascade on delete |
| title | string(255) | required; "Untitled note" when created without one |
| body | longText, nullable | Markdown, at most 200,000 characters |

## knowledge_lists

| Column | Type | Rule |
| --- | --- | --- |
| knowledge_folder_id | foreign key | a folder of the same vault, cascade on delete |
| title | string(255) | required; "Untitled list" when created without one |
| description | text, nullable | Markdown, at most 5,000 characters |

## knowledge_list_columns

| Column | Type | Rule |
| --- | --- | --- |
| knowledge_list_id | foreign key | cascade on delete |
| name | string(255) | required |
| position | unsigned integer | 1 upward, no gap within the list; at least one column per list |

## knowledge_list_items

`knowledge_list_id` (cascade on delete) and `position` (appended), nothing else.

## knowledge_list_cells

| Column | Type | Rule |
| --- | --- | --- |
| knowledge_list_item_id | foreign key | cascade on delete |
| knowledge_list_column_id | foreign key | cascade on delete; a column of the item's list |
| value | text | Markdown, at most 5,000 characters; a blank cell is no row |

Unique on (`knowledge_list_item_id`, `knowledge_list_column_id`). No uuid: a cell is reached through its item.

**Relationships**: `Vault` hasMany `KnowledgeFolder` (by position). `KnowledgeFolder` hasMany `KnowledgeNote`, `KnowledgeList`.
`KnowledgeList` hasMany `KnowledgeListColumn` (by position) and `KnowledgeListItem` (by position).
`KnowledgeListItem` hasMany `KnowledgeListCell`. Moving a note or list rewrites only its folder.

## Journal

`UserActionEnum` gains, in the voice of the possession cases, naming the title or name:
`KnowledgeFolderCreation`, `Update`, `PositionUpdate`, `Deletion`, `DeletedWithDocuments`; `KnowledgeNoteCreation`, `Update`,
`Deletion`; `KnowledgeListCreation`, `Update`, `Deletion`; `KnowledgeListColumnCreation`, `Update`,
`PositionUpdate`, `Deletion`; `KnowledgeListItemCreation`, `Update`, `Deletion`. Ticking a task
journals a note update; a folder deleted with its files journals `DeletedWithDocuments`, counting them.
