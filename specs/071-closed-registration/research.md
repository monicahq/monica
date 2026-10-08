# Research: Closing Registration on an Instance

Each decision follows a mechanism the codebase already has. The spec has no open question left.

## D1. The setting is a configuration key, overridable as an instance setting

- **Decision**: `monica.registration_enabled`, read from `MONICA_REGISTRATION_ENABLED`, default `true`,
  plus a `RegistrationEnabled` case in `InstanceSettingEnum` (group `SigningIn`, kind `Boolean`).
- **Rationale**: `magic_link_enabled` is the same shape. `InstanceSettings::apply()` already writes
  every catalogued override into `config()`, and `UpdateInstanceSetting` already writes the
  administrator log, so FR-002 and FR-003 come from the catalogue entry alone.
- **Naming**: `enabled`, not `open`, because `magic_link_enabled` and `screenshot_link.enabled` already
  name a switch that way. The screens still say "open" and "closed" to the reader.
- **Rejected**: a Pennant flag (`.claude/rules/flags.md` reserves flags for unreleased work, never an
  operator choice); a table of its own (the instance settings table exists).

## D2. The routes answer not found through a middleware

- **Decision**: `EnsureRegistrationIsEnabled`, aliased `registration`, on `auth.register.new`,
  `auth.register.create`, `auth.registerPasskey.create` and `api.v1.register`. Not on
  `auth.registerPasskey.new`: it runs after the account exists, inside the `auth` group.
- **Rationale**: `EnsureMagicLinkIsEnabled` does exactly this for the link by email flow.
- **Rejected**: a refusal inside `CreateAccount`. Every caller of it sits behind the middleware, and
  the action would need a refusal type that no reader ever sees.

## D3. Provider sign in refuses inside `SignInWithProvider`

- **Decision**: a `refuseWhenRegistrationIsDisabled()` step in `resolve()`, after a connected account
  is found and before the email checks, throwing the `ValidationException` the other refusals throw.
- **Rationale**: the callback route serves existing people too, so it cannot answer not found. The
  controller already turns that exception into an error on the sign in screen.
- **Copy**: "This instance does not accept new accounts."

## D4. The screens read the key through their view model or layout class

| Screen | Source | When disabled |
| --- | --- | --- |
| Sign in | `LoginViewModel::registrationIsEnabled()` | the "Create an account" footnote is absent |
| Marketplace header | `MarketplaceLayout::registrationIsEnabled()` | the button is absent, "Sign in" stays |
| Marketplace page | `MarketplaceViewModel::registrationIsEnabled()` | the visitor branch shows nothing |
| Documentation home | `DocsViewModel::registrationIsEnabled()` | "Get started with Monica" leads to the sign in screen |

- **Rationale**: `LoginViewModel::magicLinkIsEnabled()` is the precedent. The offer is absent rather
  than disabled because a capability that is off does not exist (`.claude/rules/flags.md`); the
  `aria-disabled` rule in `.claude/rules/policies.md` covers policy abilities, which this is not.
- **Rejected**: a shared helper for four one line reads (`CLAUDE.md`, no helper for short repeats).

## D5. The warning asks when the switch goes on

- **Decision**: `warning()` returns "Anybody who reaches this instance can create an account until it
  is switched off again." and `warnsWhen()` returns a new mode, `switchedOn`, which the Alpine
  `takesSomethingAway()` on the Configuration screen learns to read.
- **Rationale**: the screen's confirm and save again flow exists; only the direction is new.

## D6. The API reference says the register endpoint can be disabled

- **Decision**: the `register-store` body in `resources/docs/api/02-authentication.php` says it answers
  404 while registration is disabled.
- **Rejected**: reporting the switch on `GET /api/v1/status` (removed by decision on 2026-10-07).

## D7. Documents that change with it

- `.env.example` lists `MONICA_REGISTRATION_ENABLED=true` beside the other `MONICA_` settings.
- `docs/portal/ROADMAP.md`, `hosting.sign-in-options`, says "registration cannot be closed". That
  becomes the switch and its environment variable.
- `docs/FEATURES.md` gets an entry (`feature-documentator` skill).
