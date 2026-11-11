# Research: Sign in through OpenID Connect providers

Read from `vendor/socialiteproviders/openidconnect` 1.0.1 and from the application. Nothing was run.

## R1. Driver names: no prefix

**Decision**: Set `driver_prefix` to an empty string in `config/oidc.php` and name the connections
`microsoft`, `keycloak`, `auth0` and `okta`. The generic provider is the `services.openidconnect`
block, which the package registers as the `openidconnect` driver.

**Rationale**: The driver name is the enum value, the URL segment, the stored `provider` and the
`services.*` key at once. Without a prefix Microsoft keeps all four, so its redirect address
(`/sso/microsoft/callback`, registered at Microsoft by every operator), its two admin settings and
its environment variables do not change. URLs stay free of underscores, as the code style asks.

**Rejected**: the default `oidc_` prefix drafted in the working tree (`oidc_entra`), which renames
everything above. A `microsoft` connection inside `services.php`, which the package reads for one
driver only.

## R2. The package registers the drivers

**Decision**: Delete `AppServiceProvider::configureSingleSignOn()` and `SsoProviderEnum::driverClass()`.

**Rationale**: `OpenIDConnectServiceProvider::boot()` listens for `SocialiteWasCalled`, registers one
driver per connection, and copies each connection to `services.{driver}`. Microsoft was the only
case with a driver class, so the application's own registration has nothing left to do.

## R3. What "configured" means per provider

**Decision**: `SsoProviderEnum::requiredSettings()` lists the keys of `services.{value}` that must be
filled. `SsoProviderAvailability::isConfigured()` loops over it.

| Provider | Required beyond `client_id`, `client_secret`, `redirect` |
| --- | --- |
| GitHub, Google, Facebook, Microsoft | nothing (`tenant` defaults to `common`) |
| Keycloak | `server_url`, `realm` |
| Auth0, Okta | `domain` |
| Generic | `base_url` |

## R4. Instance settings point at `services.*`

**Decision**: Every new `InstanceSettingEnum` case has `services.{driver}.{key}` as its config key.

**Rationale**: The package copies connections into `services.*` in its `boot()`, and Laravel boots
discovered package providers before `AppServiceProvider`, whose `boot()` calls
`InstanceSettings::apply()`. The stored value therefore lands on the key Socialite reads, and
`apply()` stays safe to call again. Not run here: the first test of the feature proves the order.

**Rejected**: pointing at `oidc.connections.*`, which is read once and never again.

## R5. One refusal for everything that fails before the provider answers

**Decision**: Both `redirect()` methods (sign in and security settings) catch `Throwable` and refuse
with the copy `callback()` already uses.

**Rationale**: `redirect()` fetches the discovery document, so an unreachable provider, an insecure
address (`getBaseUrl()` throws unless https or loopback) and a wrong realm all throw there, and
today nothing catches them. The package enforces FR-007; the application only has to answer.

**Rejected**: checking the scheme in the availability helper, a second copy of the package's rule.

## R6. Microsoft through Entra

- The identifier is the `sub` claim, which Microsoft issues per application. The removed package
  stored the directory identifier. They differ, so an older connection never matches. Nothing is
  migrated (see the spec's assumptions); a development database is rebuilt or the provider is
  reconnected.
- The email comes from `preferred_username`, and only when it has the shape of an address. The
  package refuses the `email` claim on purpose, because a tenant administrator can set it freely.
- `tenant` restricts to one organisation (FR-003). `EntraIssuerValidator` handles `common`.

## R7. Smaller decisions

| Question | Decision |
| --- | --- |
| `monica.sso_installed` | The fixed list of all eight values when Socialite is present. The Microsoft class check goes. The key stays, since tests reach "not installed" through it |
| Generic name | `services.openidconnect.name`, falling back to "OpenID Connect", read by `brandName()` |
| Verification (FR-006) | The package's defaults: signature, issuer, audience, expiry, nonce, PKCE. No option is turned off |
| API (principle X) | Signing in through a provider is a browser redirect and has no API today. None is added |
| Relative `redirect` | `SocialiteProviders\Manager\Config` turns a path into a URL, as it did for Microsoft |
