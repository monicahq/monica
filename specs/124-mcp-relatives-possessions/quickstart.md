# Quickstart: validate relatives and possessions over MCP

## Automated

```bash
php artisan test --compact tests/Feature/Mcp/McpCatalogueTest.php tests/Unit/Mcp/Tools/CreateContactRelationshipTest.php
```

Expected: the catalogue test proves one `tools/list` without a cursor returns every registered tool
and no `nextCursor`; the tool test proves a person named in the call is recorded without a record.

## By hand, against a deployment of this branch

Prerequisite: an account with the MCP server switched on, connected to ChatGPT, holding a vault with
a contact named Maxime and at least one possession type.

1. Ask: "Add Sarah as Maxime's wife, without a record of her own. He also has two children, Milo, a
   boy, and another one whose name I do not know."
   - Maxime's page shows Sarah as partner and two children, one of them unnamed.
   - The vault's contact list does not show Sarah, Milo or the unnamed child.
2. Ask: "Add my bicycle to this vault."
   - The assistant lists possession types, picks one or asks which, and the bicycle appears in the
     vault's possessions.
3. Open the account's MCP history: both replays show `create-contact-relationship` (three calls) and
   `create-possession` succeeding (SC-004).

If step 1 still fails and the history shows no `create-contact-relationship` call, the client never
saw it: check what the client lists in its connector settings before changing anything else.
