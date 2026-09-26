# Quickstart: MCP tools for reading the account logs

## Automated

```bash
php artisan test --compact tests/Unit/Mcp/Tools/ListLogsTest.php tests/Unit/Mcp/Tools/GetLogTest.php \
  tests/Unit/Mcp/Tools/ListEmailsSentTest.php tests/Unit/Mcp/Tools/GetEmailSentTest.php
php artisan test --compact tests/Unit/Actions/CreateEmailSentTest.php
php artisan test --compact tests/Feature/Controllers/Api/EmailSentControllerTest.php tests/Feature/Controllers/Api/LogControllerTest.php
php artisan test --compact tests/Feature/Mcp tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php tests/Feature/Controllers/App/Docs
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

What each proves:

| Scenario | Where |
| --- | --- |
| Activity listed newest first, with the envelope; a page and a page size honoured; an absurd page size clamped; nothing written to the activity by listing | `ListLogsTest` |
| One entry read; another account's entry and a non-numeric identifier answer `That log entry does not exist.` | `GetLogTest` |
| Emails listed newest first without their body; paging as above | `ListEmailsSentTest` |
| One email read with its body; another account's email not found | `GetEmailSentTest` |
| A body holding a link and the address printed under a button is stored with neither, the rest of the copy kept | `CreateEmailSentTest` |
| A real sign-in link email is stored without its address | `CreateEmailSentTest` |
| The API still leaves the body off list rows and carries it on one email | `EmailSentControllerTest` (unchanged) |
| Every tool names a route that exists and reads-only tools are marked | `McpCatalogueTest` (unchanged) |
| The docs list exactly the registered tools, count 55, the new group reads only | `McpDocsViewModelTest` |
| The page and its Markdown show "Read the logs" | `McpDocsControllerTest`, `McpDocsMarkdownControllerTest` |

## By hand

With an API key for the AI Assistant and an MCP client pointed at `/api/mcp`:

1. Ask "what changed in my account recently?". The assistant calls `list-logs` and answers from the
   first page. Compare with **Settings → Logs**.
2. Rename a contact through the assistant, then ask again. The newest entry names the source `mcp`.
3. Ask for a sign-in link from the sign-in screen, then ask the assistant "show me the last email you
   can see". It calls `list-emails-sent` then `get-email-sent`. The body reads as the email did, and
   holds no `http` address.
4. Open `/docs/mcp` and `/docs/mcp.md`: the Account logs group lists four tools, and "Read the logs"
   explains them.
