# Data model: Sign in with an outside provider

One table changes, by one column. No table is added. See [research.md](research.md) for why the match is on the subject identifier rather than the address.

## `users`

`sso_provider` exists already, is fillable, and nothing writes it yet. `usesSingleSignOn()` and `usesPasswordlessSignIn()` already read it, and `DestroyUserPassword` already refuses a user who signs in this way.

| Column | Type | Meaning |
| --- | --- | --- |
| `sso_provider` | string (255), nullable | the provider the user signs in with, one of `SsoProviderEnum`. Exists. |
| `sso_id` | string (255), nullable | the identifier the provider gives this person and promises not to reuse. New. |

Added to the existing migration, which the migrations skill allows until the end of 2026, with a comment on the column and a unique index across `sso_provider` and `sso_id`. The pair is unique, not `sso_id` alone: two providers may hand out the same string and mean two different people.

Both are null together or set together. A user holds at most one provider, which is the mono-user shape and what the single column already assumes.

### Rules

- A user with `sso_provider` set may hold a password, a passkey, both or neither. Connecting never clears `password`, and an account created through a provider or a passkey simply never had one. `usesSingleSignOn()` answers whether a provider is connected and nothing more, which is a change: four existing actions read it today as though it meant the user has no other way in.
- Clearing the pair is disconnecting, and it is refused when the user has no password and no passkey. Setting the pair is connecting, and it touches nothing else on the row.
- A provider whose credentials or package are gone leaves the pair alone. The user is told the provider is unavailable and signs in with whatever else they have.

## `SsoProviderEnum`

A backed string enum in `app/Enums`, one case per provider the product supports, backed by the Socialite driver name in lower case and carrying the label shown on the control. `sso_provider` is cast to it, so the column accepts nothing the product does not support. A provider renamed at its own company changes a label and not a row.

| Case | Driver | Package | The address it returns |
| --- | --- | --- | --- |
| `GitHub` | `github`, core | none, Socialite ships it | primary and verified, which the driver picks itself under the `user:email` scope it already asks for |
| `Google` | `google`, core | none, Socialite ships it | present and verified. It is also the value every existing fixture holds |
| `Microsoft` | `microsoft` | `socialiteproviders/microsoft`, suggested | `mail`, falling back to `userPrincipalName` |
| `Facebook` | `facebook`, core | none, Socialite ships it | present unless the person registered by telephone or declined the permission, in which case the sign in is refused |

Labels are proper nouns and are not translated. The copy around them is: one `Continue with :provider` carries every control.

## What the provider returns

Read at the callback, none of it stored beyond what the table names above.

| Value | Used for |
| --- | --- |
| identifier | matching an existing user, and written to `sso_id` when an account is created |
| email address | deciding whether an account has to be created, and seeding it when one is |
| name | seeding the first and last name of a created account |
| token | nothing. Discarded, per FR-003 |
