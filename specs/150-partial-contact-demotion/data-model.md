# Data model: making a full contact partial

No schema change. The feature sets an existing column.

## Contact (`contacts`)

| Column | Role here |
| --- | --- |
| `is_partial` | The state this feature sets. `false` for a full contact, `true` for a partial one. |
| `is_me` | A contact with `true` is never made partial (policy `demote`). |
| `is_archived` | An archived contact is refused by `DemoteFullContact` (no screen reaches it). |
| `is_favourite` | Kept as it is in both directions. |
| `updated_at` | Moves on every change of state; CardDAV relies on it (research R9). |

## Transitions

| From | To | By | Guard | Journal |
| --- | --- | --- | --- | --- |
| Full | Partial | `DemoteFullContact` | same account; policy `demote` (not the account holder); not already partial; not archived | `ContactDemoted` |
| Partial | Full | `PromotePartialContact` | same account; not already full; policy `create` under the account lock | `ContactPromoted` |
| Partial + Partial | Full (merge) | `MergeContacts` | policy `create` under the account lock, new (R4) | existing merge entry |

The full state machine, with the transitions this feature does not touch, is in
[analysis.md](analysis.md), section C.

## Counts

- `Account::contactCount()` excludes partial contacts: demotion lowers it by one, promotion raises it
  by one.
- `vaults.contact_count` includes partial contacts and is not touched by either direction.

## Journal (`UserActionEnum`)

| Case | Value | Description |
| --- | --- | --- |
| `ContactPromoted` | `contact_promoted` | "Made :name a full contact" (exists on this branch) |
| `ContactDemoted` | `contact_demoted` | "Made :name a partial contact" (new) |
