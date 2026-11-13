# Quickstart: Password as the default registration method

How to prove the change works. The scenarios are the acceptance scenarios of [spec.md](spec.md).

## Prerequisites

- Registration open on the instance (`monica.registration_enabled`).
- The instance reachable over https for the passkey checks, and over http for one of them.
- Built assets: `bun run build`, or `composer run dev` running.

## Automated

```bash
php artisan test --compact tests/Unit/ViewModels/Auth/RegisterViewModelTest.php
```

```bash
php artisan test --compact tests/Feature/Controllers/App/Auth
```

```bash
php artisan monica:localize --check
```

```bash
vendor/bin/phpstan analyse --no-progress
```

```bash
vendor/bin/pint --dirty --format agent
```

Expected: every command passes, and `monica:localize` (run once without `--check`) reports two keys
dropped and none added.

## By hand, in a browser

| # | Do | Expect |
| --- | --- | --- |
| 1 | Open the registration screen over https | Password fields shown, button "Next step: validate your email address", "Use a passkey instead" under it |
| 2 | Fill it in and submit | Account created, email confirmation screen |
| 3 | Activate "Use a passkey instead" | Password fields gone, the sentence explaining a passkey, button "Create a passkey", control reads "Set a password instead", focus still on it |
| 4 | Submit with an email address already in use | The screen comes back on the passkey method, with the refusal on the field and no flash of the password fields |
| 5 | Submit with a new email address | Account created with no password, the passkey step opens |
| 6 | Activate "Set a password instead" after typing a name and an email address | Password fields back, name, email address and terms kept |
| 7 | Open the screen over http | Password form, no passkey control, no warning |
| 8 | Open it over https with `PublicKeyCredential` undefined (run `delete window.PublicKeyCredential` before Alpine starts, or use a browser profile with WebAuthn disabled) | Password form, no passkey control |
| 9 | Open it with JavaScript disabled, fill it in, submit | Password fields shown and required, account created |
| 10 | Go through 1, 3 and 6 with the keyboard only, then with a screen reader | Every control reachable, focus always visible, hidden fields never announced |

Steps 8, 9 and 10 have no automated equivalent.
