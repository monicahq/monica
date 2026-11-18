# Contracts: Stripe status in the admin space

## The routes

| Method | URL | Name | Middleware | Answer |
| --- | --- | --- | --- | --- |
| GET | `admin/billing` | `admin.billing.index` | `administrator` | 200 with the screen. 404 for anybody else |
| POST | `admin/billing/catalogue-reload` | `admin.billing.catalogueReload.create` | `administrator` | Redirect to the screen with `status` "The catalogue was read again." or "The catalogue could not be read." 404 for anybody else |

The POST sits outside the `password.confirm` group (R7).

## The Stripe call

One call per page load, never on a self hosted instance or without a secret key (R14).

```text
GET https://api.stripe.com/v1/webhook_endpoints?limit=100
Authorization: Bearer {secret key}
timeout 3 seconds, connect timeout 2 seconds
```

| Answer | Reads as |
| --- | --- |
| 200 | `reachable`. Endpoints from `data[]`: `url`, `status` (`enabled` or `disabled`), `enabled_events` (a list of event names, or `["*"]`) |
| 401 | `refused`, reason "Stripe refused the secret key" |
| 403 | `reachable`, endpoints `restricted`: the key may not read endpoints |
| other status | `unreachable`, reason the status code |
| connection error, timeout | `unreachable`, reason the exception message with the key replaced if it appears |

The catalogue is read afterwards through `StripeCatalogue`, unchanged, only on `reachable` (R5).
