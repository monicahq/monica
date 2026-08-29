# Implementation Plan: WebAuthn Authentication

**Branch**: `2026-08-22-webauthn-auth` | **Date**: 2026-08-25 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/002-webauthn-auth/spec.md`

## Summary

Passkeys become a way to sign in on their own, and the account security screen gains a place to
manage them beside the password, the two factor setting and the API keys. Somebody holding a passkey
may remove their password entirely.

The integration follows `asbiin/laravel-webauthn-example`, the package author's own reference
application, rather than being invented here. That means: the `users` provider in `config/auth.php`
moves to `driver: webauthn`, which resolves a credential to its owner with no email address in the
request; the package's routes are used as they ship, at a `passkeys` prefix;
`App\Models\WebauthnKey` extends the package model and adds a `used_at` column; and everything this
application does around a sign in or a registration hangs off the package's events as listeners in
`app/Listeners`. The browser side is `@simplewebauthn/browser`, the library the reference
application uses, wrapped in a thin Alpine component.

Registration is the one surface the package cannot help with at all. A passkey is created against an
account that already exists, so signing up with one means creating the account with no password and
adding the passkey immediately after, in that order. That ordering is a property of the protocol and
it is what makes FR-052 necessary: a prompt abandoned between the two steps leaves an account with
neither credential.

Three things are genuinely this project's, because the example does not have them. The refusal
policy this application applies to every sign in, which goes into a
`Webauthn::authenticateThrough()` stage. Removing a password and the rules that keep a passwordless
account reachable, which is FR-024 to FR-028 and has no counterpart anywhere in the package. And the
passkey section on the security screen, whose mutations post to the package's routes but whose copy,
warnings and empty state are ours.

One line carries more risk than the rest of the feature. `users` is the only provider this
application has, so switching it to `webauthn` puts password sign in, magic link sign in and the
remember cookie through the new provider too. They survive on `webauthn.fallback`, which defaults to
true and appears neither in the published config nor in the example's. It is written in explicitly,
and step 1 proves it with a regression suite before any passkey code exists.

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13.8.

**Primary Dependencies**: `asbiin/laravel-webauthn` ^6.0, new, which brings `web-auth/webauthn-lib`
^5.3 and `web-auth/cose-lib` ^4.0. Version 6.0.0 declares `illuminate/support: ^11.0 || ^12.0 ||
^13.0` and `php: >=8.2`, so it fits this project as it stands. Existing frontend unchanged: Blade,
Tailwind, Alpine.js, Alpine Ajax, Turbo, instant.page. One new JavaScript dependency,
`@simplewebauthn/browser` ^13.1.0 in `dependencies`, added with `bun add`. It is what the reference
application uses, and at version 13 it takes and returns the JSON form of the WebAuthn structures,
so this project converts nothing.

**Storage**: the package migration creates `webauthn_keys`, keyed to `users.id` with
`cascadeOnDelete`. This feature adds one column to it, `used_at`, in a migration of its own.
`users.password` is already nullable, so an account with no password needs no schema change.

**Authentication**: `config/auth.php` provider `users` moves to `driver: webauthn`. The `web` guard
is untouched and stays `session`. `config/webauthn.php` is published with `prefix` at `passkeys`,
`userless` true, `fallback` true written explicitly, `user_verification` and `resident_key` both
`required`, `model` at `App\Models\WebauthnKey`, and `limiters.login` at a named limiter.

**Testing**: PHPUnit 12. `tests/Unit` for the listeners, the pipeline stage, the model, the actions
and the mailables, `tests/Feature` for the screens and the view models, mirroring the application
namespace. The suite has to pass on SQLite, MySQL and PostgreSQL, which the package migration
already accounts for: it indexes `credentialId` with a 255 character prefix on MySQL and in full
elsewhere.

**Target Platform**: server rendered web application on desktop, tablet and phone. The browser side
requires a secure context, which means HTTPS or localhost. HTTPS is forced in production.

**Project Type**: single Laravel project. No new top-level folder, so no amendment is needed.

**Performance Goals**: SC-013, the sign in screen and the security screen each under 500ms at the
95th percentile server side for an account holding twenty passkeys. The passkey list is one query
against an indexed foreign key, so the goal is met by not doing anything foolish rather than by
optimisation.

**Constraints**: WCAG 2.2 AA on every screen touched. Nine locales, strings produced by `php artisan
monica:localize`. Migrations written here define only `up()`. Larastan at PHPStan level 5 over
`app/`, `routes/` and `database/`. Pint clean. One deployment constraint the package imposes: the
challenge is held in the cache between the options request and the verification request, so a real
cache store is required in production and `array` or `null` will not do.

**Scale/Scope**: 0 new guest routes and 0 new passkey routes, since the package's are used. 2 new
signed in routes, for setting and removing a password. 5 listeners, 1 pipeline stage, 3 actions, 2
existing actions modified, 1 model, 1 migration, 2 view responses, 5 mailables, 5 enum cases on
`EmailType`, 6 on `UserActionEnum`, 2 view models extended, 1 new view model, 5 Blade partials, 1
Alpine component wrapping `@simplewebauthn/browser`, 2 config files and `package.json` touched.

The six `UserActionEnum` cases are `PasskeyAdded`, `PasskeyRenamed`, `PasskeyRemoved`,
`PasskeyLogin`, `UserPasswordRemoved` and `UserPasswordSet`. The five `EmailType` cases mirror the
five mailables.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-checked after Phase 1 design.*

| Principle | Verdict | How |
|---|---|---|
| I. Actions hold the logic | PASS, with one reading stated | The three operations this application owns (set a password, remove a password, and the refusal decision) live in actions. What lives in listeners is reaction, not operation: writing `used_at`, dispatching `LogUserAction`, sending an email. A listener that only dispatches an existing job is not business logic hidden outside `app/Actions`, and the alternative is a controller this project would not otherwise have. See Complexity Tracking. |
| II. Simplicity | PASS | Following the reference application removed seven routes, five controllers and four actions from the previous version of this plan, and `@simplewebauthn/browser` removes the base64url conversion rather than relocating it. What is left is what the example does not have. |
| III. A test for everything | PASS | Every listener, the pipeline stage, the model, the three actions, the five mailables, the three view models and both screens get a test. Plus the regression suite the provider change makes necessary: password, magic link and remember cookie sign in, all now travelling through `EloquentWebAuthnProvider`. |
| IV. Laravel as documented, code style binding | PASS | The integration is the one the package documents and its author ships. Route names and URLs are the package's, which do not breach the naming rules: every segment is a single lowercase word. `prefix` is set to `passkeys` so the public URLs read in the product's vocabulary. Migrations written here define only `up()`. The one new dependency is covered below. |
| V. Multilingual | PASS | Every string through `__()` or `trans_choice()`, including the two browser side messages, which are translated rather than taken from the example's English. Counted phrases go through `trans_choice()` with `:count` in every segment. `monica:localize --check` is part of the definition of done. |
| VI. Nothing changes under anybody's feet | PASS, with one care | `user_verification` and `resident_key` ship at `required` from the first commit. Both are checked at assertion time, so raising either later would stop credentials registered under the looser setting from asserting, which is the thing this principle forbids. Research section 9 records it, and records that the example chooses differently. |
| VII. One personalisation mechanism | N/A | The feature adds no user defined data and no second way to attach it. |
| VIII. No silent deletion | PASS | Removing a passkey warns first and says what is left. Removing a password warns first and names what still signs you in. A counter anomaly tells the owner rather than failing quietly. A revoked passkey is genuinely deleted rather than deactivated, which is the one place this principle is read narrowly: a credential its owner has revoked must stop existing, and keeping a disabled row would be a liability rather than a way back. |
| IX. Accessible to everybody | PASS, with one boundary | Our controls are keyboard operable, labelled, visibly focused and announced. The package's `notify` callback is wired to a live region, so a cancelled prompt is announced rather than only rendered. The boundary: the operating system's passkey prompt is not ours to style or to make accessible. |
| X. Everything through the API | PASS under the transitional rule | There is still no `Api` namespace. The three operations this project owns sit in actions. The rest is the package's own HTTP surface, which a future API can call or bypass as it chooses. |

### Re-check after Phase 1

The design changed one verdict's reasoning and no verdict. Principle I needed the reading spelled
out, because following the example moves work into listeners, and a reviewer is entitled to ask
whether that is logic escaping `app/Actions`. The line drawn is: a listener may dispatch a job,
stamp a timestamp or send an email, and may not decide whether something is allowed. Every decision
in this feature is in an action or in the pipeline stage.

Two things Phase 1 made concrete:

- **Principle III.** The provider change created a class of test that did not exist before: proving
  that authentication with nothing to do with passkeys still works. It is called out separately in
  step 1 because it is a regression suite, not a feature suite, and because it is what catches a
  `fallback` mistake.
- **Principle VIII.** `RemovePasskeyListener` cannot take the passkey as a loggable after the row is
  gone, so the log entry is dispatched from the action that performs the deletion, where the row is
  still in hand. This is the `LogUserAction` documented pattern and it is the reason removal is an
  action rather than a listener.

Product invariants: mono-user is respected, and reinforced by the schema, since `users.account_id`
is unique. A passkey belongs to one user and therefore to one account. The package's
`webauthn.update` and `webauthn.destroy` routes scope by the signed in user, which is what FR-036
needs, and step 4 tests it rather than trusting it.

## Project Structure

### Documentation (this feature)

```text
specs/002-webauthn-auth/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   ├── routes.md        # The HTTP surface, most of it the package's
│   └── browser-exchange.md  # What the browser and the server send each other
├── checklists/
│   └── requirements.md  # Written by /speckit-specify
└── tasks.md             # Written by /speckit-tasks, not by this command
```

### Source Code (repository root)

```text
app/
├── Actions/
│   ├── DestroyUserPassword.php               # remove the password, needs one passkey
│   ├── DestroyPasskey.php                    # revoke, log with the row in hand, notify
│   ├── RefusePasskeyLogin.php                # the pipeline stage's decision, testable alone
│   ├── AttemptLogin.php                      # MODIFIED: refuse a user with no password
│   └── UpdateUserPassword.php                # MODIFIED: set versus change, notify on set
├── Enums/
│   ├── EmailType.php                         # MODIFIED: five cases added
│   └── UserActionEnum.php                    # MODIFIED: six cases added
├── Http/
│   ├── Controllers/App/Settings/Account/Security/
│   │   └── PasswordController.php             # MODIFIED: create and destroy added
│   └── Responses/
│       ├── PasskeyLoginViewResponse.php       # the sign in screen, or the options as JSON
│       └── PasskeyRegisterViewResponse.php    # the registration step on the security screen
├── Listeners/
│   ├── RecordPasskeyUse.php                   # used_at, on the webauthn-lib assertion event
│   ├── HandlePasskeyLogin.php                 # last_login_at, CheckLastLogin, log, new login
│   ├── HandlePasskeyRegistration.php          # log, PasskeyAdded
│   ├── HandlePasskeyRegistrationFailure.php   # the counter anomaly notification
│   └── EnsurePasskeyLoginIsAllowed.php        # the authenticateThrough stage, calls the action
├── Mail/
│   ├── PasskeyAdded.php
│   ├── PasskeyRemoved.php
│   ├── PasswordRemoved.php
│   ├── PasswordSet.php
│   └── PasskeyCounterAnomaly.php
├── Models/
│   ├── WebauthnKey.php                       # extends the package model, merges used_at
│   └── User.php                              # MODIFIED: passkeys(), and the two questions of it
├── Providers/
│   └── AppServiceProvider.php                # MODIFIED: view responses, authenticateThrough,
│                                             #   the login limiter, https in production
└── ViewModels/
    ├── Auth/LoginViewModel.php               # MODIFIED: are passkeys usable here
    └── Settings/Account/Security/
        ├── SecurityViewModel.php             # MODIFIED: the passkey list, password state
        └── PasskeyListViewModel.php          # the list as the screen shows it

