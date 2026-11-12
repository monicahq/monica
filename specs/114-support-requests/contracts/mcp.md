# MCP tools

Each tool uses `MirrorsAnApiCapability` and names the API route it mirrors. A request outside the
account, or any admin tool called without the rights of R8 and R9, answers "That support request
does not exist." MCP sends no files: attachments are listed, not uploaded.

| Tool | Mirrors | Registered for |
| --- | --- | --- |
| `ListSupportRequests` | `GET support-requests` | every user |
| `GetSupportRequest` | `GET support-requests/{request}` | every user |
| `CreateSupportRequest` | `POST support-requests` (without files) | every user |
| `AddSupportMessage` | `POST support-requests/{request}/messages` (without files) | every user |
| `SolveSupportRequest` | `POST support-requests/{request}/solution` | every user |
| `ListSupportQueue` | `GET admin/support-requests` | administrator with the `administration` scope |
| `GetSupportQueueRequest` | `GET admin/support-requests/{request}` | same |
| `SendSupportReply` | `POST admin/support-requests/{request}/replies` | same |
| `AddSupportNote` | `POST admin/support-requests/{request}/notes` | same |
| `ChangeSupportRequestStatus` | `PUT admin/support-requests/{request}/status` | same |

`shouldRegister()` of the admin tools returns false unless both conditions hold, so the tools are
not even listed to anybody else. Each tool is added to `MonicaServer::$tools`.
