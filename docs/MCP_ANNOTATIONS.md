# MCP tool annotations

Every tool in `app/Mcp/Tools/` declares `IsReadOnly`, `IsDestructive` and
`IsOpenWorld`, including false values. Laravel MCP serializes these attributes
as the boolean `readOnlyHint`, `destructiveHint` and `openWorldHint` fields in
`tools/list`. Keep the declarations on each tool; do not infer them at runtime
from its name or its API route's HTTP verb.

## Classification

- Queries that only retrieve account data are read-only, non-destructive and
  closed-world. Searching private account data and exporting a possession-type
  document do not access the public web.
- Additive operations, including ordinary record creation, imports, copies and
  restoration, are writes but non-destructive. Updating counters that describe
  the added/restored record does not make the operation destructive.
- Operations that overwrite, clear, remove or replace existing data are
  destructive, including partial updates, setters, moving a possession, template
  publication and final reminder answers. A reversible or soft deletion still
  removes data from the user's active environment.
- Creating or changing a contact/group reminder can schedule delivery over
  external notification channels. These tools are conservatively destructive
  and open-world because a delivered notification cannot be recalled, even
  though the reminder itself can subsequently be changed or deleted.
- Opening a support request, adding a support message, sending an administrator
  reply and submitting agent feedback communicate with recipients outside the
  user's private account. These are destructive, open-world writes. Internal
  administrator notes remain additive, closed-world writes; changing a support
  status without sending a message remains a destructive, closed-world write.
- A possession transaction records information. It does not execute a payment
  or contact a bank, so creation is additive and closed-world.

Two retrieval tools also change account state:

- `get-contact` enqueues `RecordContactVisit`, overwriting `last_consulted_at`.
  It is a destructive, closed-world write for annotation purposes.
- `get-contact-markdown` calls `ExportContactMarkdown`, which appends a
  user-visible export entry to the account log. It is an additive, closed-world
  write for annotation purposes.

These effects are separate from the server-wide MCP call audit trail. Routine
request logging does not change the classification of every query. Annotations
describe the existing behavior; they do not change authorization or execute
new actions, and clients must not treat them as access-control enforcement.

## Verification

`tests/Feature/Mcp/McpCatalogueTest.php` checks the serialized HTTP catalog for
all three boolean hints on every exposed tool and covers representative
operations, including the exceptions above. New tools must declare all three
attributes and be reviewed for indirect side effects, not just their primary
database operation.

```sh
DB_TEST_DRIVER=sqlite DB_TEST_DATABASE=:memory: php artisan test --compact tests/Feature/Mcp
vendor/bin/pint --test app/Mcp/Tools tests/Feature/Mcp/McpCatalogueTest.php
```

After deployment, reconnect or rescan the server in the OpenAI submission
portal so the live catalog uses the updated annotations.

References:

- <https://developers.openai.com/plugins/reference#annotations>
- <https://developers.openai.com/plugins/deploy/app-review>
- <https://modelcontextprotocol.io/specification/2025-11-25/schema#toolannotations>
