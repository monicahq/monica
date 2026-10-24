# Contract: MCP history over the JSON API

Shaped like `GET api/v1/logs` (`Api\LogController`, `LogResource`). Read only: no POST, PUT or DELETE exists.
Both routes sit inside the `flag:mcp-server` group, so they answer not found when the server is off.

## `GET api/v1/mcp-calls` (`api.v1.mcpCalls`)

| Query | Values | Default |
| --- | --- | --- |
| `period` | `day`, `week`, `month`, `all` | `all` |
| `connection` | `api_key:{id}`, `oauth_client:{id}` | none |
| `result` | a `McpCallResultEnum` value | none |
| `tool` | a tool name | none |
| `per_page` | 1 to `monica.api_maximum_items_per_page` | `monica.api_default_items_per_page` |

An unknown value for `period`, `result` or `connection` answers 422. Newest `started_at` first.

```json
{
  "data": [{
    "type": "mcp_call",
    "id": "9d3c…",
    "attributes": {
      "started_at_in_milliseconds": 1792765927412,
      "duration_in_milliseconds": 182,
      "connection": { "type": "oauth_client", "id": "9a1f…", "name": "Claude" },
      "tool": "get-contact",
      "operation": "read",
      "vault": { "id": "…", "name": "Family" },
      "subject": { "type": "contact", "label": "Jim Halpert" },
      "result": "succeeded",
      "error": null,
      "json_rpc_id": "7",
      "ip_address": "203.0.113.7",
      "parameters": "{\"vault\":\"…\"}",
      "parameters_truncated": false,
      "response": "{…}",
      "response_truncated": false
    },
    "links": { "self": "…/api/v1/mcp-calls/9d3c…" }
  }],
  "meta": { "page": 1, "per_page": 15, "total": 1 }
}
```

`vault` and `subject` are null when the call named none inside the account. `error` is
`{ "code": -32602, "message": "…" }` when there is one. `vault.id` is null once the vault is deleted.

## `GET api/v1/mcp-calls/{uuid}` (`api.v1.mcpCalls.show`)

One entry, same shape. Another account's uuid answers 404.

## Not recorded

A call to these routes is an API call, not an MCP call, and leaves no entry. The MCP server gets no
tool for its own history: reading it through MCP would record the reading.
