# 070. KnowledgeNote

**Status:** in progress (`app/Models/KnowledgeNote.php`)
**Spec:** specs/104-knowledge

## What it is

Free text somebody wants to remember, in Markdown, inside one [KnowledgeFolder](069-knowledge-folder-in-progress.md). Nothing else attaches to it yet: no link to a contact, a possession or another note.

## Fields

```
id                          int                             primary key
uuid                        uuid                            route key, and identifier for the API
account_id                  int                             account of the vault
vault_id                    int                             vault it belongs to
knowledge_folder_id         int                             folder it sits in, of the same vault
title                       string (255)                    required, "Untitled note" when created without one
body                        longText, nullable              Markdown, at most 200,000 characters
```

## Rules

- Markdown is displayed, never executed: raw HTML is escaped and unsafe links are refused.
- A task ticked while reading rewrites only its own line of the text, never one inside a code block.
- Moving a note changes only its folder.
- Reached through the API (`/api/v1/vaults/{vault}/knowledge/notes`) and the MCP server. Every change is written to the changelog.
