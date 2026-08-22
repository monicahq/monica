# Data model: Admin Space

**Feature**: `001-admin-space` | **Date**: 2026-08-21

Two tables are added. Nothing existing changes shape, and no column is dropped.

## New: `administrators`

Who holds the operator capacity for this instance. One row per administrator. Withdrawing the
capacity deletes the row, and the fact that it was withdrawn survives in `administrator_logs`.

| Column | Type | Null | Notes |
|---|---|---|---|
| `id` | big increments | no | primary key |
| `user_id` | unsigned big integer | no | unique, foreign key to `users`, cascade on delete |
| `granted_by_user_id` | unsigned big integer | yes | foreign key to `users`, null on delete. Null when the console designated them |
| `created_at` | timestamp | yes | when the capacity was granted |
| `updated_at` | timestamp | yes | |

Model `App\Models\Administrator`, table `administrators`, factory `AdministratorFactory`.

Relations:

- `user(): BelongsTo<User>`
- `grantedBy(): BelongsTo<User>`

Rules:

- `user_id` is unique, so the capacity cannot be held twice.
- `user_id` is not in `User::$fillable` anywhere, and this table is never written from a
  controller. Only `GrantAdministratorAccess` and `MakeAdministratorCommand` create a row
  (FR-004).
- The last row cannot be deleted (FR-007), and nobody may delete their own (FR-008). Both are
  enforced in `WithdrawAdministratorAccess`, inside a transaction that locks the table's rows.

Additions to `App\Models\User`:

- `administrator(): HasOne<Administrator>`
- `isAdministrator(): bool`, true when the relation exists **and** `is_active` is true. A person
  who cannot sign in cannot administer (edge case in the spec).

## New: `administrator_logs`

One row per thing an administrator did in the admin space. Append only: the model throws
`LogIsAppendOnly` on update and on delete, the same way `App\Models\Log` does (FR-030).

| Column | Type | Null | Notes |
|---|---|---|---|
| `id` | big increments | no | primary key |
| `user_id` | unsigned big integer | yes | the administrator, null when the console acted. Foreign key to `users`, null on delete |
| `user_email` | string | no | the address recorded at the time, or `Log::SYSTEM`. Survives the user |
| `action` | string | no | an `AdministratorActionEnum` value |
| `parameters` | json | yes | what the sentence needs to read, such as a name or a number of bytes |
| `account_id` | unsigned big integer | yes | the account acted on, indexed, so the record filters by account (FR-029) |
| `subject_type` | string | yes | morph type of what was acted on |
| `subject_id` | unsigned big integer | yes | morph id, no foreign key: what it names may be gone |
| `ip_address` | string(45) | yes | where the administrator was |
| `created_at` | timestamp | yes | indexed, the record reads most recent first |
| `updated_at` | timestamp | yes | |

Model `App\Models\AdministratorLog`, factory `AdministratorLogFactory`.

Relations and accessors:

- `user(): BelongsTo<User>`, `account(): BelongsTo<Account>`, `subject(): MorphTo`
- `author` accessor: the administrator's name, falling back to `user_email`, and to a
  translated name for the application itself when the console acted. Mirrors `Log::author`.
- `description` accessor: `__(AdministratorActionEnum::tryFrom($this->action)?->description() ??
  $this->action, $this->parameters ?? [])`. Mirrors `Log::description`.

## New enum: `App\Enums\AdministratorActionEnum`

String backed, implements `App\Contracts\Translatable`, one `description()` sentence per case,
`translationKeys()` returning `[$this->description()]`.

| Case | Value |
|---|---|
| `MarketplaceVersionApproved` | `marketplace_version_approved` |
| `MarketplaceVersionRejected` | `marketplace_version_rejected` |
| `UserDeactivated` | `user_deactivated` |
| `UserReactivated` | `user_reactivated` |
| `AccountStorageLimitUpdated` | `account_storage_limit_updated` |
| `LifetimeAccessGranted` | `lifetime_access_granted` |
| `LifetimeAccessWithdrawn` | `lifetime_access_withdrawn` |
| `AdministratorAccessGranted` | `administrator_access_granted` |
| `AdministratorAccessWithdrawn` | `administrator_access_withdrawn` |

## New enum cases: `App\Enums\EmailType`

| Case | Value |
|---|---|
| `MarketplaceListingApproved` | `marketplace_listing_approved` |
| `MarketplaceListingRejected` | `marketplace_listing_rejected` |

## Read only views over existing tables

Nothing below adds a column. These are the shapes the screens assemble, and they exist as view
models, not as tables.

### Account summary (Story 2)

From `accounts`: `created_at`, `is_self_hosted`, `has_lifetime_access`, `purchased_at`,
`billing_email`, `storage_used_in_bytes`, `storage_limit_in_bytes`.
From the `users` row on it: `name`, `email`, `email_verified_at`, `sso_provider`,
`two_factor_confirmed_at`, `is_active`, `last_login_at`, `deleted_at`.
Counts: `vaults()->count()`, `contacts()->count()`.

Hard boundary (FR-021, SC-006): nothing from `contacts`, `entities`, `field_values`,
`field_value_rows`, `files`, `record_snapshots` or `share_links` beyond a count of contacts. No
route in the admin space takes a contact, a file or a vault as a parameter.

Presentation rules:

- A null `storage_limit_in_bytes` reads as unlimited, never as zero (Story 2, scenario 5).
- A self hosted account reads billing email and purchase as not applicable, never as empty
  (edge case).
- A soft deleted user is shown with the deletion stated, and the screen still renders
  (Story 2, scenario 4). The lookup therefore reads users `withTrashed()`.

### Moderation queue item (Story 1)

`marketplace_listing_versions` where `status = submitted`, oldest `created_at` first, with the
`listing` and its `account.user` loaded. Shown: author name, listing name, category, submitted
date, waiting time. The detail screen adds `summary`, `description`, `change_note` and the
`payload` of the version.

### Instance overview (Story 5)

`Account::count()`, `User::count()`, `Vault::count()`, `Contact::count()`,
`Account::sum('storage_used_in_bytes')`, `User::where('last_login_at', '>=', now()->subDays(7))
->count()`, `Account::where('created_at', '>=', now()->subDays(30))->count()`,
`MarketplaceListingVersion::where('status', Submitted)->count()`.

## State transitions

Administrator capacity:

```
none --grant (another administrator, or the console)--> held
held --withdraw (another administrator, never the last one)--> none
```

User activity:

```
active --deactivate (never yourself)--> inactive   (open sessions stop working)
inactive --reactivate--> active                    (nothing owned was lost)
```

Marketplace version, unchanged from what exists:

```
submitted --approve--> approved
submitted --reject (reason required)--> rejected
submitted --author withdraws--> withdrawn
approved | rejected | withdrawn --any decision--> refused, with the standing state stated
```
