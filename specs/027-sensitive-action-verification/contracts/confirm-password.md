# Contract: confirming the password

## The session fact

`auth.password_confirmed_at`, a unix timestamp, written by `Session::passwordConfirmed()`. A proof is live while `time() - value < config('auth.password_timeout')`, which becomes 900. Read by the framework's `password.confirm` middleware and by nothing of ours. Gone when the session is invalidated, which sign out already does.

## Routes added

| Method | URL | Name | What it does |
| --- | --- | --- | --- |
| GET | `confirm-password` | `auth.confirmPassword.new` | The screen the gate redirects to. Offers the password, the passkey, or the explanation of FR-005 |
| POST | `confirm-password` | `auth.confirmPassword.create` | Takes `password`, marks the session, redirects to the intended URL. Rate limited |

Both sit in `routes/auth.php` behind `auth`. The passkey path needs no route added: `passkey.confirm-options` and `passkey.confirm` are registered by the package and already mark the session.

Whichever credential is given, the answer is `redirect()->intended(route('settings.security.index'))`, as a redirect for a browser and as `{"redirect": "…"}` for a request wanting JSON. The passkey side needs `App\Http\Responses\PasskeyConfirmationResponse` bound for that, beside the two response classes the project already binds, because the package defaults an unset destination to `/` and would drop somebody on the home page. Nothing on the screen picks a destination of its own: it follows what came back.

`PUT settings/account/profile/email`, `settings.email.update`, is added so that changing the address is gated without gating a change of name. `ProfileController::update` keeps the current address, `EmailController::update` keeps the current names, and both call `UpdateUserProfile`.

## Routes gated

Under `password.confirm:auth.confirmPassword.new`:

`settings.password.create`, `settings.password.update`, `settings.password.destroy`, `settings.twoFactor.new`, `settings.twoFactor.create`, `settings.twoFactor.destroy`, `settings.recoveryCodes.create`, `settings.passkeys.update`, `settings.apiKeys.create`, `settings.apiKeys.destroy`, `settings.email.update`, `settings.account.destroy`.

Through `config('passkeys.management_middleware')`, which holds `EnsurePasswordIsConfirmedWhenThereIsOne` rather than the framework middleware: `passkey.registration-options`, `passkey.store`, `passkey.destroy`. That one stands aside when the account has no password, since a passkey is the only thing such an account could give and the lost device is exactly when a new one is needed. The sign up flow reaches `passkey.store` before any password exists.

Not gated, and this is the boundary FR-002 draws: `settings.profile.update`, every vault route, every record route, and the whole of `routes/api.php`.

`GET settings/account/security/recovery-codes`, `settings.recoveryCodes.show`, is added for FR-013 and is not gated either. It renders only what the previous request flashed, so it is reachable but empty on its own, and it redirects to the security screen when there is nothing to show. `settings.security.index` stops carrying the codes: it says how many are left.

## Refusals

| Situation | Answer |
| --- | --- |
| No live proof, browser request | 302 to `auth.confirmPassword.new`, intended URL kept |
| No live proof, request wanting JSON | 423, which is what the framework sends |
| Wrong password | Back to the screen with one error saying the credential was wrong, nothing about which credential exists |
| Too many attempts | 429 from the named limiter, keyed on the user |
| Account holds neither credential | The screen, explaining, pointing at the password reset email. No field to fill in. Its passkey routes are open, so adding one is a way back too |
| Passkey assertion refused by the device | The screen or the modal says so through `aria-live`, and the password is still offered when the account has one |

## Log

Three cases on `UserActionEnum`, each naming what happened rather than one case carrying the credential as a parameter: `PasswordConfirmed`, `password_confirmed`, and `PasswordConfirmationRefused`, `password_confirmation_refused`, both written by `ConfirmPassword`; and `PasskeyConfirmed`, `passkey_confirmed`, written by `HandlePasskeyLogin`, which branches on the guard already holding somebody.
