# Phase 1 Data Model: Vault endpoints

**Feature**: [spec.md](spec.md) | **Date**: 2026-09-01

No schema change. No migration, no new model, no new column, no new factory state. The tables below
already exist and are described here only to say which columns leave the application and which do not.

## Vault

`app/Models/Vault.php`, table `vaults`. Soft deleting. Route key `uuid`.

| Column | Type | In the API | As |
| --- | --- | --- | --- |
| `id` | int | **no** | The primary key stays inside. Handing it out would say how many vaults exist and let one address be guessed from the next. |
| `uuid` | string(36) | yes | `id`, as a string. The only address the API has. |
| `account_id` | int | **no** | Always the caller's account. Saying it adds nothing and names an internal key. |
| `name` | string(255) | yes | `attributes.name` |
| `description` | text, nullable | yes | `attributes.description`, `null` when the vault has none |
| `colour` | string(7) | yes | `attributes.colour`, one of eight, lowercase |
| `position` | int | yes | `attributes.position`. The order the account arranged, which is the order the list answers in. |
| `is_default` | bool | yes | `attributes.is_default` |
| `contact_count` | int | yes | `attributes.contact_count` |
| `last_visited_at` | datetime, nullable | **no** | When a *person* last opened the vault in a browser. It says nothing to a caller and describes the other surface. |
| `created_at` | datetime | yes | `attributes.created_at`, a Unix integer |
| `updated_at` | datetime, nullable | yes | `attributes.updated_at`, a Unix integer or `null` |
| `deleted_at` | datetime, nullable | **no** | A deleted vault is outside the default scope, so it is never listed and never found. There is nothing to report. |

### Validation, and where each rule comes from

Both write endpoints validate in the controller, inline, which is how this application validates
everywhere but one place.

| Field | Create | Change | Rule |
| --- | --- | --- | --- |
| `name` | required | required | `['required', 'string', 'max:255']` |
| `description` | optional | must be present | create `['nullable', 'string', 'max:65535']`, change `['present', 'nullable', 'string', 'max:65535']` |
| `colour` | optional | required | create `['nullable', 'string', Rule::in(VaultColourEnum::values())]`, change the same with `required` |
| `is_default` | optional | optional | `['nullable', 'boolean']` |
| `template_version_id` | optional | optional | `['nullable', 'integer', Rule::exists('templates', 'published_version_id')->where('account_id', ...)->where('applies_to', TemplateAppliesToEnum::Contact->value)->where('is_active', true)]` |

The colour rule, the name and description limits and the whole `template_version_id` rule are lifted
from `App\Http\Controllers\App\Vaults\VaultController::rules()` rather than reinvented, so the two surfaces refuse the same
requests. Why `colour` and `description` are stricter on a change than on a creation is
[research.md](research.md) section 4.

### What the write operations do that the resource does not show

`CreateVault` also sets `position` to the end of the account's list, sets `is_default` when the account
held no vault, and gives the vault the template new contacts start from when the account's system
contact template has been seeded. `UpdateVault` sanitizes to plain text and records the values before
and after in the journal. `DestroyVault` does the eight things listed in
[research.md](research.md) section 6. None of that needs a field of its own: it is visible in the
vault that comes back, in the list, or in the journal.

## VaultDefaultTemplate

`app/Models/VaultDefaultTemplate.php`. Written by `SetVaultDefaultTemplate`, not read by this feature.

The row is keyed by vault **and** by kind of record, so one vault holds one default per kind. That is
why the vault resource carries no field naming it: a single field would describe the contact case only.
Reading these defaults is its own endpoint in its own change. A caller may set the contact one here, in
the same request that creates or changes the vault, exactly as the browser form does.

## The invariant this feature must not break

An account holds exactly one default vault while it holds a vault at all, and none while it holds
none. Three of the five endpoints can move it:

- Creating the first vault of an account makes it the default (`CreateVault`).
- Creating or changing a vault with `is_default` true takes the title from whoever held it
  (`SetDefaultVault`, inside a transaction).
- Deleting the vault that held it hands it to the first vault left, or to nobody when there is none
  (`DestroyVault`).

The tests assert the *count* of default vaults in the account after each write, not only the flag on
the vault that was written. A test that checks the flag alone passes while two vaults hold the title.

## Resource shape

One resource, `App\Http\Resources\VaultResource`, used by all four endpoints that answer with a vault.
No conditional keys: every endpoint shows the same fields, and the delete endpoint answers with nothing
at all. The envelope is `type`, `id`, `attributes`, `links`, matching the four resources already
shipped. The exact shape is in [contracts/vaults.md](contracts/vaults.md).
