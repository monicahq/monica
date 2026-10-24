# Quickstart: proving the MCP history

Every scenario is a PHPUnit test. Recording is proven over `POST /mcp` with a real key, the way
`tests/Feature/Mcp/McpTransportTest` does, because only that path carries a connection;
`EricaServer::tool()` records nothing. No scenario needs a migration on the local database.

| # | Scenario | Spec | Expected |
| --- | --- | --- | --- |
| 1 | Jim calls `get-contact` on his own contact | US1.1 | One row: `read`, `succeeded`, vault and contact set, duration set |
| 2 | `update-contact-name` on that contact | US1.2 | `update`, `succeeded` |
| 3 | `get-contact` with a contact of Dwight's account | US1.3 | `refused`, `vault_id` and subject null |
| 4 | `update-life-event` with `happened_on: "31/02/1978"` | US1.4 | `invalid_parameters`, code -32602, reason stored |
| 5 | A tool whose action throws | US1.5 | HTTP 200 with `isError: true`; row says `error` |
| 6 | A fixture tool reads the history while it runs | US1.6 | It sees its own row, `interrupted`, duration null |
| 7 | Request with no token | US1.7 | 401 and no row anywhere |
| 8 | `create-life-event` with a sensitive field and a `password` key | US4.1 | Stored JSON holds `[masked]` for both |
| 9 | A 40 KB `narrative` | US4.2 | `parameters_truncated` true, stored text 16 384 bytes |
| 10 | Rows 91 and 89 days old, then `model:prune` | US4.3 | Only the 89 day row remains |
| 11 | Revoke the API key, open the screen | US2.6 | Its rows show the old name |
| 12 | Michael's and Jim's rows, Jim opens the screen and the API | US2.4 | Only Jim's |
| 13 | Each filter, then one matching nothing | US3 | Filtered list, then the "nothing matches" state |

Run the narrowest set while working:

```bash
php artisan test --compact tests/Feature/Mcp
```

```bash
php artisan test --compact --filter=McpCall
```

Then the gates `CLAUDE.md` lists: `php artisan monica:localize --check`,
`vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`.

By hand, once: open Settings, Security and access, follow the link to the history, unfold a row with
the keyboard only, and check it with VoiceOver and at 200% zoom.
