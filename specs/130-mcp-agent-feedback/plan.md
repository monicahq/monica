# Implementation Plan: Agent feedback through MCP

**Branch**: `2026-11-18-mcp-agent-feedback` | **Date**: 2026-11-18 | **Spec**: [spec.md](spec.md)

## Summary

Agents call `submit-feedback`. The tool mirrors `POST api/v1/agent-feedback`, which stores a report through an action. Administrators triage reports on two new admin screens, list and detail, built from `Admin Agent Feedback.dc.html`. They accept, fix, dismiss, reopen and add notes, and each of those has its admin API endpoint and MCP tool, the way the support queue works. The MCP docs gain two families. Decisions are in [research.md](research.md), tables in [data-model.md](data-model.md), routes in [contracts/api-and-mcp.md](contracts/api-and-mcp.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel 13

**Primary Dependencies**: laravel/mcp, Blade, Alpine Ajax, Tailwind

**Storage**: two new tables (data-model.md)

**Testing**: PHPUnit (unit for actions, view models and enums; feature for controllers)

**Target Platform**: the existing web application, hosted and self hosted

**Project Type**: web application

**Performance Goals**: the list stays paged and indexed, and is searchable at 500 reports (SC-002)

**Constraints**: WCAG 2.2 AA; no vault content reaches administrators (FR-005)

**Scale/Scope**: 1 agent endpoint, 4 admin endpoints, 5 MCP tools, 4 web routes, 2 screens

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the logic | `SubmitAgentFeedback`, `ChangeAgentFeedbackStatus` and `AddAgentFeedbackNote` hold the logic; the controllers stay thin |
| II. Simplicity | no history table (R7), no scrubber (R4), and there is no flag |
| III. Tested | each action, view model and controller is tested as `.claude/rules/testing.md` assigns |
| V. Localized | every string goes through `__()`/`trans_choice()`; translations are written by Sonnet subagents |
| VIII. Nothing disappears silently | statuses are reversible (reopen); reports are deleted only with their account, as the user chose |
| IX. Accessible | `aria-pressed` tabs, labelled filters, an announced sidebar count; the accessibility-reviewer agent runs before the PR |
| X. Through the API | every web action has an endpoint and a tool (R2) |
| XI. Short record | every artifact is under its cap |
| Invariant: the administrator never reaches vault content | the tool description asks for paraphrase and placeholders (R4). This depends on the agent following it, and the screen says so |

Gate: pass. Re-checked after design: pass.

## Project Structure

```text
database/migrations/2026_11_18_000000_create_agent_feedback_reports_table.php
database/migrations/2026_11_18_000001_create_agent_feedback_notes_table.php
database/factories/AgentFeedbackReportFactory.php, AgentFeedbackNoteFactory.php
app/Models/AgentFeedbackReport.php, AgentFeedbackNote.php
app/Enums/AgentFeedbackKindEnum.php, AgentFeedbackSeverityEnum.php, AgentFeedbackStatusEnum.php
app/Enums/AdministratorActionEnum.php                         (+2 cases)
app/Actions/SubmitAgentFeedback.php, ChangeAgentFeedbackStatus.php, AddAgentFeedbackNote.php
app/Http/Controllers/Api/AgentFeedbackController.php          (create)
app/Http/Controllers/Api/Admin/AgentFeedbackController.php    (index, show)
app/Http/Controllers/Api/Admin/AgentFeedbackNoteController.php, AgentFeedbackStatusController.php
app/Http/Controllers/App/Admin/AgentFeedbackController.php    (index, show)
app/Http/Controllers/App/Admin/AgentFeedbackNoteController.php, AgentFeedbackStatusController.php
app/ViewModels/Admin/AgentFeedbackQueueViewModel.php, AgentFeedbackReportViewModel.php
app/ViewModels/Admin/AdminNavigationViewModel.php             (+newAgentFeedbackCount)
app/Mcp/Tools/SubmitFeedback.php, ListAgentFeedback.php, GetAgentFeedback.php,
              AddAgentFeedbackNote.php, ChangeAgentFeedbackStatus.php
app/Mcp/Tools/MirrorsAnApiCapability.php                       (+agentFeedbackReport lookup)
app/Mcp/Servers/MonicaServer.php                               (register, one line of instructions)
app/ViewModels/Docs/McpDocsViewModel.php                       (families "Feedback", "Agent feedback queue")
resources/views/app/admin/agent-feedback/index.blade.php, show.blade.php, _row.blade.php
resources/views/layouts/admin.blade.php                        (sidebar entry with count)
routes/api.php, routes/administrator.php
resources/docs/api + api-changelog, changelog/ entry, lang/*.json
tests/Unit/Actions/*, tests/Unit/ViewModels/Admin/*, tests/Unit/Enums/AgentFeedbackStatusEnumTest.php
tests/Feature/Controllers/Api/AgentFeedbackControllerTest.php, Api/Admin/*, App/Admin/*
```

## Notes for implementation

- The admin screens reuse `x-page-header`, `x-list-panel`, `x-detail-list`, `x-badge`, `x-copy-button`, `x-code-value` and `x-caption`. List the components folder before writing markup.
- The status tabs and the filters submit a GET form. Alpine Ajax swaps the list, as the support queue does. The state lives in the URL.
- The detail screen offers only the transitions `allowedTargets()` returns. Each is a small form with a PUT.
- Report text is printed with `{{ }}` and never rendered as Markdown (spec edge case).
- `SubmitAgentFeedback` takes the connection name from `request()->attributes->get('mcp_connection_name')`, falling back to the API key name, then to an empty string.
- Account deletion relies on the `cascadeOnDelete` of the foreign key. A test proves the reports go.

## Complexity Tracking

None.
