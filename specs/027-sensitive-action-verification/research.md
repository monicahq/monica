# Research: Prove a credential before a sensitive action

**Date**: 2026-09-11 | **Spec**: [spec.md](spec.md)

## Where the proof lives

**Decision**: Laravel's own session fact, `auth.password_confirmed_at`, written with `Session::passwordConfirmed()`.

**Rationale**: it is already the session's answer to "this person proved themselves just now", and the passkey package writes it too (below), so one key covers both credentials. Nothing is persisted, which is what FR-006 asks for: the proof dies with the session.

**Alternatives considered**: a key of our own, which would mean a middleware of our own and two things meaning one thing. A column on `users`, which would make the proof survive sign out.

## The gate

**Decision**: the framework's `password.confirm` alias, `Illuminate\Auth\Middleware\RequirePassword`, with the route name passed as a parameter: `password.confirm:auth.confirmPassword.new`. The sensitive routes are grouped so it is written twice, not twelve times.

**Rationale**: it reads the key above, honours the timeout, and answers 423 to a request that wants JSON. FR-008 is then the framework's behaviour rather than ours.

**Alternatives considered**: naming our route `password.confirm` so the default applies, rejected because a passkey satisfies it too and the name would say otherwise. A subclass to carry the default, rejected as a class that exists to hold one string.

## The window

**Decision**: `config/auth.password_timeout` goes from 10800 to 900, fifteen minutes, still read from `AUTH_PASSWORD_TIMEOUT`.

**Rationale**: FR-006. The 10800 is the framework's shipped default and nothing in this project chose it.

## The passkey proof

**Decision**: the package already ships it. `GET passkeys/confirm/options` and `POST passkeys/confirm` verify an assertion for the signed in user and call `session()->passwordConfirmed()`. The browser reaches them with `Passkeys.verify({ routes: { options, submit } })`, which the client supports per call. Nothing new server side.

**Rationale**: the ceremony, the options in the session, the throttling and the refusals are all written and tested upstream.

## A confirmation is not a sign in

**Decision**: `HandlePasskeyLogin` branches on whether the guard already holds somebody, and records a proof rather than a sign in when it does.

**Rationale**: both flows run the same `VerifyPasskey` action, so `PasskeyVerified` fires for both. Today a confirmation would move `last_login_at`, write a `PasskeyLogin` and email "new login detected". The discriminator is sound: `PasskeyLoginController` verifies before it calls `login()`, and its route is behind `guest`, so the guard is empty for a sign in and full for a confirmation.

## The passkey management routes

**Decision**: `config/passkeys.php` `management_middleware` holds `EnsurePasswordIsConfirmedWhenThereIsOne`, which asks only when the account has a password.

**Rationale**: the comment sitting there was this feature's reason to exist, and it was right. An account with no password can give nothing but a passkey, and the moment a new passkey is needed is the moment the old one is on a telephone in a taxi. The sign up flow makes it sharper still: `RegistrationPasskeyController` creates the account with no password and lands on `/register/passkey`, so a plain gate shuts the door on the way in.

**Alternatives considered**: the framework middleware on all three routes, which shuts a passwordless account out of its own way back. A different rule for `passkey.destroy` than for registration, which the package cannot express: it reads one array for all three.

## The lost device

**Decision**: two ways back, and both stay open. A passwordless account adds a passkey with nothing asked. An account with a password uses the password reset email.

**Rationale**: the server cannot tell a lost passkey from a present one, so it cannot ask for one safely. `forgot-password` is unauthenticated and untouched by this feature, which covers the account that has a password but cannot reach it.

**Accepted**: a taken over session on a passwordless account can add its own passkey. Closing that would close the way back with it, and the way back is worth more.

## Resuming the action

**Decision**: a third modal beside `modal/warning` and `modal/form`, shown in front of the sensitive control, which submits the form once the proof is given. The middleware stays as the backstop and the screen it redirects to is the path for a request that arrives without one.

**Rationale**: FR-009. `Redirector::guest()` sets the intended URL to the *previous* page for anything that is not a GET, so the framework's redirect lands back on the settings screen and loses the payload. Proving before the form is sent keeps what was typed in the form, where it already is.

**Alternatives considered**: stashing the intercepted request and replaying it, which means holding an arbitrary payload in the session. Redirecting with `withInput()` and asking for a second press, which FR-009 forbids.

## The rest

- **Rate limiting**: a named limiter beside `passkey` in `AppServiceProvider`, keyed on the user. The passkey side is already covered by `throttle:passkey`.
- **Logging**: two cases on `UserActionEnum`, one for a proof and one for a refusal, written by the action and by the listener.
- **Untouched**: every action under `app/Actions`, and the whole JSON API. FR-010 leaves the token as the credential.
