# Implementation Plan: Sign in with an outside provider

**Branch**: `2026-09-11-socialite-login` | **Date**: 2026-09-11 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/027-socialite-login/spec.md`

## Summary

Add Socialite, name the three providers the product supports in `SsoProviderEnum`, and read availability through `config/monica.php` so both the installed and the absent state are testable, the way `PasskeyAvailability` already reads the secure context. Three guest routes carry the sign in, two signed in routes carry connecting from the security screen. `users.sso_provider` exists already and gains `sso_id` beside it, because matching on the address alone cannot survive somebody changing it at the provider. Four actions hold the logic, and the controllers do what `MagicLinkController` already does with two factor. Decisions are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13.30.1.

**Primary Dependencies**: `laravel/socialite` added to `require`, approved on 2026-09-11. GitHub, Google and Facebook are drivers it ships. Microsoft needs `socialiteproviders/microsoft`, which goes to `require-dev` with a `suggest` entry, per the constitution's rule on integration packages.

**Storage**: `users.sso_provider` exists and is unused. One column added beside it, `sso_id`, and a unique index across the pair. The migration is edited rather than added, which the migrations skill allows until the end of 2026.

**Testing**: PHPUnit. Actions and the enum under `tests/Unit`, the five routes and the two screens under `tests/Feature/Controllers/App`. The driver is never called: Socialite is faked and handed what a provider would have returned. SC-004 is reached by overriding the config key, not by uninstalling anything.

**Target Platform**: the responsive web application. No API counterpart, per the spec's assumption and principle X.

**Project Type**: web application, server rendered Blade.

**Constraints**: `socialiteproviders/microsoft` is absent on an instance that never installs it, so nothing may name its class outside the one place config reads, and Microsoft must not be offered, routed to or named there. The provider returns its own subject identifier, which is what a user is matched on; the address is only ever used to decide whether an account has to be created.

**Scale/Scope**: one enum, one helper, four actions, two controllers, five routes, two screens, one column, one notification, two changelog entries.

## Constitution Check

*GATE: passes before Phase 0 and after Phase 1.*

| Principle | How this feature satisfies it |
| --- | --- |
| I Actions hold the business logic | `SignInWithProvider`, `CreateAccountFromProvider`, `ConnectProviderToUser`, `DisconnectProviderFromUser`. The controllers redirect, hand the callback over and apply the two factor branch `MagicLinkController` already has. |
| II Simplicity over abstraction | One enum names the providers, one helper answers whether one is available, and the four actions do not share a base class. The sign in screen gets one loop, not a block per provider. |
| III Tested before merged | Both package states, every provider refusal, the address match, the subject match after an address change, the two factor branch, the deactivated user, the last way in, and both screens. |
| IV Laravel conventions | Socialite is used as documented. Routes are one per line with camelCase names, the enum carries the `Enum` suffix, `env()` stays in `config/`, and the migration has only `up()`. |
| V Localized and safe | Provider names are proper nouns and are not translated. The copy around them is, through one `Continue with :provider` and the refusals. Disconnecting warns first. |
| VI Nothing changes under anybody's feet | An instance that installs nothing changes in nothing. A provider connected to an account is never changed by anybody but its owner, and a provider going away deactivates the way in rather than the account. |
| VIII Nothing disappears silently | An instance that removes a provider's credentials or its package leaves the users connected to it intact: they are told the provider is unavailable and keep whatever else signs them in. Nothing is deleted. |
| IX Accessible to everybody | The provider controls are ordinary links in a list, labelled with the provider name rather than its mark alone, so no information is carried by colour or by an icon. |
| X Everything goes through the API | The API authenticates with tokens and gains nothing. The redirect is transport of the sign in screen, which is what the spec assumes and what the magic link already does. |
| XI The written record is short | `plan.md`, `research.md`, `data-model.md`, `contracts/` and `quickstart.md`, each inside its cap, no fact in two of them. |
| Product invariants | One account, one user, no role. An account created through a provider is metered like any other, so the feature exists identically on both deployments. |

## Project Structure

### Documentation (this feature)

`plan.md`, `research.md`, `data-model.md`, `contracts/sign-in-with-a-provider.md`, `quickstart.md` and `checklists/requirements.md`, each inside its cap.

### Source Code (repository root)

```text
composer.json                                              # laravel/socialite in require, socialiteproviders/microsoft suggested
config/monica.php                                          # sso_installed, the drivers actually there
config/services.php, .env.example                          # one entry per provider, and the nine keys documented
database/migrations/0001_01_01_000000_create_users_table.php   # sso_id, unique across the pair
app/Enums/SsoProviderEnum.php                              # GitHub, Google, Microsoft, Facebook, cast on the model
app/Helpers/SsoProviderAvailability.php                    # installed, configured, the list to offer
app/Models/User.php                                        # signsInWith(), sso_id on the docblock and the fillable
app/Actions/AttemptLogin.php, UpdateUserPassword.php, DestroyUserPassword.php, RefusePasskeyLogin.php  # the exclusivity branch goes
app/Http/Controllers/Api/Auth/LoginController.php          # the same branch, on the API sign in
resources/views/app/settings/account/security/_change-password.blade.php  # shown to a connected user too
app/Actions/CreateUser.php                                 # already takes ssoProvider, gains ssoId
app/Actions/SignInWithProvider.php                         # match on the pair, refuse, stamp, notify, log
app/Actions/CreateAccountFromProvider.php                  # delegates to CreateAccount, address already verified
app/Actions/ConnectProviderToUser.php                      # from the security screen, signed in
app/Actions/DisconnectProviderFromUser.php                 # refuses the last way in
app/Http/Middleware/EnsureSsoProviderIsAvailable.php       # not found unless the provider is installed and configured
bootstrap/app.php                                          # alias sso, beside magic.link
app/Http/Controllers/App/Auth/SsoController.php            # redirect, callback, the two factor branch
app/Http/Controllers/App/Settings/Account/Security/SsoController.php   # connect, callback, disconnect
routes/auth.php                                            # two guest routes under a group
routes/web.php                                             # three routes beside the passkey ones
app/ViewModels/Auth/LoginViewModel.php                     # providersToOffer()
app/ViewModels/Settings/Account/Security/SecurityViewModel.php  # the connected provider, what may be offered
resources/views/app/auth/login.blade.php                   # the loop, beside the passkey block
resources/views/app/settings/account/security/_sso.blade.php    # and _sso-unavailable.blade.php
app/Enums/UserActionEnum.php                               # UserSsoConnected, UserSsoDisconnected
app/Notifications/SignInProviderDisconnected.php           # what PasswordRemoved does for a password
```

**Structure Decision**: nothing outside a folder that exists. `app/Helpers` holds `PasskeyAvailability` already, and the two controllers sit where the sign in and the security screen already put theirs.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Two dependencies added | Approved on 2026-09-11: `laravel/socialite` permanently, provider packages suggested | Writing the OAuth exchange by hand is more code, less reviewed, and the project already takes first party Laravel packages |
| Editing a shipped migration | The migrations skill asks for it until the end of 2026, and `sso_provider` is already there unused | A second migration for one column on a table nobody has in production yet |
| Two controllers named `SsoController` | One is guest and signs in, one is signed in and connects. They share no route, no middleware and no screen | One controller under `guest` and `auth` at once cannot express which method is which |
| Reversing tested behaviour in four existing actions | Today a connected provider silently disables a password and a passkey, which principle VI refuses. The rule settled on 2026-09-11 makes connecting additive | Leaving it exclusive makes connecting a provider destroy a password without asking, and makes every disconnect refusable |
