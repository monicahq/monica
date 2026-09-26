# Contract: what the page serves

`{url}` is the server address: the reader's Monica address, by default `config('app.url')` without a
trailing slash, followed by `/api/mcp`.

## Routes

| Route | Name | Answers |
| --- | --- | --- |
| `GET docs/mcp` | `docs.mcp.index` | 200, HTML, public |
| `GET docs/mcp.md` | `docs.mcp.markdown.index` | 200, `text/markdown; charset=UTF-8`, public |

## Client tabs

| Tab | File label | Content |
| --- | --- | --- |
| Claude Code | `.mcp.json` | `{"mcpServers":{"monica":{"type":"http","url":"{url}","headers":{"Authorization":"Bearer ${MONICA_API_KEY}"}}}}` |
| Cursor | `~/.cursor/mcp.json` | `{"mcpServers":{"monica":{"url":"{url}","headers":{"Authorization":"Bearer ${env:MONICA_API_KEY}"}}}}` |
| Codex | Terminal | `codex mcp add monica --url {url} --bearer-token-env-var MONICA_API_KEY`, split on three lines with `\` |
| Codex | `~/.codex/config.toml` | `[mcp_servers.monica]`, `url = "{url}"`, `bearer_token_env_var = "MONICA_API_KEY"` |
| VS Code | User configuration | `inputs`: `{"type":"promptString","id":"monica-api-key","description":"Monica API key","password":true}`; `servers.monica`: `{"type":"http","url":"{url}","headers":{"Authorization":"Bearer ${input:monica-api-key}"}}` |
| Claude Desktop | none | Warning notice, wording from the spec's corrections |
| ChatGPT | none | Quiet notice "Not available yet", the design's wording |
| Other | Connection details | `Server URL: {url}` and `Header: Authorization: Bearer YOUR_API_KEY` |

JSON is printed with two spaces of indentation, keys in the order above.

## Tools table

| Group | Changes data | Tools, in order |
| --- | --- | --- |
| Vaults | no | `list-vaults` |
| Contacts, reading | no | `list-contacts`, `get-contact`, `get-me-contact` |
| Contacts, changing | yes | `create-contact`, `update-contact-name`, `set-contact-favourite` |
| Templates | no | `list-templates`, `list-template-versions` |
| Entity types | no | `list-entity-types` |
| Relationship vocabulary | no | `list-relationship-types`, `list-relationship-categories` |
| Life event categories | no | `list-life-event-categories` |

Descriptions: the design's, as corrected in the spec.

## Links

| Link | Target |
| --- | --- |
| Breadcrumb Home, Docs | `home.index`, `docs.index` |
| See also: API reference, API keys, Logs | `docs.api.index`, `settings.security.index`, `settings.logs.index` |
| Open in Claude, Open in ChatGPT | [research R3](../research.md#r3) |
| Portal MCP card; `/llms.txt` line "MCP" | `docs.mcp.index`; `docs.mcp.markdown.index` |
