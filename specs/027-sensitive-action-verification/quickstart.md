# Quickstart: prove a credential before a sensitive action

What to run, and what to look at, to see that the feature does what [spec.md](spec.md) says.

## Prerequisites

`composer install`, `bun install`, `bun run build`, a seeded database. Passkeys need a secure origin, so drive the browser over https or on localhost, which browsers treat as secure.

## The suite

```bash
php artisan test --compact tests/Feature/Controllers/App/Auth/ConfirmPasswordControllerTest.php
php artisan test --compact tests/Feature/Controllers/App/Settings/Account
php artisan test --compact --filter=confirmPassword
vendor/bin/pint --dirty --format agent
php artisan monica:localize --check
```

The suite has to pass on the three connections, so run it once under SQLite and once under PostgreSQL before the pull request. Nothing here writes SQL, so the risk is low and the check is cheap.

## By hand

1. **The gate.** Sign in, open `settings/account/security`, press "Remove my password". The modal asks for the password. Nothing has changed until it is given, and giving it carries the removal out with no second press. This is FR-001, and it is the only check FR-009 gets: the resumption is browser behaviour and the suite holds no browser test.
2. **Straight at the route.** With the same session, `curl -X DELETE` the same URL with a valid token for the form. It answers 302 to `confirm-password`, and the password is still there. This is FR-008.
3. **The window.** Prove once, then create an API key and delete it. Neither asks again. Set `AUTH_PASSWORD_TIMEOUT=60`, wait a minute, and the next one asks. This is FR-006.
4. **The passkey.** Add a passkey, remove the password, sign out and back in with the passkey. Every sensitive control now offers the passkey and no password field. This is FR-002 of the spec's second story.
5. **Neither credential.** In `tinker`, null the password and delete the passkeys of a test account, then reach a sensitive action. The screen explains and names the password reset email. This is FR-005.
6. **The refusal.** Give a wrong password six times. The sixth answers 429, and the log holds six refusals beside no change. This is FR-007 and FR-011.
7. **The codes once.** Turn two factor authentication on. The screen that follows prints the codes. Go back to `settings/account/security`: a count, and no code. Press "Get new codes" and land on the same screen with a new set. Reload it and it sends you back empty handed. This is FR-013.
8. **The API.** With a token, `POST /api/v1/api-keys` and `PUT /api/v1/me` with a new address. Both succeed exactly as before. This is FR-010.

## Accessibility

Do step 1 again with the mouse unplugged: tab to the control, press it, land inside the modal, tab through it without escaping, press escape, and land back on the control that opened it. Then repeat with a screen reader and confirm the refusal is read out. Zoom to 200% and confirm the modal still fits. This is FR-012 and constitution principle IX.

## What should not have changed

The sign in screen, the magic link, the password reset emails, the two factor challenge and the codes that answer it, every vault and record screen, and every endpoint in `routes/api.php`.
