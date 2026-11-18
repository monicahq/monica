# Data model: Agent feedback

## `agent_feedback_reports` (model `AgentFeedbackReport`)

| Column | Type | Notes |
| --- | --- | --- |
| id | id | shown as `#id` |
| uuid | uuid, unique | URLs, API and MCP |
| account_id | foreignIdFor(Account), cascadeOnDelete | the report goes with the account (spec, Q2) |
| user_id | foreignIdFor(User), nullable, nullOnDelete | |
| status | string | `AgentFeedbackStatusEnum`, default `new` |
| kind | string | `AgentFeedbackKindEnum` |
| severity | string | `AgentFeedbackSeverityEnum` |
| title | string(200) | |
| tool | string(100), nullable | the tool concerned, stored as written |
| request | text, nullable | the person's request, paraphrased |
| steps | json, nullable | list of `{tool, arguments, outcome, result}`, max 20 |
| observed | text | max 4000, placeholders refused |
| expected | text | max 4000 |
| proposed_fix | text | max 4000 |
| proposed_code | text, nullable | max 8000 |
| model | string(100), nullable | reported by the agent |
| connection_name | string | from the MCP request, or the API key name |
| timestamps | | |

Indexes: `(status, created_at)` and `tool`.

## `agent_feedback_notes` (model `AgentFeedbackNote`)

| Column | Type | Notes |
| --- | --- | --- |
| id, uuid | | |
| agent_feedback_report_id | foreignIdFor, cascadeOnDelete | |
| author_user_id | foreignIdFor(User), nullable, nullOnDelete | |
| author_name | string | kept if the administrator goes |
| body | text | max 4000 |
| timestamps | | |

## Enums

- `AgentFeedbackKindEnum`: `bug`, `missing_tool`, `unclear_description`, `improvement`.
- `AgentFeedbackSeverityEnum`: `blocks_task`, `needs_workaround`, `minor`.
- `AgentFeedbackStatusEnum`: `new`, `accepted`, `fixed`, `dismissed`, with `allowedTargets()`:

| From | To |
| --- | --- |
| new | accepted, dismissed |
| accepted | fixed, dismissed |
| fixed, dismissed | new (reopen) |

- `AdministratorActionEnum` gains `AgentFeedbackStatusChanged` and `AgentFeedbackNoteAdded`.
- A step's `outcome` is `success` or `error`.