config/
├── auth.php                                  # MODIFIED: users provider driver becomes webauthn
└── webauthn.php                              # published, with the deviations research names

package.json                                  # MODIFIED: @simplewebauthn/browser in dependencies

database/migrations/
└── 2026_08_25_000000_add_used_at_to_webauthn_keys_table.php   # up() only

resources/
├── js/
│   ├── app.js                                # MODIFIED: register the component
│   └── passkey.js                            # Alpine wrapper over @simplewebauthn/browser
└── views/
    ├── app/auth/login.blade.php              # MODIFIED: the passkey button and its fallback
    ├── app/auth/register.blade.php           # MODIFIED: passkey primary, password an alternative
    ├── app/auth/register-passkey.blade.php   # the step that creates the credential
    └── app/settings/account/security/
        ├── index.blade.php                   # MODIFIED: the passkeys section
        ├── _passkeys.blade.php               # the list, or the empty state
        ├── _passkey-row.blade.php            # one passkey, rename and remove
        ├── _passkeys-unavailable.blade.php   # why they cannot be used here
        └── _remove-password.blade.php        # the passwordless step and its warning

routes/
└── web.php                                   # MODIFIED: two routes, for the password

tests/
├── Unit/
│   ├── Actions/            # one file per new and modified action
│   ├── Listeners/          # one file per listener
│   ├── Models/WebauthnKeyTest.php
│   └── Mail/               # one file per mailable
└── Feature/
    ├── Auth/ProviderRegressionTest.php   # password, magic link and remember, through the driver
    ├── Auth/PasskeyLoginTest.php         # the package's routes, this application's policy
    ├── Controllers/App/Settings/Account/Security/
    └── ViewModels/
