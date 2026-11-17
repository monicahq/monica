# Quickstart: validating 128

## Automated

```bash
php artisan test --compact tests/Feature/Controllers/Api tests/Unit/Mcp tests/Feature/Mcp tests/Feature/Views
php artisan test --compact
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
php artisan monica:localize --check
```

## The update rule, by hand

With an API key, on any record of [research.md R1](research.md):

1. `PUT` the record with one field changed. Every other field keeps its value.
2. `PUT` it with that field set to `null`. The field is cleared, unless it is a name, which is refused.
3. Repeat through the mirroring MCP tool: the answer and the stored record match step 1 and 2.

## The reported bug (monicahq/monica#7990)

1. Give a contact a birthday 30 days away; no other reminder in the vault.
2. Ask a connected assistant "list all reminders of my vault".
3. It calls `list-vault-reminders` and names the birthday, or calls the dated tool and says the window
   is 7 days.

## The documentation page

1. Signed out, open `/docs/mcp` and `/docs/mcp/tools/reminders`; compare with `MCP Docs.dc.html` at
   1280px and 390px wide.
2. Search "reminder": only reminder tools are offered; Tab reaches every control, Escape leaves search.
3. Every tool the server registers appears once; `/docs/mcp.md` lists the same tools and descriptions.
