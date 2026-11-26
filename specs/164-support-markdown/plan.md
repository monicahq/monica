# Implementation Plan: Markdown in support requests

**Branch**: `2026-11-26-support-markdown` | **Date**: 2026-11-26 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/164-support-markdown/spec.md`

## Summary

A support message is stored exactly as written (R1) and drawn with Laravel's `Str::markdown()`, called
in one helper with the settings that make it safe: raw HTML escaped, unsafe links refused, nesting
bounded, images turned into links, headings moved below the screen's own (R2 to R4). One component
draws a message on both threads (R5). Emails, the API and the MCP server keep their code; their
documentation says the body is Markdown (R6). One line under each box points at the syntax (R8).
Decisions are in [research.md](research.md); what the output may hold is in
[contracts/rendering.md](contracts/rendering.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major)

**Primary Dependencies**: `Str::markdown()` over league/commonmark 2.10, already installed and used by
Knowledge; Blade, Tailwind CSS; no new dependency

**Storage**: no migration. `support_messages.body` now holds the text as written

**Testing**: PHPUnit (`tests/Unit/Helpers`, `tests/Unit/Actions`, `tests/Unit/ViewModels`,
`tests/Unit/Notifications`, `tests/Feature/Views`)

**Target Platform**: web application, emails, the API v1 and the MCP server

**Project Type**: web application (Laravel monolith)

**Performance Goals**: a 20,000 character message built to be slow renders in under one second (SC-003)

**Constraints**: WCAG 2.2 AA; nothing fetched from a third party when a message is drawn

**Scale/Scope**: one helper, one small extension, two components, four actions, one view model, three
views, four MCP descriptions, two API doc files, one portal section

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions hold the business logic | Pass. The four actions keep writing; they stop stripping the body |
| II. Simplicity | Pass. Laravel's own Markdown call, one helper holding its settings, emails and API untouched |
| III. Tested before merged | Pass. Hostile inputs on the helper, one check per surface |
| IV. Laravel conventions | Pass. `Str::markdown()`, an anonymous component |
| V. Localized and safe | Pass. The hint line and the portal section translated; user content never translated |
| VI. Nothing changes under anybody's feet | Pass. No stored message rewritten; no API field changes; the changelog says what `body` may now hold |
| VII. Stable core | Pass. No structure touched |
| VIII. Nothing disappears silently | Pass. Text that used to be stripped at saving is now kept (R1) |
| IX. Accessible | Pass. Headings below the screen's (R3), code blocks wrap instead of scrolling, links named by their words |
| X. Everything through the API | Pass. Markdown is written and read through the API as on screen |
| XI. Short record | Pass. No data model: no schema change |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

```text
specs/164-support-markdown/
├── plan.md
├── research.md
├── quickstart.md
├── contracts/
│   ├── rendering.md
│   └── api.md
└── tasks.md             # /speckit-tasks
```

### Source Code (repository root)

```text
app/Helpers/SupportMarkdown.php                                   # new, toHtml() and plainText() (R2, R7)
app/Helpers/SupportHeadingLevelExtension.php                      # new (R3)
app/Actions/OpenSupportRequest.php, AddSupportMessage.php,
  SendSupportReply.php, AddSupportNote.php                        # body kept as written (R1)
app/ViewModels/Admin/SupportQueueViewModel.php                    # lastLine through plainText() (R7)
app/Mcp/Tools/CreateSupportRequest.php, AddSupportMessage.php,
  SendSupportReply.php, AddSupportNote.php                        # body described as Markdown (R6)
resources/views/components/support/message-body.blade.php         # new (R5)
resources/views/components/support/markdown-hint.blade.php        # new (R8)
resources/views/app/support/_message.blade.php                    # x-support.message-body
resources/views/app/admin/support/_message.blade.php              # x-support.message-body
resources/views/app/support/new.blade.php, show.blade.php         # hint under the box
resources/views/app/admin/support/_composer.blade.php             # hint under both boxes
resources/docs/api/32-support-requests.php, 33-support-administration.php
resources/docs/api-changelog/<date>.php
docs/portal/en/10-your-account-and-security/19-ask-for-help.md    # "Format your message"
changelog/<date>-<ulid>-support-markdown.md
lang/*.json                                                       # translated by Sonnet subagents
```

Tests:

- `tests/Unit/Helpers/SupportMarkdownTest.php`, new: the hostile inputs, the shape rules and the time
  bound (R9).
- The four action tests: a body holding `<b>` and `a < b` is stored as written.
- `SupportQueueViewModelTest`: the last line without markup.
- One notification test: a body holding `<script>` and an image is escaped in the HTML part and
  loads nothing.
- `tests/Feature/Views/Components/Support/MessageBodyTest.php`, new: a payload is escaped and a list
  is drawn.

## Complexity Tracking

| Deviation | Why needed | Simpler alternative rejected because |
| --- | --- | --- |
| `x-support.message-body` extracted at two files, below the three file bar | It holds the only `{!! !!}` printing a message and receives only the source, so a later partial cannot skip the escaping (R5) | Two partials each printing `{!! !!}`: the copy a later change gets wrong |
