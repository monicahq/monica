# Quickstart: validating support requests

The checkout is served by Herd at `monica.test`. Never migrate the local database for this: the tests
build their own.

## Automated

```bash
PAO_DISABLE=true php artisan test --parallel --compact
```

```bash
vendor/bin/phpstan analyse --no-progress
```

```bash
php artisan monica:localize --check
```

The suite must prove, each in its own layer (`.claude/rules/testing.md`):

| Scenario | Spec | Layer |
| --- | --- | --- |
| Opening sets `to_handle`, writes the history row, emails the copy, refuses a 6th file and one over 10 MB | US1, FR-001, FR-008 | `OpenSupportRequestTest` |
| A user message on a solved request reopens it with the same number | US3 | `AddSupportMessageTest` |
| A reply moves to the chosen status and emails the user; a note moves nothing and emails nobody | US2, FR-005, FR-009 | `SendSupportReplyTest`, `AddSupportNoteTest` |
| A non administrator reaches no admin action, refused as not found | FR-015 | each admin action test |
| No note reaches the user: user view model, user API resource, user MCP tool, notification | FR-006, SC-003 | one test per surface |
| Queue counts, filters, oldest first, empty copy | US2, FR-010 | `SupportQueueViewModelTest` |
| An admin key without the `administration` scope gets 404 | R9 | `Api\Admin\SupportRequestControllerTest` |
| A request of another account answers 404 through the API and MCP | FR-012 | API and MCP tests |
| No support area without an active administrator | edge case | `SupportRequestFormViewModelTest` |

## By hand at `monica.test`

1. As a user, open the account menu, choose Help and support, pick "I have a billing question",
   check the guidance changes, send with a PDF. The confirmation shows `MON-{n}`.
2. As an administrator, open the admin space: the Support tab shows a count of 1. Open the request,
   add an internal note, then reply with "Then move it to: Waiting on the user".
3. As the user, open Your requests: the request reads "Reply from support", the note is absent.
   Reply; the administrator sees it back in To handle.
4. Repeat 1 to 3 with the keyboard only and with VoiceOver, and at 200% zoom.
