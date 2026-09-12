# Research: Sign in with an outside provider

Eleven decisions. Everything the plan, the data model and the contract assume is settled here and nowhere else.

## Socialite is required, a provider's own package is suggested

- **Decision**: `laravel/socialite` goes in `require`, approved on 2026-09-11. A package serving one provider and nothing else, `socialiteproviders/microsoft` today, goes in `require-dev` with a matching `suggest` entry. `config/monica.php` exposes `sso_installed`, the list of providers whose driver is actually there, built from a `class_exists` per packaged provider evaluated in the config file.
- **Rationale**: Socialite is the mechanism rather than an integration with any one service, so every instance carries it and the screens can name it freely. A provider package is the integration, and the constitution asks for those to be suggested, kept in the development dependencies so the working path stays covered, and read through configuration rather than tested where they are used. CI installs development dependencies, so the present state is the default and the absent state is reached by overriding one config key.
- **Alternatives**: suggesting Socialite too, which buys nothing once the enum and the screens reference it anyway; a bare `class_exists` where it is used, which no test can flip.

## A user is matched on the provider's subject identifier

- **Decision**: `users` gains `sso_id`, and a sign in matches on `sso_provider` and `sso_id` together, unique across the pair. The address decides one thing only, whether an account has to be created.
- **Rationale**: SC-002 refuses a sign in reaching an account that is not the signer's when the address changed at the provider since last time. Matching on the address cannot satisfy that: the person keeps their account at the provider and loses it here, or worse, lands on somebody else's. The subject identifier is the one value a provider promises not to reuse.
- **Alternatives**: matching on the address, which fails the edge case the spec names; a separate table for connections, which the mono-user invariant makes pointless since a user signs in through at most one provider.

## Four providers, named in an enum and cast on the model

- **Decision**: `SsoProviderEnum` names GitHub, Google, Microsoft and Facebook, backed by the Socialite driver name in lower case, and `sso_provider` is cast to it on the model. GitHub, Google and Facebook are drivers Socialite ships. Microsoft is `socialiteproviders/microsoft`. A fifth provider means a case, and a package when the driver is not in core.
- **Rationale**: the clarification asks for a fixed set the product supports, narrowed to what is installed. Splitting across core and packaged drivers is what makes the availability question worth asking at all: GitHub, Google and Facebook are there whenever the product is, Microsoft only when somebody installed it. Google was added on 2026-09-11, which also settles the cast: it is the only value the existing fixtures hold, so casting now costs one assertion rather than twelve.
- **Alternatives**: letting the operator name any driver, which puts untranslated copy on the sign in screen and offers providers nothing tests; a generic entry for any standards based provider, which is a second code path and was not what was asked for.

## Instagram is not among them, and the reason is permanent

- **Decision**: Instagram is dropped. It is recorded here so nobody adds it back without reading this.
- **Rationale**: `socialiteproviders/instagram` requests `account_type`, `id`, `username`, `media_count` and `profile_picture_url` under the `instagram_business_basic` scope, and the word `email` does not appear anywhere in the provider. Meta's Graph API exposes no address. FR-004 refuses a sign in that arrives without a verified one and FR-005 seeds a created account from it, so an Instagram control would refuse every attempt it ever received.
- **Alternatives**: connecting only, never signing up, which keeps Instagram at the cost of refining FR-004 so an address is needed only when an account has to be created, and was considered and declined on 2026-09-11; creating accounts with no address, which the user record refuses since the address is the sign in identity.

## Connecting adds a way in, it never replaces one

- **Decision**: an account created through a provider or a passkey has no password and never had one. An account that has a password keeps it when a provider is connected, and loses it only when its owner removes it, which `DestroyUserPassword` already guards by refusing to leave nobody a way in.
- **Rationale**: the rule was settled on 2026-09-11. It also means the running code is wrong today: `AttemptLogin` refuses a password from a user holding a provider, `UpdateUserPassword` refuses to change one, `RefusePasskeyLogin` refuses a passkey, and the API sign in refuses the same user. All four read `usesSingleSignOn()` as though a provider were the only way in. This feature reverses that, so `usesSingleSignOn()` stops deciding whether somebody may use a password and answers only whether a provider is connected.
- **Alternatives**: keeping the exclusive model, which matches the code today but makes connecting a provider silently destroy a password, which principle VI refuses; requiring a password to be set in the same gesture as disconnecting, which solves the same problem with a screen nobody asked for.

## Availability is two conditions

- **Decision**: `SsoProviderAvailability` answers whether the package is installed and which providers have credentials. A provider is offered when both hold. It is a helper beside `PasskeyAvailability`, which already answers this shape of question for the sign in screen.
- **Rationale**: FR-001 and FR-007 are one question asked from two screens and one middleware. Putting it in one place keeps the answer identical in all three, and the helper is what the codebase already reaches for.
- **Alternatives**: reading the config in each view model, which is the same read written twice.

## Refusing a matched address is not a leak

- **Decision**: a provider sign in whose address belongs to a user who signs in another way is refused with a message that says the address is already in use and to connect the provider from inside the account.
- **Rationale**: FR-012 keeps the product silent about whether an address has an account. This case is the exception, and deliberately: the person has just proved to the provider that the address is theirs, which is a higher bar than the registration screen applies before telling anybody the same thing. Staying silent here would leave somebody who owns both no way to understand why nothing happened. The silence still holds on every other failure, where nothing has been proved.
- **Alternatives**: a generic failure, which is unexplainable to the person it happens to.

## Nothing the provider issues is kept

- **Decision**: the token Socialite returns is read for the subject identifier, the address and the name, then discarded. No token, refresh token or secret belonging to a person is written anywhere.
- **Rationale**: FR-003, and the constitution's rule that the account with the outside service belongs to the instance. The product never calls the provider on somebody's behalf, so there is nothing a stored token would serve.
- **Alternatives**: keeping the token for a later integration, which stores a credential for a feature that does not exist.

## The callback carries session state

- **Decision**: the default stateful flow, on the guest routes and on the signed in ones, with Socialite's state parameter doing what it does by default.
- **Rationale**: both screens are ordinary web sessions, and the state parameter is what refuses a callback nobody asked for, which is one of the spec's edge cases.
- **Alternatives**: the stateless flow, which drops that protection for no gain here.

## Two factor and the deactivated user are already solved

- **Decision**: the controller does what `MagicLinkController` does: put `twoFactor.user.id` in the session and redirect to the challenge when the user has two factor, otherwise sign them in. The action refuses an inactive user the way `ConsumeMagicLink` does.
- **Rationale**: FR-009 asks for the conditions that already exist, and copying the branch is four lines against a shared helper nothing else would use.
- **Alternatives**: extracting the branch, which principle II refuses for this little.

## Disconnecting is told, connecting is logged

- **Decision**: `UserSsoConnected` and `UserSsoDisconnected` join `UserActionEnum`, and disconnecting sends `SignInProviderDisconnected`, beside the `PasswordRemoved` the same gesture already sends for a password.
- **Rationale**: losing a way in is what the existing notification exists to announce, and a way in appearing on an account is what the changelog exists to record.
- **Alternatives**: no email, which leaves somebody whose provider was disconnected to find out at the next sign in.
