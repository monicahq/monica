# Data model: WebAuthn Authentication

**Feature**: `002-webauthn-auth` | **Date**: 2026-08-24

Two tables are involved and neither is created by this feature. `webauthn_keys` comes from the
package migration, and this feature adds one column to it. `users` already has every column needed.

## `webauthn_keys`

Created by `asbiin/laravel-webauthn`, migration `2019_03_29_163611_add_webauthn.php`. Left exactly
as it ships, including its `down()`, which the constitution permits because this project did not
write it.

| Column | Type | Null | Notes |
|---|---|---|---|
| `id` | bigint unsigned, auto increment | no | primary key |
| `user_id` | bigint unsigned | no | foreign to `users.id`, `cascadeOnDelete` |
| `name` | varchar(255), default `'key'` | no | what the person sees and renames |
| `credentialId` | mediumtext | no | indexed, 255 char prefix on MySQL, full elsewhere. Cast to Base64 by the model |
| `type` | varchar(255) | no | credential type as the authenticator reported it |
| `transports` | text | no | cast to array |
| `attestationType` | varchar(255) | no | `'none'`, since attestation is not conveyed |
| `trustPath` | text | no | cast to `TrustPath` |
| `aaguid` | text | no | cast to Uuid |
| `credentialPublicKey` | text | no | cast to Base64. Never leaves the instance |
| `counter` | bigint unsigned | no | signature counter, advanced by the package on each assertion |
| `created_at` | timestamp | no | when the passkey was added |
| `updated_at` | timestamp | no | |

### Added by this feature

Migration `2026_08_25_000000_add_used_at_to_webauthn_keys_table.php`, `up()` only.

| Column | Type | Null | Notes |
|---|---|---|---|
| `used_at` | timestamp | yes | when this passkey last signed somebody in. Null means never used, which the screen states rather than leaving blank (FR-006) |

The name is `used_at`, taken from `asbiin/laravel-webauthn-example`, which adds the same column in
`2025_05_05_101750_webauthn_used_at.php`. Two deliberate differences from the example's version: no
`down()`, because the constitution forbids one in a migration this project writes, and no
`->after('counter')`, because it is a MySQL only hint and this suite runs on three drivers, where it
is inert.

The column is nullable and has no default on purpose. A passkey that has never been used is a
distinct state the interface shows, and defaulting it to the creation time would erase it.

It is written by `RecordPasskeyUse`, a listener on the `web-auth/webauthn-lib` event
`AuthenticatorAssertionResponseValidationSucceededEvent`, which is where the example writes it too.
That event fires inside validation and carries the user handle and the credential id, so the column
is stamped wherever an assertion is verified rather than only on the path this feature happens to
build.

## `App\Models\WebauthnKey`

Extends `LaravelWebauthn\Models\WebauthnKey`, registered through `config('webauthn.model')`. Table
`webauthn_keys`, inherited.

The class is `WebauthnKey` and not `Passkey`, following the example. It sits on a table called
`webauthn_keys`, so the names agree, and the earlier mismatch between a `Passkey` class and a
`webauthn_keys` table is gone. Every string a person reads still says passkey: that is copy, not a
class name.

The example's constructor is the pattern to copy, and merging rather than redeclaring is the point
of it. The parent sets `$visible`, so a plain `protected $visible = [...]` here would drop the
protocol fields the package needs:

```php
public function __construct(array $attributes = [])
{
    parent::__construct($attributes);

    $this->mergeFillable(['used_at']);
    $this->setVisible(array_merge($this->getVisible(), ['used_at']));
    $this->mergeCasts(['used_at' => 'datetime']);
}
```

This model is on the sign in path, not only the settings screen.
`EloquentWebAuthnProvider::retrieveByCredentials` queries `Webauthn::model()` by `credentialId`, so
whatever `config('webauthn.model')` points at is the class that resolves a credential to its owner.
Pointing it at this class is what puts our casts and relations in play there.

Adds, beyond the constructor above:

- `user(): BelongsTo<User, $this>`, as the example has it, so the codebase reads the owner off the
  model the way it does everywhere else.
