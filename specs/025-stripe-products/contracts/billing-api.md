# Contract: what changes in the billing API

The endpoints of feature 024 are unchanged in shape. Only the fields listed here move, and one route joins the web side. Everything else in [024's contract](../../024-stripe-paywall/contracts/billing-api.md) still holds.

## `GET api/v1/billing`

| Field | Change |
| --- | --- |
| `purchased_at` | Gone from the account, now the order's `created_at`, and still answered under the same name |
| `order` | New object, or null when nothing was ever ordered: `provider_id` (the checkout session), `status` (`unpaid`, `paid`, `refunded`, `disputed`), `offer` (from the price, null once the catalogue no longer knows it), `price_id`, `ordered_at`, `settled_at` |
| `access_kind` | Unchanged in shape, but `lifetime` now means a settled order and `granted` means `access_granted_at` stands, rather than both being guessed from whether an invoice exists |
| `payment` | Unchanged, and null for a purchase Stripe raised no invoice for, which is now the ordinary case |

`order` is what FR-012 asks the API for: what was ordered, when, the state it stands in, and why access ended, the reason being the status itself.

## `POST api/v1/billing/checkout-sessions`

Answers `201` with the same `{url}`. It now also writes the order behind it, `unpaid`, before answering. Nothing else changes, and `422` and `503` mean what they meant.

An API client is never redirected, so it never carries a session identifier back. Its order is settled by `checkout.session.completed`, which is why that event is not optional. A client polls `GET billing` to see `has_full_access` turn true.

## New web route

`GET settings/account/billing/checkout/success`, named `settings.checkoutReturn.show`, is where Stripe sends the buyer. It reads `session_id`, settles the order through `SettleOrder`, and redirects to `settings.billing.index` with `?checkout=success` when the session is paid, or `?checkout=pending` when Stripe does not call it paid yet, which is what the screen already says while a confirmation is outstanding.

It writes on a `GET`, because a redirect from Stripe is a `GET` and there is nowhere else for the settlement to happen. The screen itself stays a read.

## A route that opens wider

`GET settings/account/billing/subscription/portal` refused everything but a running subscription, while `POST api/v1/billing/portal-sessions` has always opened for any account Stripe knows. The web route now guards the same way, on the account being known to Stripe, so that a lifetime holder can read their own invoices there and the two surfaces agree. An account that only ever held a grant is unknown to Stripe, so it still gets `404`, and the billing screen shows it no link.

## Refusals

| Case | Answer |
| --- | --- |
| No `session_id` on the return | Redirect to the billing index, nothing settled, nothing said |
| A session Stripe does not know | Same, and the failure is logged |
| A session belonging to another account | Same. The order is matched on `stripe_id` and the account together, so another account's session finds nothing |
| A session Stripe does not call paid | `?checkout=pending`. The webhook settles it when the money lands |
| Stripe unreachable | `?checkout=pending`. The buyer is told the payment is being confirmed, and the webhook does the rest |

Nothing here can unlock access twice: `SettleOrder` returns early on an order already `paid`.
