# Browser exchange contract: WebAuthn Authentication

**Feature**: `002-webauthn-auth` | **Date**: 2026-08-25

What the browser and the server send each other. This is not a public API. The endpoints are the
package's routes, the credential handling is `@simplewebauthn/browser`, and what is left is the thin
Alpine component that joins them. This file exists so that component and the screens agree.

## The library

**`@simplewebauthn/browser` ^13.1.0**, which is what
[`asbiin/laravel-webauthn-example`](https://github.com/asbiin/laravel-webauthn-example/blob/main/package.json)
uses.

```js
import { startRegistration, startAuthentication, browserSupportsWebAuthn } from '@simplewebauthn/browser';
```

Installed with `bun add @simplewebauthn/browser`. It goes in `dependencies` rather than
`devDependencies`, which is where the example puts it, because this project keeps runtime libraries
(`alpinejs`, `@hotwired/turbo`, `instant.page`) in `dependencies` and build tooling in
`devDependencies`. Following the local convention matters more here than matching the example's
file.

This is a new frontend dependency, which the constitution says needs approval, and it has it. It is
not the single page framework the constitution rules out: it is a credential handling library, and
the alternative is hand written base64url conversion, which is the part of a WebAuthn integration
most likely to be subtly wrong.

The package's own bundled `resources/js/webauthn.js` is **not** used. An earlier version of this
contract specified it. The library is what the reference application actually reaches for, its
option and response shapes match what the package serialises, and it is maintained against the
specification rather than alongside it.

## Encoding, which the library owns

At version 13 the library takes and returns the **JSON form** of the WebAuthn structures:
`PublicKeyCredentialCreationOptionsJSON`, `PublicKeyCredentialRequestOptionsJSON`,
`RegistrationResponseJSON` and `AuthenticationResponseJSON`. Every binary field in those is a
base64url string.

So there is no conversion in this project at all. No `ArrayBuffer`, no `btoa`, no padding fix, no
`-` to `+` substitution. The server emits the options as the package serialises them, the component
hands that object straight to the library as `optionsJSON`, and the object the library returns is
already JSON and posts straight back.

The one thing to verify rather than assume: that the package's serialised options are exactly the
JSON form the library expects, field for field. They should be, since both follow the same
specification, but a mismatch here is silent and shows up as an unusable prompt. It is open item 4
in [research.md](../research.md) and a task of its own, not a hope.

## Registering a passkey

### 1. Ask for options

`POST passkeys/keys/options`, with the CSRF token. Answered by `PasskeyRegisterViewResponse`, which
returns `{ publicKey: { ... } }` for a JSON request, as the example's `RegisterKey.vue` expects.

`excludeCredentials` holds the credentials this account already has, which is what makes the browser
refuse a duplicate at the device level before a request is sent (FR-004). The server still refuses
one, because a browser is not a check.

Expected values, from the configuration research settles: under `authenticatorSelection`,
`residentKey` and `userVerification` both `required`, and `authenticatorAttachment` absent so both
platform and cross platform authenticators are offered (FR-007). `attestation` is `none` (FR-008).

### 2. Call the authenticator

```js
const credential = await startRegistration({ optionsJSON: publicKey });
```

Three outcomes the component tells apart on `error.name`, because FR-002 requires the person to be
told which happened:

| `error.name` | Means | Component does |
|---|---|---|
| no error | success | submits step 3 |
| `NotAllowedError` | cancelled, dismissed or timed out | announces that no passkey was added, submits nothing |
| `InvalidStateError` | this authenticator already holds a credential for this account | announces that this device already has a passkey here |

Anything else is announced as a failure to add the passkey. Nothing is submitted in any failure
case, so no half registration can exist.

### 3. Send it back

`POST passkeys/keys`, with the `RegistrationResponseJSON` the library returned, plus a `name`.

The name is collected before the prompt opens, as the example does, so the person is not asked for
it while a device dialog is in front of them. It is chosen from what the authenticator reports about
itself, falling back to a plain default the person can change (FR-005). It reaches
`Webauthn::validateAttestation($user, $credentials, $keyName)` as the third argument, which is why
the caller decides it and not the package.

On success the package fires `WebauthnRegister`, and `HandlePasskeyRegistration` writes the activity
entry and sends `PasskeyAdded`.

## Signing in with a passkey

### 1. Ask for a challenge

`POST passkeys/auth/options`, with **no email field**. `AuthenticateController::create()` sees
`Webauthn::userless()` is true and passes a null user to `PrepareAssertionData`, so
`RequestOptionsFactory` returns options whose `allowCredentials` is empty.

That empty list is what makes this a discoverable credential sign in and what lets the person type
nothing (FR-009). It also means the response reveals nothing about who has an account here, which
SC-004 requires.

`userVerification` is `required`, which is what makes the claim in FR-020 true rather than usually
true.

### 2. Call the authenticator

```js
const assertion = await startAuthentication({ optionsJSON: publicKey, useBrowserAutofill: false });
```

`useBrowserAutofill` is false. True is the conditional mediation flow, where the browser offers
passkeys inside a username field, and there is no username field to attach it to on a screen where
nothing is typed. The example passes it as a prop because its login screen has both shapes; this one
has only the modal flow.

The cross device flow of User Story 4 needs nothing here: the platform runs it inside this same
call. An abandoned or dismissed prompt gives `NotAllowedError`, and the component returns the screen
to its starting state and says nothing was signed in (Story 4 scenario 2).

### 3. Send it back

`POST passkeys/auth`, with the `AuthenticationResponseJSON` the library returned, plus `remember`
when the person asked for it.

That object carries `id`, `rawId`, `type` and `response`, which are the four keys
`EloquentWebAuthnProvider::isSignedChallenge()` tests for. It reaches `Auth::attempt()` unchanged,
and dropping or renaming any of the four would make the provider treat it as an email and password
pair instead. The library produces exactly those four, which is another reason not to hand roll the
transformation.

`response.userHandle` is present for a discoverable credential. Nothing in this application reads
it. The user comes from `retrieveByCredentials`, which looks the credential id up in `webauthn_keys`
and returns that row's owner, because that row is what proves the credential was registered here and
against whom.

Response `302` on success. Any refusal carries the message [routes.md](routes.md) specifies, and the
component shows it without interpreting it.

## Detecting that passkeys cannot be used

Both sides check, and the reasons differ.

The browser, before showing any passkey control, calls `browserSupportsWebAuthn()`. That returns a
boolean and not a reason, so the reason is worked out the way the example's
`resources/js/methods.js` does it:

| Condition | Copy says |
|---|---|
| `window.isSecureContext` is false, on a host that is not `localhost` or `127.0.0.1` | the connection is not secure, and asks for https |
| anything else | the browser cannot do passkeys at all |

Both sentences are this project's copy, translated through `__()` like every other string rather
than taken from the example's English. Failing either check, the control is not rendered and the
explanation takes its place (FR-038, Story 3 scenario 2).

The server checks too, when rendering the sign in screen and the security screen, and again when a
password removal is requested:

- `config('webauthn.enable')` is true, and
- the request is secure, or the host is localhost (FR-032).

The server check exists because FR-038 asks for the reason on a server rendered screen, and because
FR-040 must refuse a password removal for real rather than by hiding a button. A hidden control is
not a refusal. Production forces https anyway, following the example's `AppServiceProvider`, so on a
correctly deployed instance the browser check is the one that fires.

## What the Alpine component does, and nothing more

`resources/js/passkey.js` has four jobs:

1. Ask the options endpoint, with the CSRF token.
2. Hand `response.data.publicKey` to `startRegistration` or `startAuthentication`.
3. Post what comes back to the matching route.
4. Write every outcome into a live region, so a cancelled prompt is announced and not only rendered
   (FR-044).

It converts nothing, it holds no credential state between requests, and it interprets no server
message beyond displaying it.

## What never crosses

No public key, no `credentialPublicKey`, no `trustPath`, no `aaguid` and no counter is ever sent to
a browser (FR-042). The package model's `$visible` list is what enforces it, and this project's
subclass merges `used_at` into that list rather than redeclaring it, so the protocol fields stay
hidden by inheritance rather than by our remembering to hide them.

The options objects carry challenges and credential ids, which are not secrets, and nothing else.
There is no endpoint that exports a passkey and none that describes one beyond the name, the date it
was added and the date it was last used.
