# Quickstart: validating the MCP documentation page

## Automated

```bash
php artisan test --compact tests/Feature/Controllers/App/Docs tests/Unit/ViewModels/Docs
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

Expected: all pass. `McpDocsViewModelTest` fails if a tool is added to `EricaServer` without the
table following ([research R5](research.md#r5)).

## By hand

| Step | Expected |
| --- | --- |
| Open `/docs`, follow the MCP card | `/docs/mcp`, server address `{APP_URL}/api/mcp` |
| Copy the address and each configuration | Clipboard matches [the contract](contracts/page-content.md) |
| Type `https://example.test/` in "Your Monica address" | Every address reads `https://example.test/api/mcp`; clearing it restores the instance's |
| Tab to the client tabs, use arrow keys, Home, End | Focus and selection move; the panel follows; a screen reader announces tab and panel |
| Scroll the page at 1280 px, then at 390 px | "On this page" marks the section in view; at 390 px it is hidden, no horizontal scroll |
| Open `/docs/mcp.md` and `/llms.txt` | Markdown with the same sections; `llms.txt` lists it |
| Follow "Open in Claude" and "Open in ChatGPT" | A new conversation with the prompt naming `/docs/mcp.md` |

## Against a real client

Create an "AI Assistant" key, export `MONICA_API_KEY`, and connect Claude Code with the `.mcp.json`
from the page; ask "List my Monica vaults." Then run the Codex command from the page and confirm it
connects without starting an OAuth flow, since `auth` defaults to `oauth` ([research R2](research.md#r2)).
If it does not, the Codex tab gains the setting that turns it off before the change ships.
