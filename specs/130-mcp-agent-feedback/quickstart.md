# Quickstart: Agent feedback

## Automated

```bash
php artisan test --compact --filter=AgentFeedback
php artisan test --compact tests/Unit/Mcp tests/Feature/RouteProtectionTest.php
php artisan monica:localize --check
```

## By hand

1. Connect an assistant to `/mcp` with an account. Ask it to call `submit-feedback` with a complete report. The answer carries an id.
2. Call it again with `observed: "test"`. It is refused, and the message asks for a real description.
3. Sign in as an administrator. The sidebar shows Agent feedback with a count of 1.
4. Open the report. Check the request, steps, observed, expected, fix, code, connection and the "Same tool" panel.
5. Accept it, mark it fixed, then reopen it. Each step appears in Record (the administrator log).
6. Add a note, then press Copy as Markdown and paste the result.
7. Filter by kind and severity, and search by the person's email.
8. Delete the account. The report is gone.
9. Open `/docs/mcp`. "Feedback" lists `submit-feedback` and its purpose.
