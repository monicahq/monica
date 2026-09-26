# Quickstart: Validating the Telegram Bot Setup

## Automated

```bash
php artisan test --compact --filter=Telegram
```

```bash
php artisan test --compact tests/Feature/RouteProtectionTest.php tests/Feature/Controllers/App/Admin
```

Every Telegram call is faked with `Http::fake()`; `Http::preventStrayRequests()` proves nothing reaches
the real service.

## Manual, end to end (needs a real bot and a public HTTPS address)

Prerequisites: the integration package installed, a bot created with BotFather, `APP_URL` set to an
HTTPS address Telegram can reach (a tunnel works), a queue worker running, `APP_ENV=production` with
`php artisan monica:setup` run once so the route table is cached.

| Step | Expected |
| --- | --- |
| 1. Admin > Configuration, Telegram panel, with no Telegram value set | "Register" is shown, unavailable, and names the missing token |
| 2. Save the bot token on the screen | the panel now names the missing secret |
| 3. Press "Generate a secret" and confirm the password | "A secret is set"; the panel reads "not registered" |
| 4. Press "Register" | success naming the address; the bot address setting is filled with `https://t.me/<bot>` |
| 5. Press "Check with Telegram" | the address Telegram holds, marked as this instance's, no error |
| 6. As an account, Settings > Notifications > Telegram, follow the link, send `/start` | the channel connects, although the route table was cached before the token existed |
| 7. Type a new secret in the secret setting and save | a warning first; after the second save the panel reads "out of date: the secret changed" |
| 8. Register again | "registered", with today's date |
| 9. Admin > Record | two registrations and the setting changes, no secret or token shown |
| 10. `php artisan monica:setup-telegram` | same outcome; the Record names the system as author |

Failure checks: set `APP_URL=http://localhost` and register (refused before any call, naming the
address); block `api.telegram.org` and register (refused as unreachable in under 15 seconds).
