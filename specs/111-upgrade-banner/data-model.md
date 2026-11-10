# Data model: Upgrade strip under the top bar

No table, column or migration changes. Everything the strip reads exists.

## Read

| Source | Used for |
| --- | --- |
| `users.full_access_offer_dismissed_at` | Whether the user hid the banner. Written by `MarkFullAccessOfferAsDismissed`, unchanged |
| `Account::isMetered()`, `Account::hasFullAccess()` | Whether the account is offered full access, through `User::isOfferedFullAccess()`, unchanged |
| `Account::contactCount()`, `Account::contactLimit()` | The people meter |
| `$account->vaults()->count()`, `Account::vaultLimit()` | The vault meter |

## `FullAccessOfferViewModel` (presentation only)

Built from a `User` whose `isOfferedFullAccess()` is true.

`meters(): list<array{label: string, used: int, limit: int, percent: int, tone: string}>`, in this
order:

| label | used | limit |
| --- | --- | --- |
| `__('People')` | `contactCount()` | `contactLimit()` |
| `__('Vaults')` | `vaults()->count()` | `vaultLimit()` |

- `percent`: `min(100, round(used / limit * 100))`, and 100 when `limit` is 0.
- `tone`: `danger` when `limit` is 0 or `used >= limit`, `warning` when `used * 5 >= limit * 4`,
  `neutral` otherwise.

## State

| User and account | Strip |
| --- | --- |
| Hosted, metered, no full access, never hid it | Shown on every screen of the shell and the account layout, except billing and the screens reached from it |
| The user hid it, now or before this change | Hidden for good |
| Full access, by purchase, subscription or grant | Hidden while it lasts, shown again when it ends unless hidden |
| Self hosted | Never shown |
