# Quickstart: validating the swap

Passkeys need a secure context. `PasskeyAvailability` exempts nothing, localhost included, so use the
https host Herd or Valet provides and set `APP_URL` to it: `relying_party_id` and `allowed_origins`
are both derived from it, and a mismatch fails every ceremony with an origin error.

## Setup

```bash
composer require laravel/passkeys
php artisan vendor:publish --tag=passkeys-migrations
php artisan vendor:publish --tag=passkeys-config
bun install && bun run build
php artisan migrate:fresh --seed
```

`migrate:fresh` is the point: FR-004 drops `webauthn_keys`, so any passkey enrolled before the change
is gone and has to be created again.

## Scenarios

Each maps to a user story in [spec.md](spec.md).

1. **Sign in (US1)**. Register an account, add a passkey, sign out, sign in from the passkey button
   without typing an address. Check the security screen shows a fresh "last used", the account
   journal names the passkey, and a new-sign-in email is queued.
2. **Refusal before verification (US1, FR-005)**. Deactivate the user, then try the same passkey. The
   refusal must read the same as a wrong password, `last_used_at` must **not** move, and no journal
   entry or email may appear. Those three absences are the point of research D1.
3. **Manage (US2)**. Add a second passkey, rename one, remove one. Renaming goes through the kept
   route, removal through the package's. Confirm the removal warning appears first and the message
   afterwards still says what is left to sign in with.
4. **Passkey-only account (US3)**. Register choosing a passkey, confirm no password is stored, then
   add a second passkey without being asked to confirm a password.
5. **Announcements (FR-011)**. Cancel the device prompt at each ceremony. The live region must say
   something happened; silence is the failure this catches.

## Checks

```bash
php artisan test --compact --filter=Passkey
vendor/bin/pint --dirty --format agent
php artisan monica:localize --check
```

The suite is the real gate: [spec.md](spec.md) SC-001 keeps every existing assertion except the two
FR-009 repeals.
