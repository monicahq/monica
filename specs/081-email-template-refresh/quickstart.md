# Quickstart: checking the email layout

## Automated

```bash
php artisan test --compact tests/Feature/Views/Layouts/MailLayoutTest.php tests/Unit/Notifications
```

The layout test covers FR-003, FR-004, FR-010, FR-016 and FR-017 (research R7). The notification
tests prove every email still renders its own copy (FR-001, FR-007).

## By eye, in a browser

Write the rendered confirmation message to a file from a throwaway test (`(string) $message->render()`
into `storage/app/verify.html`), open it, and compare with design 1a. Switch the operating system to
dark and reload; compare with 1b. Do the same with `NewLoginDetected` for the panel (User Story 3).
Narrow the window to 375 pixels for SC-006.

## In real clients

1. Start Mailpit (`docker compose up -d mailpit`) and set `MAIL_MAILER=smtp`, `MAIL_PORT=1025`.
2. Trigger the confirmation email by registering an account, and a sign in alert by signing in with
   an email link.
3. Send both to a real mailbox and open them in the SC-003 client list, in light and dark, with
   images on and blocked (SC-005).
4. Check every image request goes to `APP_URL` (SC-007).

## Expected

| Check | Expected |
| --- | --- |
| Apple Mail, light / dark | 1a / 1b |
| Gmail app, dark | its own inversion, all text and the mark readable |
| Outlook Windows | square card, same order and copy |
| Images blocked | product name in the header bar, button works |
| Plain text part | unchanged |
