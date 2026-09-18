# Data Model: Instance Configuration

## Table `instance_settings`

| Column | Type | Notes |
| --- | --- | --- |
| `id` | id | |
| `name` | string, unique | the enum case's value, e.g. `free_contact_limit` |
| `value` | text, nullable | encrypted for every setting, not only secrets (see [research.md](research.md)) |
| `created_at`, `updated_at` | timestamps | |

A row exists only for a setting somebody changed. Absence means the deployment value governs, which is what makes reverting a delete rather than a write.

## `InstanceSettingEnum`

One case per setting, carrying `group()`, `kind()`, `configKey()` and `rules()`. `configKey()` is what the provider overrides, so a case named `FreeContactLimit` returns `monica.free_contact_limit`.

| Group | Settings |
| --- | --- |
| This instance | whether it is self hosted (read only, FR-007). First on the screen: it decides whether accounts are metered at all, so it reads as context for everything under it |
| Accounts | free storage limit, free vault limit, free contact limit, paid storage limit, largest upload |
| Signing in | remembered session duration, sign in link offered, sign in link lifetime |
| Signing in with an outside provider | the identifier and secret of each provider whose driver is installed |
| Reminders and notifications | generation window, maximum attempts, retention months, channel failure threshold |
| Files | detached file grace period |
| API | default page size, maximum page size |
| Outbound services | mail transport and its credentials, text message provider, chat provider, the currency amounts are quoted in |

## `InstanceSettingKindEnum`

The kind decides validation and how the control is drawn.

| Kind | Rules | Shown as |
| --- | --- | --- |
| `Integer` | `['required', 'integer', 'min:1']` | a number field |
| `Boolean` | `['required', 'boolean']` | a switch |
| `Url` | `['required', 'url', 'max:255']` | a text field |
| `Text` | `['required', 'string', 'max:255']` | a text field |
| `Secret` | `['nullable', 'string', 'max:1024']` | a write only field reporting set or unset |

An empty secret means leave it alone, never clear it. Clearing is its own control, so a blank submission can never silently drop a credential.

Two rules span settings and are checked in `UpdateInstanceSetting`, not in the kind: the default page size may not exceed the maximum page size, and the free storage limit may not exceed the paid one, because paying would otherwise buy less. (The grace period rule this once named had nothing to compare against: no record restore window is configured anywhere.)

## Derived, not stored

- **Deployment value**: what configuration held before any stored value overrode it, stashed by the provider at boot. FR-002 shows it, FR-005 reverts to it, FR-014 names it when the two disagree.
- **Changed**: a row exists and its value differs from the deployment value.
- **Orphaned**: a row whose `name` is absent from the enum. Kept and shown as no longer applying (FR-013).
- **Availability**: whether an outbound provider's package is installed, read through configuration as the constitution requires, never with a bare `class_exists` at the point of use.

## Administrator record

`AdministratorActionEnum` gains `InstanceSettingUpdated` and `InstanceSettingReverted`. Parameters hold the setting name, the value before and the value after. For a secret both values are omitted and only the fact is written (FR-006).
