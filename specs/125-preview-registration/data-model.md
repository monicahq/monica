# Data Model: Preview list

One new table. It belongs to the instance and carries no `account_id`.

## `preview_list_entries`

| Column | Type | Rule |
| --- | --- | --- |
| `id` | id | |
| `email` | string | unique, stored lowercased |
| `locale` | string | the locale the visitor read the page in |
| `token` | string, nullable | unique, SHA-256 of the token in the link, null once used or never admitted |
| `token_expires_at` | datetime, nullable | 14 days after the admission |
| `admitted_at` | datetime, nullable | the last admission |
| `registered_at` | datetime, nullable | when the account was created |
| `created_at`, `updated_at` | timestamps | `created_at` is the day the entry joined |

## State

| State | Condition |
| --- | --- |
| Waiting | `admitted_at` and `registered_at` are null |
| Admitted | `admitted_at` is set and `registered_at` is null |
| Registered | `registered_at` is set |

| From | By | To |
| --- | --- | --- |
| nothing | a visitor joins | Waiting |
| Waiting, Admitted | an administrator admits (new token, new expiry) | Admitted |
| Admitted | the person creates their account (token set to null) | Registered |
| any | an administrator removes | deleted |

## Reads

- A link is usable when its token hash matches an entry that is not registered and whose
  `token_expires_at` is in the future. `PreviewListEntry::findByAdmissionToken()` is the one place
  that says so, for the link, the middleware and the three sign up controllers.
- The admin list orders by state (waiting, admitted, registered), then `created_at`, then `id`, with
  a `CASE` expression that SQLite, MySQL and PostgreSQL all accept.

## Setting

`preview_list_enabled` comes from `MONICA_PREVIEW_LIST_ENABLED`, and is a row of the existing
`instance_settings` table once an administrator overrides it. No migration for it.
