# Data model: Stripe status in the admin space

Nothing new is stored. One cache key is added beside the catalogue. Everything else is read.

## Read from the database

| Table | Columns read | For |
| --- | --- | --- |
| `accounts` | `id`, `access_granted_at` | Access counts, every account (R8) |
| `stripe_orders` | `account_id`, `stripe_status` (`paid`) | Purchases made once (R8) |
| `subscriptions` | `account_id`, `stripe_id`, `stripe_status`, `trial_ends_at`, `ends_at`, through Cashier's scopes | Subscription states and access through a subscription (R8, R9) |
| `payments` | `stripe_subscription_id`, `kind`, `total`, `tax`, `currency`, `billed_at` | A subscription's kind (R9), monthly payments (R10) |
| `stripe_events` | `type`, `created_at` | Refunds and disputes (R10), confirmations received (R12). Kept 30 days |

## Read live, never stored

| What | Source |
| --- | --- |
| Keys set and their mode | `config('cashier.key')`, `config('cashier.secret')`, `config('cashier.webhook.secret')`, values never returned (R13) |
| Stripe connection, endpoints | `GET /v1/webhook_endpoints` (R3, R4) |
| Offers on sale | `StripeCatalogue::offers()`, cached one hour (R5) |

## Cache

| Key | Value | Written by |
| --- | --- | --- |
| `stripe_offers` | The catalogue, unchanged | `StripeCatalogue::read()` |
| `stripe_offers_read_at` | When `stripe_offers` was written, same lifetime | `StripeCatalogue::read()`, forgotten by `forget()` (R6) |

## The action's answer

`CheckStripeConnection::execute()` returns:

```text
keys: { publishable, secret, signing_secret } each { set: bool, mode: test|live|unknown|null }
modes_disagree: bool
environment_warning: null | test_keys_in_production | live_keys_outside_production
connection: not_configured | reachable | refused | unreachable
response_time_in_ms: int|null
failure_reason: string|null
endpoints: null (not read) | restricted | list of { address_is_this_instance: bool, url: string|null, enabled: bool }
missing_events: list of event names
```