```

**Structure Decision**: single Laravel project, every file in a folder the constitution already
names. `app/Listeners` and `app/Http/Responses` are the two folders this feature uses that the
constitution's file list does not mention. Neither is a new top-level folder, both sit under `app/`,
and both exist because the package's integration points are events and response contracts. Called
out here so a reviewer sees them rather than finds them.

## Implementation Order

Each step leaves the suite green, and steps 1 to 3 deliver User Story 1.

1. **Install, configure, and prove nothing broke.** Require the package. Publish
   `config/webauthn.php` and set `prefix` to `passkeys`, `userless` true, `fallback` true explicitly
   with a comment saying what it protects, `user_verification` and `resident_key` to `required`,
   `model` to `App\Models\WebauthnKey`, `limiters.login` to a named limiter, and `timeout`
   explicitly. Switch the `users` provider in `config/auth.php` to `webauthn`. Force https in
   production. Write the `used_at` migration and the model. Register the login limiter.

   Then, before any passkey code exists, write `ProviderRegressionTest`: password sign in, a wrong
   password, a deactivated user, an SSO user, magic link sign in with and without two factor, and
   the remember cookie. This is the step that catches a `fallback` mistake, and it has to come
   before the feature rather than after it.

   Settle three open items research names: whether `CACHE_STORE=array` carries the challenge between
   two requests in one test, whether the FR-014 throttle key can be shaped through `limiters.login`
   or has to move into the pipeline stage, and whether the package's `LoginViaRemember` listener
   does anything unwanted now that every sign in goes through the driver.
2. **Register a passkey.** `PasskeyRegisterViewResponse`, the Alpine wrapper over
   `@simplewebauthn/browser`, the passkeys section on the security screen,
   `HandlePasskeyRegistration` and `PasskeyAdded`. Registration posts to `webauthn.store`. Prove
   early that the package's serialised options are the JSON form the library expects, because a
   mismatch there is silent.
3. **Sign in with a passkey.** `PasskeyLoginViewResponse`, the button on the sign in screen,
   `RefusePasskeyLogin` with `EnsurePasskeyLoginIsAllowed` wired through
   `Webauthn::authenticateThrough()`, `RecordPasskeyUse`, and `HandlePasskeyLogin`. User Story 1 is
   complete and demonstrable.
4. **Manage passkeys.** Rename and remove on the security screen, posting to `webauthn.update` and
   `webauthn.destroy`, with the warning FR-031 requires. `DestroyPasskey` for the log entry and
   `PasskeyRemoved`. Test that another account's passkey is not reachable. User Story 2 done.
5. **Keep a way in.** The unavailable states on both screens from `browserSupportsWebAuthn()` plus
   the secure context test, both messages translated. The counter anomaly notification. The message
   for a credential bound to a former address. User Story 3 done, and Story 4 needs nothing beyond
   step 3, so it is verified here rather than implemented.
6. **Go passwordless.** `DestroyUserPassword`, the two password routes, the `AttemptLogin` guard for
   a null password, the `UpdateUserPassword` split between setting and changing, `PasswordRemoved`,
   `PasswordSet`, and the warning copy. Check whether any other screen confirms a password before
   acting, which the example's own Fortify workaround suggests is worth looking for. User Story 5
   done.
7. **Finish.** `php artisan monica:localize` and translate every reported string in all nine
   locales, Pint, PHPStan, the accessibility pass on the two screens, and the three connection run.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|---|---|---|
| The `users` provider changes from `eloquent` to `webauthn`, which puts every sign in in the application through a package class | It is how the package documents passwordless authentication and what the reference application does. It supplies the credential to user resolution FR-009 needs with no email address. `EloquentWebAuthnProvider` extends `EloquentUserProvider` and delegates to it for anything that is not a signed challenge, so the blast radius is one config key and one behaviour flag | Resolving the credential in our own action, which two earlier versions of this plan proposed. Rejected: it duplicates the provider's padded and unpadded base64 handling, and it means `Auth::attempt` can never be used for a passkey |
| Reaction lives in `app/Listeners` rather than in `app/Actions` | The package's integration points for what happens after a sign in or a registration are its events, and that is what the reference application uses. Putting `used_at`, the activity log and the notification emails on those events is how the package is meant to be extended | Wrapping the package's routes in our own controllers so the side effects could sit in actions, which the previous version of this plan did. Rejected: it rebuilds seven routes and five controllers to relocate three job dispatches. The line held instead is that a listener may react and may not decide |
| `user_verification` and `resident_key` are `required`, where the reference application has both at `preferred` | FR-020 promises that a passkey sign in needs no two factor code because the device verified its owner, which is only true at `required`. FR-009 promises nothing is typed, which needs a discoverable credential. Both are checked at assertion time, so they cannot be tightened later without breaking existing credentials | Matching the example. Rejected only because the specification says otherwise, and the trade is stated in research section 9: matching it would mean amending FR-020 to ask for the two factor code after a passkey sign in. That is a product decision, not a technical one |
| A new frontend dependency, `@simplewebauthn/browser` ^13.1.0 | The constitution fixes the frontend stack and requires approval to change dependencies. Approval was given explicitly. At version 13 the library takes and returns the JSON form of the WebAuthn structures, so it removes the base64url conversion from this repository rather than moving it around, and it is what the reference application uses | The package's bundled `resources/js/webauthn.js`, which would cost nothing since the PHP package is already being added. Rejected: it is not what the reference application reaches for, and it works in `ArrayBuffer`, so the caller carries the padding and substitution concern that version 13 removes. Hand written helpers were the first plan's answer and are worse still: fifty lines of encoding logic in the one place a mistake is hard to see |
| Two existing actions are modified rather than left alone | `AttemptLogin` would hand a null hash down the provider chain once an account can have no password, and `UpdateUserPassword` cannot tell setting a first password from changing an existing one, which FR-028 needs it to do | Wrapping either in a new action would leave the original reachable and wrong. The change to each is a few lines, and both already have tests to extend |
