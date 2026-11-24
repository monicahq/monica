# Implementation Plan: Email the administrators about support requests

**Branch**: `2026-11-24-support-administrator-notifications` | **Date**: 2026-11-24 | **Spec**: [spec.md](spec.md)

## Summary

The two actions a user writes to support through, `OpenSupportRequest` and `AddSupportMessage`, each
send one queued email to every active administrator except the author, and nothing while support is
switched off. Decisions and their reasons are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel (current major) | **Testing**: PHPUnit
**Storage**: no schema change. Two new `EmailType` values land in the existing `emails_sent` rows.
**Constraints**: the user's request is answered before any email goes out (queue `high`).
**Scale**: one email per administrator per user message. Instances have a handful of administrators.

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the logic | Pass. Sending lives in the two existing actions (R1). |
| II. Simplicity | Pass. No event, listener or helper. Recipients reuse `SupportAvailability` (R2). |
| III. Tested | Pass. Two notification tests, two action tests extended. |
| V. Localized | Pass. Every string through `__()`, category label rendered per recipient (R5), translated with `monica:localize`. The user's words are left as written. |
| IX. Accessible | Pass. No screen changes; the emails use the shared mail template. |
| X. Through the API | Pass. API and MCP call the same actions; no new operation to expose. |
| XI. Short record | **Exceeded**: `spec.md` is 210 lines against a cap of 120. See Complexity Tracking. |

Re-checked after design: unchanged.

## The two emails

Both: queued on `high`, implement `HasEmailType`, button "Open the request" to
`route('admin.support.show', $supportRequest)`, and the outro line saying a reply to the email does not
reach the user and that the answer is written from the request in the admin space (FR-009).

| | `SupportRequestOpened` | `SupportMessageAdded` |
| --- | --- | --- |
| `EmailType` | `SupportRequestOpened` (`support_request_opened`) | `SupportMessageAdded` (`support_message_added`) |
| Subject | "New support request :reference" | "New message in support request :reference" |
| First line | a new request was opened | the user wrote again in an existing request |
| Lines | subject, category, author and account number, body | subject, author and account number, body |
| Conditional lines | files are attached | files are attached; the message reopened a solved request |

The author line reads "From :name, account :id" (R6).

## Source Code

```text
app/Actions/OpenSupportRequest.php       notify(): also send SupportRequestOpened (R1, R2, R3)
app/Actions/AddSupportMessage.php        keep the previous status; new notify() step
app/Enums/EmailType.php                  two cases
app/Notifications/SupportRequestOpened.php   new
app/Notifications/SupportMessageAdded.php    new
lang/*.json                              new strings, every locale (Sonnet subagents)
tests/Unit/Notifications/SupportRequestOpenedTest.php   new
tests/Unit/Notifications/SupportMessageAddedTest.php    new
tests/Unit/Actions/OpenSupportRequestTest.php           extended
tests/Unit/Actions/AddSupportMessageTest.php            extended
```

In each action the step reads:

```php
if (! SupportAvailability::isEnabled()) {
    return;
}

$administrators = SupportAvailability::administrators()->reject(fn (User $administrator): bool => $administrator->id === $this->user->id);

Notification::send($administrators, new SupportRequestOpened(...));
```

`OpenSupportRequest` keeps sending the user's own copy first and unconditionally, as today (FR-011).

### Tests

| Test | Proves |
| --- | --- |
| Notification tests | subject, lines, `actionUrl`, `outroLines`, `emailType()`, the conditional lines on and off, the category label in another locale |
| `OpenSupportRequestTest` | each active administrator is sent one, the author and a deactivated administrator are not, nothing with support off, the user's copy still sent |
| `AddSupportMessageTest` | the same three, and the reopened flag set only from a solved request |

No controller, API or MCP test: the surfaces are unchanged and reach the same actions (testing.md, one
layer per rule).

### Also in the change

- A changelog entry for administrators (`changelog` skill).
- No API reference, MCP tool, help bubble or product documentation changes: nothing a user or an API
  client does or reads changes.

## Complexity Tracking

| Violation | Why | Simpler alternative rejected because |
| --- | --- | --- |
| `spec.md` over its 120 line cap | Written long in the first session and extended in the second | Trimming it is a separate edit; the pull request states the overrun, as XI allows |
