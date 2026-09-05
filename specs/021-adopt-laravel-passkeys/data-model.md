# Data Model: Adopt laravel/passkeys

The entity is described in [spec.md](spec.md) under Key Entities. This records only what changes in
the database and in the model classes.

## Table

`webauthn_keys` is dropped with its migration, `2026_08_25_163611_add_webauthn.php`. The package's
migration is published with `vendor:publish --tag=passkeys-migrations` and **left exactly as it
ships**: constitution Principle IV exempts package migrations from the `up()`-only and no-
`constrained()` rules that bind migrations this project writes.

| `webauthn_keys` | `passkeys` | Note |
| --- | --- | --- |
| `credentialId` | `credential_id` | unique; unpadded base64url only, no dual-form matching |
| `credentialPublicKey`, `counter`, `trustPath`, `aaguid`, `transports`, `attestationType`, `type` | `credential` | one JSON column holding the serialised `CredentialRecord` |
| `used_at` | `last_used_at` | shipped by the package, so the `mergeFillable`/`mergeCasts` constructor goes |
| `name`, `user_id`, timestamps | same | |

FR-004 replaces rather than migrates, so no backfill exists and every enrolled credential is lost.
`users` is untouched; `password` is already nullable.

## Models

`App\Models\WebauthnKey` is deleted. `App\Models\Passkey extends Laravel\Passkeys\Passkey` replaces
it, registered with `Passkeys::usePasskeyModel()`, and carries three things:

- `resolveRouteBinding()`, scoping `{passkey}` to the signed-in user so a passkey somebody does not
  own is a 404 before the package's `abort_unless(..., 403)` is reached (research D3).
- `user()`, retyped to `App\Models\User` so static analysis keeps working.
- `hasBeenUsed()`, which the security screen already asks rather than comparing against null.

The parent supplies `credential_id`, `credential` and `last_used_at` casts, `$fillable`, and an
`authenticator` accessor that names the device from its AAGUID. Nothing is redeclared.

`App\Models\User` swaps `LaravelWebauthn`'s trait for `Laravel\Passkeys\PasskeyAuthenticatable` and
implements `Laravel\Passkeys\Contracts\PasskeyUser`. The trait supplies `passkeys()`,
`hasPasskeysEnabled()` and the WebAuthn handle. `getPasskeyDisplayName()` falls back to a `name`
attribute this application does not have, so it is overridden to return the person's full name.

`WebauthnKeyFactory` becomes `PasskeyFactory`, producing a `credential` JSON payload rather than the
seven separate columns.
