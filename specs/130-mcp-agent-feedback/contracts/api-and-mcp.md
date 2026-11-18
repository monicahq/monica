# Contracts: API and MCP

The field limits are in [data-model.md](../data-model.md).

## Agent side

| Route | Name | MCP tool | Who |
| --- | --- | --- | --- |
| `POST api/v1/agent-feedback` | `api.v1.agentFeedback.create` | `submit-feedback` | any signed in user, `mcp-server` on |

Body: `title`, `kind`, `severity`, `observed`, `expected` and `proposed_fix` are required. `tool`, `request`, `steps[]` (`tool`, `arguments`, `outcome`, `result`), `proposed_code` and `model` are optional. The answer is a 201 with `{ id, status }`.

Tool description, in substance: report friction with this Monica MCP server to the administrators of this instance (a tool that failed, a tool that was missing, a description that misled you, or an idea). Call it at most once per task, and only when something got in the way. Never call it to log progress or to test it. Paraphrase the person's request and use placeholders instead of names, dates and other details from the vault. Never include secrets.

## Admin side (`administrator`, `administration`)

| Route | Name suffix | MCP tool |
| --- | --- | --- |
| `GET\|QUERY api/v1/admin/agent-feedback` | `admin.agentFeedback` | `list-agent-feedback` |
| `GET\|QUERY api/v1/admin/agent-feedback/{agentFeedbackReport}` | `admin.agentFeedback.show` | `get-agent-feedback` |
| `POST api/v1/admin/agent-feedback/{agentFeedbackReport}/notes` | `admin.agentFeedback.notes.create` | `add-agent-feedback-note` |
| `PUT api/v1/admin/agent-feedback/{agentFeedbackReport}/status` | `admin.agentFeedback.status.update` | `change-agent-feedback-status` |

The list filters on `status`, `kind`, `severity` and `q` and is paged as `{data, meta}`. A transition `allowedTargets()` does not permit returns a 422.

## Web (admin space)

| Route | Name |
| --- | --- |
| `GET admin/agent-feedback` | `admin.agentFeedback.index` (query: `status`, `kind`, `severity`, `q`) |
| `GET admin/agent-feedback/{agentFeedbackReport}` | `admin.agentFeedback.show` |
| `POST admin/agent-feedback/{agentFeedbackReport}/notes` | `admin.agentFeedback.notes.create` |
| `PUT admin/agent-feedback/{agentFeedbackReport}/status` | `admin.agentFeedback.status.update` |
