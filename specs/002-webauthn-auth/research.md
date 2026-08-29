# Research: WebAuthn Authentication

**Feature**: `002-webauthn-auth` | **Date**: 2026-08-25

Every claim below about `asbiin/laravel-webauthn` was read off the package at version 6.0.0 or off
`master`, and every claim about the reference application was read off
[`asbiin/laravel-webauthn-example`](https://github.com/asbiin/laravel-webauthn-example) on `main`.
Nothing here is recalled. Where something was inferred rather than read, it says so and it becomes a
task in step 1 of the implementation order.

**Revision history.** This document has been corrected three times, twice because it had not read
far enough into the package and once on the browser library.

1. The first version claimed the package could not sign somebody in without an email address, and
   planned to hand write the lookup from credential to user. That was read off `LoginUserRetrieval`,
   the controller pipeline, without reading `EloquentWebAuthnProvider`, the auth driver. The driver
   already does that lookup.
2. The second version kept `Webauthn::ignoreRoutes()` and rebuilt the HTTP surface, on two arguments
   that do not survive the reference application. The route names do not breach this project's
   naming rules, and the side effects the package pipeline "cannot carry" are carried by listeners
   on its events. Both are corrected in sections 5 and 6.
3. Sections 10 said to hand write the base64url conversion, then said to use the package's bundled
   `resources/js/webauthn.js`. Both are wrong. The reference application uses
   `@simplewebauthn/browser`, and so does this plan. Section 10 is rewritten.

## 1. The package, and whether it fits

**Decision**: require `asbiin/laravel-webauthn` ^6.0.

**Rationale**: 6.0.0 was released on 2 June 2026 and declares `php: >=8.2` and
`illuminate/support: ^11.0 || ^12.0 || ^13.0`. This project is PHP ^8.4 on `laravel/framework`
^13.8, so it is inside the supported range with no constraint gymnastics. Underneath it uses
`web-auth/webauthn-lib` ^5.3, which is the reference PHP implementation and the thing actually doing
the cryptography, the origin check and the signature counter check. It is also the project owner's
own package, so a bug found here has the shortest possible path to a fix.

## 2. The reference application is the shape to follow

**Decision**: take the authentication integration from `asbiin/laravel-webauthn-example` rather than
inventing one.

**Rationale**: it is the package author's own application and it settles, in working code, every
question this plan was answering from first principles. What it does:

| Concern | How the example does it |
|---|---|
| User resolution | `config/auth.php` provider `users` at `driver: webauthn` |
| Credential storage | `App\Models\WebauthnKey` extending the package model, with `used_at` |
| Last use | a listener on the `webauthn-lib` validation success event |
| Side effects | listeners on `WebauthnLogin`, `WebauthnRegister`, `WebauthnRegisterFailed` |
| Screens | `Webauthn::loginViewResponseUsing()` and `registerViewResponseUsing()` |
| Routes | the package's, untouched. No `ignoreRoutes()` anywhere |
| Pipeline | `authenticateThrough()`, used on the Fortify side, available on the Webauthn side |
| Browser | `@simplewebauthn/browser` ^13.1.0, not the package's bundled helper |
| Deployment | HTTPS forced in production, because WebAuthn requires it |

What the example does **not** cover, and this feature still has to design: removing a password and
the passwordless account rules (FR-024 to FR-028), the refusal policy this application applies to
every sign in, the two factor interaction of FR-020 and FR-022, and the passkey management section
on the security screen. Those are the parts that are Erica's and not the package's.

The example is a Jetstream and Fortify application, so its scaffolding, its Inertia views and its
Vue components are not reusable here. Its *mechanics* are, and that is what is taken.

## 3. The `webauthn` auth driver

**Decision**: change the `users` provider in `config/auth.php` from `eloquent` to `webauthn`, which
is what the example does and what the README calls option 2.

```php
'providers' => [
    'users' => [
        'driver' => 'webauthn',
        'model' => env('AUTH_MODEL', User::class),
    ],
],
```

**Rationale**: the driver is `LaravelWebauthn\Auth\EloquentWebAuthnProvider`, which extends
`EloquentUserProvider` and overrides three methods:

```php
public function retrieveByCredentials(array $credentials): ?User
{
    if ($this->isSignedChallenge($credentials)) {
        try {
            $webauthnKey = (Webauthn::model())::where('credentialId', ...)
                ->orWhere('credentialId', ...)
                ->firstOrFail();

            return $this->retrieveById($webauthnKey->user_id);
        } catch (ModelNotFoundException $e) {
            return null;
        }
    }

    return parent::retrieveByCredentials($credentials);
}
```

`isSignedChallenge()` is true when the array carries `id`, `rawId`, `type` and `response`, which is
the assertion the browser returns and nothing else. So a raw assertion resolves to its owner with no
email address anywhere in the request, which is FR-009. `validateCredentials()` then calls
`Webauthn::validateAssertion($user, $credentials)`.

The credential id is stored base64 encoded and the provider queries it two ways, padded and
unpadded. That is the kind of detail a reimplementation gets wrong once and then carries, which is
the strongest single argument for not writing this here.

## 4. `webauthn.fallback`, the riskiest line in the feature

**Decision**: publish `config/webauthn.php` with `'fallback' => true` written explicitly, even
though it is already the default and even though the example does not write it.

**Rationale**: the `users` provider is the only provider this application has, so switching it to
`webauthn` puts every sign in through `EloquentWebAuthnProvider`, password sign in included.
Password sign in survives because of this, in `validateCredentials`:

```php
if ($this->fallback) {
    return parent::validateCredentials($user, $credentials);
}

return false;
```

and because `retrieveByCredentials` delegates to `parent::` for anything that is not a signed
challenge. With `fallback` true, an email and password pair behaves exactly as it does today. With
it false, every password sign in in the application returns false and nobody gets in without a
passkey.

The trap is that `fallback` is read as `$config->get('webauthn.fallback', true)` and **appears
neither in the published config nor in the example's config**. Somebody reading either file to learn
what is configurable will not see the one key that keeps password sign in alive. Writing it in, with
a comment, costs one line and removes a silent way to lock an instance out.

This is why FR-018 gets a regression suite of its own rather than being assumed: password sign in,
magic link sign in and the remember cookie all now travel through a provider they did not travel
through before.

## 5. The package's routes are kept

**Decision**: no `Webauthn::ignoreRoutes()`. Use the package's routes, set
`config('webauthn.prefix')` to `passkeys`, and supply this application's own screens through
`Webauthn::loginViewResponseUsing()` and `Webauthn::registerViewResponseUsing()`.

**Rationale**: this reverses the previous version, whose argument does not hold up.

That argument was that names like `webauthn.auth.options` and `webauthn.store.options` breach the
camelCase route name rule. They do not. The rule asks for camelCase segments, as in
`contacts.shareLinks.create`, and every segment in every package route name is a single lowercase
word, whose camelCase form is itself. There is nothing to breach. The URLs are the same story:
`webauthn/auth` and `webauthn/keys/{id}` are already kebab-safe.

The one adjustment worth making is vocabulary rather than style. This product says passkey
everywhere, so `config('webauthn.prefix')` becomes `passkeys` and the public URLs read
`passkeys/auth` and `passkeys/keys/{id}`. The route names stay `webauthn.*`, because the package
owns them and renaming them is not offered. That mismatch is documented and accepted.

The routes, at that prefix:

| Verb | URL | Name |
|---|---|---|
| GET | `passkeys/auth` | `webauthn.login` |
| POST | `passkeys/auth/options` | `webauthn.auth.options` |
| POST | `passkeys/auth` | `webauthn.auth` |
| GET | `passkeys/keys/create` | `webauthn.create` |
| POST | `passkeys/keys/options` | `webauthn.store.options` |
| POST | `passkeys/keys` | `webauthn.store` |
| PUT | `passkeys/keys/{id}` | `webauthn.update` |
| DELETE | `passkeys/keys/{id}` | `webauthn.destroy` |
| POST | `passkeys/confirm-key` | `webauthn.key.confirm` |

`webauthn.key.confirm` is not used by this feature and is left alone rather than removed.

**Alternatives considered**: `ignoreRoutes()` plus seven of our own routes, controllers and actions,
which is what the previous version specified. Rejected: it was justified by a style breach that does
not exist and by a side effect problem that section 6 solves, so what remains is duplicated
transport.

## 6. Side effects go on events, and policy goes in the pipeline

**Decision**: everything this application does around a sign in or a registration hangs off the
package's events, as auto-discovered listeners in `app/Listeners`. The refusal policy goes into
`Webauthn::authenticateThrough()`.

**Rationale**: the previous version claimed the package pipeline could not carry seven obligations
from the specification. It carries all of them, in the example's own idiom.

The example has `WebauthnLoginHandler` on `WebauthnLogin`, `WebauthnRegisterHandler` on
`WebauthnRegister`, `WebauthnRegisterFailedListener` on `WebauthnRegisterFailed`, and
`WebauthnAuthenticateListener` on the `webauthn-lib` validation event. Each does logging and
notifications and nothing else. That is exactly the shape of Erica's own side effects:

| Obligation | Where it goes |
|---|---|
| `used_at` on the passkey (FR-006, FR-023) | listener on `AuthenticatorAssertionResponseValidationSucceededEvent` |
| `last_login_at`, `CheckLastLogin` (FR-016) | listener on `WebauthnLogin` |
| `LogUserAction` for a passkey sign in (FR-016) | listener on `WebauthnLogin` |
| `NewLoginDetected` (FR-017) | listener on `WebauthnLogin` |
| `LogUserAction` and `PasskeyAdded` (FR-035) | listener on `WebauthnRegister` |
| `PasskeyCounterAnomaly` (FR-012) | listener on `WebauthnRegisterFailed` and the assertion failure event |
| `is_active`, `sso_provider`, identical refusal message, `LoginFailed` (FR-013, SC-004) | a pipeline stage in `authenticateThrough()` |

Laravel 13 discovers listeners in `app/Listeners` from the `handle()` type hint, so no
`EventServiceProvider` is needed. The example relies on exactly that: it registers no listeners
anywhere.

The refusal policy is the one piece that is a decision rather than a reaction, and a pipeline stage
is where the package puts decisions. `Webauthn::authenticateThrough()` takes the list, the way the
example's `Fortify::authenticateThrough()` does, and the stage runs before
`AttemptToAuthenticate::class`, so a refused user never reaches the assertion check.

**Open item**: whether the throttle key can be made per credential inside
`EnsureLoginIsNotThrottled`, or whether the Erica stage has to own the throttle to satisfy FR-014.
Step 1.

## 7. The model, and `used_at`

**Decision**: `App\Models\WebauthnKey` extending `LaravelWebauthn\Models\WebauthnKey`, and a column
named `used_at`. Both follow the example exactly.

The example's model merges rather than redeclares, which matters because the parent sets `$visible`
and a plain redeclaration would drop the protocol fields:

```php
public function __construct(array $attributes = [])
{
    parent::__construct($attributes);

    $this->mergeFillable(['used_at']);
    $this->setVisible(array_merge($this->getVisible(), ['used_at']));
    $this->mergeCasts(['used_at' => 'datetime']);
}

public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

**Naming**: the class is `WebauthnKey` and the column is `used_at`, not `Passkey` and `last_used_at`
as the previous version had it. The example's names win. The table is `webauthn_keys` either way, so
a model called `WebauthnKey` on `webauthn_keys` reads correctly, and the previous mismatch between a
`Passkey` class and a `webauthn_keys` table disappears. The product still says passkey in every
string a person reads, which is a copy decision and not a class name.

**The migration** is the example's, with one change this project's rules require and one it does not
need:

```php
Schema::table('webauthn_keys', function (Blueprint $table): void {
    $table->timestamp('used_at')->nullable();
});
```

No `down()`, because the constitution forbids one in a migration this project writes. No
`->after('counter')`, because it is a MySQL only hint and this suite runs on three drivers, where it
is inert and buys nothing.

`used_at` stays nullable with no default, because a passkey never used is a state FR-006 asks the
screen to show, and defaulting it to the creation time would erase it.

## 8. `userless`, and what it gates

**Decision**: `userless` true, as the example has it, through `WEBAUTHN_USERLESS` defaulting to
true.

**Rationale**: `AuthenticateController::create()` decides whether to look a user up at all:

- when `Webauthn::userless()` is true, or `user_verification` is `discouraged`, it passes `null` to
  `PrepareAssertionData`;
- otherwise it calls `LoginUserRetrieval`, which reads `$request->only([Webauthn::username(),
  'password'])` and throws when that resolves to nobody.

`RequestOptionsFactory::getAllowedCredentials(?User $user)` then returns `$user !== null ?
$this->repository->getRegisteredKeys($user) : []`, so a null user produces request options with an
empty `allowCredentials`. That is the discoverable credential flow, and it is why
`webauthn.auth.options` can be posted with no email at all.

## 9. User verification and resident keys, where this feature is stricter than the example

**Decision**: `user_verification` and `resident_key` both `required`, from the first commit. The
example ships both at `preferred`.

**Rationale**: this is the one place the reference application is deliberately not followed, and the
reason is the specification rather than taste.

FR-020 says a passkey sign in does not ask for the two factor code, and the reason it gives is that
the device released the credential only after its owner proved themselves to it. At
`user_verification: preferred` an authenticator may skip that proof, so the assertion can carry one
factor while the product claims two. `required` is what makes the sentence in FR-020 true rather
than usually true.

FR-009 needs the credential to be discoverable. At `resident_key: preferred` an authenticator may
create a non-resident credential, which will never appear in a sign in that names nobody, so a
person would be quietly unable to use the passkey they just made.

The timing is what makes this awkward to defer. Both are checked when an assertion is verified, not
only when a credential is created, so raising either after credentials exist stops credentials
registered under the looser setting from asserting. That is the failure Principle VI exists to
prevent, so they ship correct or they cost a migration of people's devices later.

The cost is real and worth stating: `required` on both excludes authenticators that cannot verify
their owner, including older security keys with no PIN, and eats resident key slots on hardware keys
that have few. The example accepts that cost in the other direction because it is a demonstration
rather than a product making the FR-020 promise. **If you would rather match the example, FR-020 has
to change: a passkey sign in would then have to ask for the two factor code when it is enabled.**

## 10. The browser side is `@simplewebauthn/browser`

**Decision**: `bun add @simplewebauthn/browser` at ^13.1.0, in `dependencies`, wrapped in a thin
Alpine component. Do not use the package's bundled `resources/js/webauthn.js`, and do not hand write
the conversions.

**Rationale**: this is the third answer this document has given to the same question, and the
reference application settles it. Its `package.json` pins `@simplewebauthn/browser` at `^13.1.0`,
and `WebauthnLogin.vue` uses it directly:

```js
import { startAuthentication, browserSupportsWebAuthn } from '@simplewebauthn/browser';

startAuthentication({ optionsJSON: publicKey, useBrowserAutofill: props.autofill })
```

At version 13 the library takes and returns the JSON form of the WebAuthn structures, where every
binary field is already a base64url string. That removes the conversion layer entirely rather than
relocating it: the server's serialised options go in as `optionsJSON` untouched, and what comes back
posts straight to the route. Neither of the two earlier answers does that. Hand writing it puts the
most error prone part of a WebAuthn integration in this repository, and the package's bundled helper
still works in `ArrayBuffer` and needs the caller to think about padding.

**Placement**: `dependencies`, not `devDependencies` where the example has it. This project keeps
runtime libraries (`alpinejs`, `@hotwired/turbo`, `instant.page`) in `dependencies` and build
tooling in `devDependencies`, and the local convention wins over the example's file layout. The
package manager is `bun`.

**The dependency question**: the constitution lists the frontend stack and says dependencies are not
changed without approval. This addition was approved explicitly. It is not the single page framework
the constitution rules out; it is a credential handling library with no rendering opinion, and it
leaves Blade, Alpine and Turbo exactly as they are.

**Support detection**: `browserSupportsWebAuthn()` returns a boolean and not a reason, so the reason
is worked out the way the example's `resources/js/methods.js` does it: an insecure context on a host
that is not `localhost` or `127.0.0.1` asks for https, and anything else says the browser cannot do
passkeys. Both sentences go through `__()`.

**One thing to verify rather than assume**: that the options the package serialises are field for
field the JSON form the library expects. Both follow the same specification so they should agree,
but a mismatch is silent and surfaces as an unusable prompt. It is a task in step 2, not a hope.

**Alternatives considered**: the package's `resources/js/webauthn.js`, which ships with a dependency
this project is already adding and would therefore be free. Rejected because it is not what the
reference application uses and because it works in `ArrayBuffer`, so the caller carries the encoding
concern that version 13 of the library removes. Hand written helpers were the first version's answer
and are rejected outright: roughly fifty lines of padding and substitution logic, in the one place a
mistake is hard to see and easy to ship.

## 11. HTTPS, and the cache

**Decision**: force the https scheme in production, as the example does in
`AppServiceProvider::boot()` with the comment that WebAuthn requires it. Document a real cache store
as a deployment requirement.

**Rationale**: the challenge is created during the options request and checked during the
verification request, and the package holds it in the cache in between. Its README is explicit that
`array` and `null` will not do.

In the test suite this is an open question, and an honest one. `phpunit.xml` sets
`CACHE_STORE=array`. Within a single test the two requests run in one process, so the array store
may well carry the challenge, in which case nothing changes. If it does not, the fix is
`CACHE_STORE=database`, since `0001_01_01_000001_create_cache_table.php` already exists and works on
all three connections. Not verified by reading code, so it is the first task in step 1.

## 12. An account with no password

**Decision**: no schema change. Guard `AttemptLogin` against a null hash, and take the example's
hint about password confirmation.

**Rationale**: `users.password` is already `nullable()`, commented as null when the user signs in
through SSO. A passwordless passkey account widens what that null means without changing the column.

The guard is required. `AttemptLogin` refuses early when the candidate is null, inactive or an SSO
user, then calls `Auth::attempt`. Once a password can be absent for another reason, that call
reaches `validateCredentials`, which with `fallback` true hands a null hash to
`EloquentUserProvider`. One more condition on the existing early return covers it, with the same
message as every other refusal, which FR-027 requires.

The example flagged something this plan had missed. Its `FortifyServiceProvider` replaces the
password confirmation validator so that passwordless users can get past it. Erica has the same
problem in a different place: `PasswordController::update` validates `'current_password' =>
['required', 'string', 'current_password']`, and that rule cannot pass for an account with no
password. That is why setting a password is a separate route with no `current_password` rule, and it
is worth checking whether any other screen confirms a password before acting.

`UpdateUserPassword` is extended rather than duplicated: it reads whether a password was there
before writing the new one, logs `UserPasswordSet` or `UserPasswordUpdate` accordingly, and sends
`PasswordSet` only in the first case. Password changes keep sending no email, as today, because
FR-028 asks for notification on removing and on setting, not on every change.

## 13. Rate limiting

**Decision**: set `config('webauthn.limiters.login')` to a named limiter, as the example does, and
register that limiter with the same shape `AttemptLogin` already uses.

**Rationale**: the example sets `limiters.login` to `'login'` and defines the limiter in its service
provider at five a minute per email and IP, which is exactly `AttemptLogin::MAX_ATTEMPTS`. Erica
does the same, except that a passkey sign in has no email, so the key is the credential id and the
IP (FR-014). With the package's routes in use, `EnsureLoginIsNotThrottled` is the stage that reads
it, which is why section 6 leaves it open whether the key can be shaped there or has to move into
the Erica stage.

## 14. Naming, and one inconsistency worth stating

**Decision**: mailables are `PasskeyAdded`, `PasskeyRemoved`, `PasswordRemoved`, `PasswordSet` and
`PasskeyCounterAnomaly`, with no `Mail` suffix.

**Rationale**: `.claude/rules/code-style.md` says mailables take a `Mail` suffix. The codebase does
not: `app/Mail` holds `LoginFailed`, `NewLoginDetected`, `MagicLinkCreated` and the rest, all
unsuffixed. Following the rule here would leave `app/Mail` half suffixed, which is worse for a
reader than either convention applied consistently. The constitution's own follow-up list already
records that `.claude/CLAUDE.md` states things this repository contradicts, and this is one more for
that list rather than something to settle inside this feature.

The example uses notifications rather than mailables. Erica uses mailables dispatched through the
`SendEmail` job with an `EmailType`, because that is how every email in this application is already
sent and because `emails_sent` records them. The example's mechanism is not followed here.

## 15. Account deletion, and soft deleted users

**Decision**: FR-037 needs no new code, only a test.

**Rationale**: `DestroyAccount` hard deletes the account row, described in the action as the one
hard delete in the product. `users.account_id` is declared
`foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete()`, and
`webauthn_keys.user_id` cascades from `users.id`. Deleting an account removes its user row and its
passkeys at the database level, on all three connections.

A soft deleted user is a different case and the cascade does not fire, which is correct: FR-013
wants the assertion refused, not the credential destroyed. `retrieveByCredentials` ends in
`retrieveById($webauthnKey->user_id)`, which is `EloquentUserProvider`'s and applies the model's
global scopes, so a soft deleted user resolves to null. That inheritance is the reason it works, so
it gets a test rather than a comment.

## 16. Signing up with a passkey, and the one thing the package cannot help with

**Decision**: create the account with no password, then create the passkey, in that order.

**Rationale**: there is no other order available. `Webauthn::prepareAttestation(User $user)` takes a
user, and `CredentialRepository` scopes by one, so a credential cannot be built for somebody who
does not exist yet. The package offers nothing for the case of registering a credential and an
account together, and neither does `web-auth/webauthn-lib`: the user handle is part of what gets
signed, so it has to be settled first.

So `CreateAccount` gains a nullable password, the account is made, the person is signed in, and the
device prompt happens on the next screen with the package's ordinary registration routes, which work
because there is now a signed in user.

**What that costs, and why it is accepted**: a prompt abandoned between the two steps leaves an
account holding neither a passkey nor a password. That state is already reachable through User Story
5, and FR-033 already warns about it there, so it is not new. What is new is reaching it by accident
rather than by choice, and that is what FR-052 answers: the account is not undone, because it is
real and the emailed link reaches it, and the next screen says so and offers both routes.

**Alternatives considered**: holding the whole registration in the session and creating the account
only once the credential exists. Rejected: the user handle signed into the credential would have to
be invented before the user, and reconciling an invented handle with the real one afterwards is
either a lie in the credential or a second registration. Also considered creating the account with a
throwaway password and deleting it, which is the same end state reached by a worse route, and would
put a real password hash on disk for no reason.

## Open items carried into implementation

Four of the six were settled during Phase 1 and 2. What was found is recorded here rather than only
in the task list, because the answers change what a later reader should believe.

1. **Settled. The test cache store carries the challenge.** `CACHE_STORE=array` in `phpunit.xml`
   does carry a value from one request to the next inside a single test, because both run in one
   process. `phpunit.xml` is left alone. Proven by
   `tests/Feature/Auth/PasskeyChallengeTest::the_configured_cache_store_carries_a_challenge_between_two_requests`.
   The production requirement stands and is now written into `.env.example`.
2. **Settled, and the answer is neither option.** The FR-014 throttle key is shaped in the named
   limiter, not in a pipeline stage. `AuthenticateController` drops `EnsureLoginIsNotThrottled` from
   the pipeline entirely as soon as `config('webauthn.limiters.login')` is not null, and the routes
   file then applies `throttle:<limiter>` to the guest group instead. So naming a limiter replaces
   the package's throttle rather than configuring it.

   This matters more than it looks. The package's `LoginRateLimiter::throttleKey()` is
   `Str::lower($request->input(Webauthn::username())).'|'.$request->ip()`, and a userless sign-in
   sends no username, so that key collapses to `'|'.$ip`: everybody behind one address would share a
   single allowance. The `login` limiter in `AppServiceProvider` keys on the credential id and the
   address instead.
3. **Settled. Do not register `LoginViaRemember`.** The package does not register it either; it is
   opt-in. It exists to mark the webauthn session flag satisfied when somebody returns on a remember
   cookie, which only matters when `WebauthnMiddleware` is gating routes as a second factor. This
   feature makes a passkey a primary credential and gates nothing, so registering it would do
   nothing.
4. **Mostly settled for the assertion side.** The options the package serialises come out as
   `{"publicKey":{"challenge":"...","timeout":60000,"rpId":"...","allowCredentials":[],"userVerification":"required"}}`,
   with the challenge in unpadded base64url. That is the JSON form `@simplewebauthn/browser` 13
   reads, and `tests/Feature/Auth/PasskeyChallengeTest` pins the encoding. The attestation side,
   whose options carry `user.id` and `excludeCredentials`, is still to be checked in Phase 3.
5. Whether `validateAssertion` re-checks that the credential belongs to the user passed to it. Under
   the driver the user comes from the credential so the two cannot disagree, but User Story 1
   scenario 8 has to prove that rather than reason about it. Phase 3.
6. **Settled. Only one screen confirms a password, and it steps aside.** A sweep of `app/` and
   `resources/views/` for `current_password` and for a password confirmation middleware found
   exactly one place: `PasswordController::update` and the field on `_change-password.blade.php`.
   There is no `password.confirm` route and no `RequirePassword` middleware anywhere, so nothing
   else would break for an account with no password.

   That screen is handled by not showing it: `_change-password.blade.php` yields to
   `_remove-password.blade.php` when `usesPasswordlessSignIn()` is true, and the way to set a
   password again is `settings.password.create`, which carries no `current_password` rule because
   there is nothing to confirm. The example application solves the same problem by replacing
   Fortify's password confirmation callback; this application had one screen rather than a
   framework-wide gate, so one branch was enough.

## Found during implementation, not planned for

- **The package's guest routes carry no `set.locale`.** Its routes file puts only the guard and the
  throttle middleware on the sign-in group, and `config('webauthn.middleware')` reaches the key
  management group alone. So a refusal returned from `webauthn.auth` is rendered in the default
  locale rather than the reader's. The screens themselves are Erica's own routes and are unaffected.
  This needs a decision in Phase 5, where the refusal copy is built, and the options are to add
  `set.locale` to the `web` group or to accept that these few strings follow the instance default.
- **`brick/math` had to come down from 0.18.0 to 0.17.2** for `web-token/jwt-library` to install.
  `laravel/framework` accepts `^0.14.2 || ... || ^0.18` and `ramsey/uuid` accepts `<=0.18`, so both
  are satisfied. Worth knowing because it is the only pre-existing package this feature moved.
