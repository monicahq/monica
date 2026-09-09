# Data Model: The order behind a purchase

## New table, `stripe_orders`

Shaped like `subscriptions`, because that is what the request asked for. `up()` only, no `constrained()`, a comment on every column, the foreign key declared at the bottom. File `2026_09_08_000000_create_stripe_orders_table.php`.

| Column | Type | Comment it carries |
| --- | --- | --- |
| `id` | `id()` | primary key |
| `account_id` | `unsignedBigInteger` | account that ordered, the billable model Cashier is told to use |
| `type` | `string` | name the order is addressed by, always default since an account orders one thing |
| `stripe_id` | `string`, unique | checkout session identifier at Stripe, which is what makes a repeated return recognisable |
| `stripe_status` | `string` | state as Stripe names it, unpaid then paid, refunded or disputed once money goes back |
| `stripe_price` | `string` | price the order was placed against, which is what names the offer bought |
| `timestamps` | | placed at `created_at`, and `updated_at` is when the state last moved |

Index on `['account_id', 'stripe_status']`, the way `subscriptions` is indexed. Foreign key on `account_id` to `accounts`, `cascadeOnDelete`, so deleting an account takes its orders with it.

## States and what moves them

| From | To | What moves it |
| --- | --- | --- |
| absent | `unpaid` | `StartCheckout`, once Stripe has given the session an id |
| `unpaid` | `paid` | `SettleOrder`, from the return address or from `checkout.session.completed` |
| `unpaid` | `unpaid`, for good | nothing. An abandoned session is not followed, and the row holds nothing |
| `unpaid` | `unpaid`, another session | `StartCheckout` again. The old session is closed at Stripe, then the row is written over, session, price and date, so an abandoned checkout is replaced rather than doubled. A session Stripe will not close leaves its row alone |
| `paid` | `refunded`, `disputed` | `EndAccess`, when Stripe reports money back it can tie to the order, or `WithdrawLifetimeAccess` naming the reason |
| `refunded`, `disputed` | `paid` | `RestoreAccess`, when a dispute is won or a refund is reversed |

Only `paid` holds full access. No state deletes a row.

## Changed table, `accounts`

Edited in place in `0000_01_01_000000_create_accounts_table.php`, since nothing has shipped and there is no marker anywhere to carry over.

- `has_lifetime_access`, gone. Whether access is held is now three questions: a settled order, a valid subscription, a standing grant.
- `purchased_at`, gone. The day of the purchase is the order's `created_at`.
- `access_granted_at`, new, nullable datetime: when an administrator granted full access, null when nobody did. Who granted it is already in `administrator_logs`.

## Models

- `StripeOrder`: `belongsTo` account, `#[Fillable]` over the five columns, `isSettled()` reading `stripe_status`, and `offerKind()` resolving `stripe_price` through `StripeCatalogue`, null once the catalogue no longer knows the price.
- `Account`: `orders()` newest first, `settledOrder()` the paid one, `hasFullAccess()` over the three sources, `accessKind()` naming which, and `purchasePayment()` removed. `availableOffers()` and `offersAvailable()` read `hasFullAccess()` rather than the marker.
- `Payment`: unchanged. A purchase may have no payment row at all, and the invoice list stays as Stripe filled it.

## Not stored

The catalogue, still Stripe's tagged prices read once an hour. The offer an order was for, derived from `stripe_price` and unknown once that price is gone, which costs nothing while one thing is on sale. The payment intent behind the session, which is why a refund cannot find its order on its own.
