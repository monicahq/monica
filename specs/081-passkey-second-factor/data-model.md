# Data model: Security keys as a second factor

## `security_keys` (new)

```text
id              bigint, primary key
user_id         foreignIdFor(User::class), constrained, cascadeOnDelete, indexed
name            string (255)        what its owner calls it; defaults to the authenticator's name or "Security key"
credential_id   string, unique      base64url, unpadded, as the package writes it in passkeys
credential      json                the serialised WebAuthn credential record, rewritten at every use (sign counter)
last_used_at    timestamp, nullable null until it first answers a challenge
created_at      timestamp
updated_at      timestamp
```

Same shape as `passkeys`, so the package's serialiser reads both. Migration with `up()` only.

## `App\Models\SecurityKey`

- `#[Fillable]` name, credential_id, credential, last_used_at; casts `credential` to array and `last_used_at` to `CarbonImmutable`.
- `user(): BelongsTo<User, $this>`; `hasBeenUsed(): bool`, as `Passkey`.
- `resolveRouteBinding()` finds only a row whose `user_id` is `Auth::id()`, as `Passkey` does, so another person's key answers 404. Listed in `RouteProtectionTest::$modelsScopedByTheirController`.
- No soft delete: removing a key is final and warned (spec FR-004), like a passkey.

## `App\Models\User` additions

| Member | Answers |
| --- | --- |
| `securityKeys(): HasMany<SecurityKey, $this>` | the person's keys, oldest first |
| `usesAuthenticatorApp(): bool` | `two_factor_confirmed_at !== null`, the old meaning |
| `usesTwoFactorAuthentication(): bool` | `usesAuthenticatorApp()` or at least one security key (spec FR-007) |

`users` keeps its columns. `two_factor_recovery_codes` now exists while either factor does.

## States of two-factor authentication

```text
off ──confirm app / add first key──▶ on   (codes issued)
on  ──add the other factor─────────▶ on   (codes kept)
on  ──remove app or a key, one left▶ on   (codes kept)
on  ──remove the last factor───────▶ off  (codes cleared)
```

A passkey never moves this state: it answers the challenge only while the state is on.

## Journal and emails

| Event | `UserActionEnum` | Email |
| --- | --- | --- |
| Key added | `SecurityKeyAdded` (:name) | `SecurityKeyAdded` |
| Key renamed | `SecurityKeyRenamed` (:name) | none, as for a passkey |
| Key removed | `SecurityKeyRemoved` (:name) | `SecurityKeyRemoved` |
| Key answers the challenge | `SecurityKeyUsed` (:name) | `NewLoginDetected`, sent once by the challenge |
| Passkey answers the challenge | `PasskeyConfirmed` (:name), existing | `NewLoginDetected`, sent once by the challenge |
| The first factor arrives, or the last one goes | `TwoFactorEnabled` / `TwoFactorDisabled`, existing, now also written by a key | none |
| The app arrives or goes while a key stays | `AuthenticatorAppAdded` / `AuthenticatorAppRemoved` | none |
