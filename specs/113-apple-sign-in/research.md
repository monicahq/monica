# Research: Sign in with Apple

Read from the application, from `vendor/socialiteproviders/manager`, and from the `master` branch of
`socialiteproviders/apple` (`Provider.php`, `AppleToken.php`, `composer.json`, its guide). The
package is not installed here and nothing was run.

## R1. Apple answers with a cross-site POST, so the callback is relayed

**Decision**: A second route, `POST sso/apple/callback`, outside the `web` group, answers 303 to the
existing `GET sso/apple/callback` with `code`, `state` and `error` in the query, and Apple's `user`
field encrypted. The existing callback decrypts `user` back into the request and runs unchanged.

**Rationale**: Apple requires `response_mode=form_post` whenever a name or an email is asked for.
The session cookie is `SameSite=lax`, so it does not travel with that POST. Without the session the
state check fails, and the callback cannot tell connecting from signing in or see who is signed in.
A GET that follows the POST is a top level navigation, which carries the cookie. The route sits
outside `web` because a session started there would replace the reader's session cookie and sign
them out. `user` is encrypted because it holds a name and an email address, which do not belong in
an address or an access log.

**Rejected**: `SESSION_SAME_SITE=none`, which loosens every cookie. The package's
`stateless()->cookieNonce()`, which verifies Apple but still leaves the callback without the
session. Dropping the name, which breaks acceptance scenario 2 of story 1.

## R2. The secret is built from the key, on every request

**Decision**: The operator supplies `client_id` with either `team_id`, `key_id` and `private_key`,
or a `client_secret` generated in advance. `SsoProviderEnum::requiredSettings()` asks for the
team and key identifiers when `private_key` is filled, and for the secret otherwise.

**Rationale**: With a private key the package signs a secret valid for one hour each time it needs
one (`AppleToken::generate()`), which satisfies FR-008 with no job and no stored token. The manager
throws when the `client_secret` key is missing, and accepts it empty. Without a key the package
sends the configured secret unchanged; with one it replaces it, so the key wins. A secret generated
in advance lasts six months at most, which is the operator's to renew.

**Rejected**: A stored secret renewed by a scheduled command. The helper package the Apple one suggests.

## R3. The private key is accepted on one line

**Decision**: Before the driver is built, the key is rewritten as a PEM block whatever its
shape: with or without its header, with line breaks, spaces or `\n` between its lines. A value that
is a path to a readable file is left alone, since the package reads it.

**Rationale**: A `.p8` file has several lines. An environment file and the admin space's secret
field both hold one. The package accepts the key's text, but only as valid PEM.

**Rejected**: A new multi line secret kind in the admin space. Environment only, which breaks FR-009.

## R4. Present or absent is one configuration key

**Decision**: `monica.sso_installed` lists every provider when Socialite is present, and leaves
`apple` out unless `SocialiteProviders\Apple\Provider` exists. The driver is registered from
`AppServiceProvider` only when `apple` is in that list.

**Rationale**: The key already decides what the screens, the `sso` middleware and
`InstanceSettingEnum::providerIsInstalled()` answer, and tests already reach "not installed" by
setting it. The admin space already prints its sentence for a setting whose provider is absent
(`InstanceSettings::describe()`), so FR-009 needs the mapping and no new copy. The package has no
service provider of its own; the manager, which fires `SocialiteWasCalled`, is already required by
the OpenID Connect package.

## R5. What "configured" means for Apple

`SsoProviderEnum::requiredSettings()` answers `client_id`, `team_id`, `key_id`, `private_key` and
`redirect`. It is the only provider that does not need `client_secret`.

## R6. Smaller decisions

| Question | Decision |
| --- | --- |
| Dependency | `require-dev` and `suggest`, as Telegram and Vonage. It brings `lcobucci/jwt`, `lcobucci/clock` and `firebase/php-jwt` |
| Verification (FR-007) | The package's: signature against Apple's published keys, issuer, audience, expiry, nonce, state. Nothing is turned off |
| No name after the first sign in | `SignInWithProvider` already falls back to the email address, and a returning person is matched by identifier, so nothing changes |
| Hidden email | The relay address arrives as `email`. Existing rules |
| Button | The existing provider control, which draws no logo for any provider. Apple's logo rules are not looked at (see Risks in the plan) |
| Storage | `provider_connections.provider` is a string. It gains the value `apple`; no migration |
| API (principle X) | None, as in 106: a browser redirect has no API |
| Version | `^6.1` (6.1.0, 2026-09-19), which matches what was read on `master` |