- `hasBeenUsed(): bool`, which is `used_at !== null`. The screen asks this rather than comparing
  against null in Blade.
- A docblock listing every property, as every model in this codebase carries.

Does not use the package's `WebauthnAuthenticatable` trait, which provides one method,
`webauthnKeys()`. The example does not use it either. The driver does not require it, and the
relation is declared on `User` as `passkeys()` instead, in the vocabulary the rest of the product
uses.

Does not add: soft deletes. A revoked passkey is deleted (see Principle VIII in the plan, where the
narrow reading is justified). Does not override `publicKeyCredentialSource`, `$casts` for the
protocol columns, or `$visible`. Those are the package's and stay its business.

### Scoping

There is no `account_id`. Compartmentalisation runs `account` then `user`, and `users.account_id` is
unique, so one user is one account and `user_id` is the whole of the scope. Every read on the
settings screen is `$user->passkeys()`, never `WebauthnKey::find()`, so a passkey belonging to
another account is not found rather than found and refused (FR-036).

The sign in path is the one exception, and it is not a hole. The provider looks a credential up
across every row, because at that moment nobody is signed in and there is no scope to apply. What it
returns is the credential's own owner, so a credential can only ever open the account it was
registered against (User Story 1 scenario 8).

## `users`, and what changes about it

No columns are added. Two existing columns take on a wider meaning.

| Column | Was | Becomes |
|---|---|---|
| `password` | nullable, and null meant the user signs in through SSO | nullable, and null means either SSO or an account that has removed its password in favour of passkeys |
| `password_changed_at` | null alongside a null password | same, and set again when a password is set after having been removed |

The column comment on `password` still says "null when the user signs in through SSO", which is now
incomplete. Changing a column comment needs a migration that changes nothing else, so the comment is
left and the widened meaning is stated in the `User` model docblock instead, where a reader of the
code will actually meet it.

### Added to `App\Models\User`

- `passkeys(): HasMany<WebauthnKey, $this>`, ordered by the caller rather than the relation.
- `hasPasskeys(): bool`.
- `usesPasswordlessSignIn(): bool`, which is `password === null && sso_provider === null`. This is
  the state that distinguishes a passkey only account from an SSO one, and both from an ordinary
  account, and it is asked by name rather than reconstructed at each call site.

`usesSingleSignOn()` keeps its current meaning and is unchanged.

## In memory, not persisted

Two things the specification names as entities are deliberately not rows.

**Registration challenge** and **sign in challenge**. Both are one time values with a short life,
held by the package in the cache between the options request and the verification request, keyed to
the session. They are not modelled here, they are not queryable, and nothing in this feature reads
them directly. `config('webauthn.challenge_length')` stays at 32 and `config('webauthn.timeout')` is
set explicitly so the window is short (FR-003, FR-010).

**Instance address**. The relying party id, derived from the application URL. Not a setting and not
a row. It appears in the data model only to explain FR-041: it is baked into every credential at
creation and checked at every assertion, so a credential created under a former address fails
verification. The failure is caught and turned into the explanation FR-041 asks for, rather than
being reported as a wrong credential.

## State, and what moves between states

A passkey has two states and one transition.

```
never used  ->  (first successful assertion)  ->  used
```

`used_at` null becomes a timestamp, and never returns to null. Renaming does not change state.
Removal is not a state, it is the end of the row.

An account has three sign in states, and this feature adds the third.

| State | `password` | `sso_provider` | passkeys |
|---|---|---|---|
| Password account | set | null | none or some |
| SSO account | null | set | none. Registration is refused for an SSO user, as password changes already are |
| Passwordless account | null | null | at least one, enforced when the password is removed |

Moving from password account to passwordless requires at least one passkey (FR-024) and a working
passkey environment (FR-040). Moving back is setting a password again (FR-026), and is always
available. A passwordless account that removes its last passkey stays passwordless with no passkeys,
which is a reachable state and the one FR-033 warns about before it happens: the emailed link is
what still reaches it.
