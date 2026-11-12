# 069. KnowledgeFolder

**Status:** in progress (`app/Models/KnowledgeFolder.php`)
**Spec:** specs/104-knowledge

## What it is

A folder of a [Vault](007-vault-in-progress.md)'s Knowledge, grouping its notes and lists by theme: Home, Travel, Health. Folders are flat, never nested, and keep the order the person gives them.

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account of the vault
vault_id                    int                             vault it belongs to
name                        string (255)                    required
position                    int                             1 upward, no gap within the vault
```

## Rules

- A vault that holds Knowledge keeps at least one folder: the last one cannot be deleted.
- Starting a note or a list in a vault without a folder creates "General" to hold it.
- Deleting a folder moves its notes and lists to another folder of the vault, or deletes them, as the person chooses.
- Reached from the vault's Knowledge screen, through the API (`/api/v1/vaults/{vault}/knowledge/folders`) and through the MCP server. Every change is written to the changelog.
- Deleting the vault deletes its folders.
