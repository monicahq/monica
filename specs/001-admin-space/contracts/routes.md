# HTTP contract: Admin Space

**Feature**: `001-admin-space` | **Date**: 2026-08-21

Every route below sits in one group in `routes/web.php`:

```php
Route::middleware(['administrator', 'set.locale'])->prefix('admin')->group(...)
```

The `administrator` middleware answers `404` for a guest, for a signed in user who holds no
administrator row, and for one whose `is_active` is false. There is no `403` anywhere in the
admin space, and no redirect to the sign in screen (FR-002, SC-005).

Route names are camelCase and prefixed `admin.`. Public URL segments are kebab-case.

## Overview

| Verb | URL | Name | Controller |
|---|---|---|---|
| GET | `admin` | `admin.overview.index` | `Admin\OverviewController@index` |

Returns the overview screen: counts of accounts, users, vaults and contacts, total storage,
sign ins over the last seven days, accounts created over the last thirty days, and submissions
waiting. Each figure states its period. An empty instance renders zeros (FR-032, FR-033). The
waiting count links to `admin.moderation.index` in one step.

## Marketplace moderation

| Verb | URL | Name | Controller |
|---|---|---|---|
| GET | `admin/moderation` | `admin.moderation.index` | `Admin\ModerationController@index` |
| GET | `admin/moderation/{version}` | `admin.moderation.show` | `Admin\ModerationController@show` |
| POST | `admin/moderation/{version}/approval` | `admin.moderation.approval.create` | `Admin\ModerationApprovalController@create` |
| POST | `admin/moderation/{version}/rejection` | `admin.moderation.rejection.create` | `Admin\ModerationRejectionController@create` |

`{version}` is a `MarketplaceListingVersion` bound by primary key.

`index`: every version awaiting a decision, oldest first, paged, showing author, listing name,
category, submitted date and waiting time (FR-011, FR-035).

`show`: everything the author submitted for that version, including description and payload
(FR-012).

`approval.create`: no payload. Delegates to `ApproveMarketplaceListingVersion`. Redirects to
`admin.moderation.index` with a status message. A version that is no longer awaiting a decision
produces a redirect back with an error naming its current state, `422` on the form (FR-015,
Story 1 scenario 5).

`rejection.create`:

```
reason: required, string, max 65535
```

Delegates to `RejectMarketplaceListingVersion`. A missing reason fails validation and the
rejection does not happen (Story 1 scenario 3, FR-014).

## Account lookup

| Verb | URL | Name | Controller |
|---|---|---|---|
| GET | `admin/accounts` | `admin.accounts.index` | `Admin\AccountController@index` |
| GET | `admin/accounts/{account}` | `admin.accounts.show` | `Admin\AccountController@show` |

`index` accepts `?query=` (string, optional). Empty means the most recently created accounts.
A term matches on lowercased partial email, first name or last name. No match renders a stated
empty result, not a blank screen (Story 2 scenario 2).

`show` renders the account summary in `data-model.md`. It never links to a vault, a contact or a
file, and it renders for an account whose user is soft deleted (FR-021, Story 2 scenario 4).

## Account actions

| Verb | URL | Name | Controller |
|---|---|---|---|
| PUT | `admin/accounts/{account}/storage-limit` | `admin.accounts.storageLimit.update` | `Admin\AccountStorageLimitController@update` |
| POST | `admin/accounts/{account}/lifetime-access` | `admin.accounts.lifetimeAccess.create` | `Admin\AccountLifetimeAccessController@create` |
| DELETE | `admin/accounts/{account}/lifetime-access` | `admin.accounts.lifetimeAccess.destroy` | `Admin\AccountLifetimeAccessController@destroy` |
| POST | `admin/accounts/{account}/activation` | `admin.accounts.activation.create` | `Admin\UserActivationController@create` |
| DELETE | `admin/accounts/{account}/activation` | `admin.accounts.activation.destroy` | `Admin\UserActivationController@destroy` |

`storageLimit.update`:

```
storage_limit_in_bytes: nullable, integer, min 0
```

Null means no limit. A value below current usage is accepted after the warning, deletes nothing,
and new uploads are then refused by the check already in `CreateFile` (FR-024, Story 3
scenario 3).

`activation.destroy` deactivates the user on the account. It answers `404` when the target is
the signed in administrator's own user (FR-023, Story 3 scenario 6). `activation.create`
reactivates.

Every one of these five is reached from a warning the administrator has to confirm (FR-026),
built with `<x-modal.warning>`.

There is no route that deletes an account or a user, and none that signs an administrator in as
a user (FR-022, FR-027).

## Administrators

| Verb | URL | Name | Controller |
|---|---|---|---|
| GET | `admin/administrators` | `admin.administrators.index` | `Admin\AdministratorController@index` |
| POST | `admin/administrators` | `admin.administrators.create` | `Admin\AdministratorController@create` |
| DELETE | `admin/administrators/{user}` | `admin.administrators.destroy` | `Admin\AdministratorController@destroy` |

`index`: everybody holding the capacity, with who granted it and when, and with a deactivated
administrator shown as unable to administer rather than as active (FR-009, edge case).

`create`:

```
email: required, string, email, exists in users
```

An address belonging to nobody fails validation with a message saying no such user exists
(Story 4 scenario 3).

`destroy`: `{user}` is a `User`. Answers `404` when it is the administrator's own user
(FR-008), and `422` with the reason when it would leave the instance with none (FR-007).

## Record of administrator actions

| Verb | URL | Name | Controller |
|---|---|---|---|
| GET | `admin/logs` | `admin.logs.index` | `Admin\AdministratorLogController@index` |

Accepts `?administrator=` (user id) and `?account=` (account id), both optional. Most recent
first, paged (FR-029, FR-035). Read only: no route writes, edits or deletes an entry (FR-030).
