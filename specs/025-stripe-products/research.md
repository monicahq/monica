# Research: The order behind a purchase

Decisions taken before writing anything, and what each one costs.

## Why the purchase unlocks nothing today

**Decision**: stop deriving the purchase from an invoice. **Rationale**: feature 024 asked Stripe to raise one through `invoice_creation`, that parameter was dropped, and `invoice.paid` is the only thing that reached `RecordPayment` and `RecordPurchase`. A one time payment raises no invoice on its own, so nothing runs and the buyer stays on the free offer. Subscriptions are untouched: a renewal always has an invoice. **Alternatives**: put `invoice_creation` back, which asks Stripe for a business profile complete enough to issue an invoice and fails the whole offer when it is not.

## What Cashier gives and what it does not

**Decision**: write the order ourselves, `Checkout` gives us the session id before the redirect. **Rationale**: `$account->checkout([$price => 1], $options)` returns `Laravel\Cashier\Checkout`, which proxies the Stripe session, so `id` and `payment_status` are readable at once and the row can be written before the buyer ever leaves. Cashier has no order concept and no table for one. **Alternatives**: the metadata dance from the Cashier quickstart, which writes the row first and passes its id in `metadata`. Needless here: the session id is the key, so nothing has to be carried there and back.

## What Stripe calls a paid session

**Decision**: settle on `payment_status` being `paid`, and store that word in `stripe_status`. **Rationale**: the session's `status` goes `open`, then `complete` or `expired`, and says nothing about money. `payment_status` is `unpaid` then `paid`, which is what FR-002 asks for. `no_payment_required` also exists, for a session discounted to nothing, and is deliberately not treated as paid: the product enables no promotion code, so it cannot occur, and treating it as paid would unlock the product for free the day one is enabled. **Alternatives**: reading `status`, which cannot tell a paid session from an abandoned one.

## Replacing an abandoned checkout

**Decision**: close the old session at Stripe with `checkout->sessions->expire()`, then write the row over. **Rationale**: the buyer asked for a second checkout, so an account should hold one order at a time, and the row is reused rather than deleted because an order is never deleted. Writing over a row whose session is still payable would strand money: a page left open in another tab could be paid, and `checkout.session.completed` would then name a session no order carries. Closing it first removes that path, and a session Stripe refuses to close is one it has already settled, so its order is left alone and the new checkout gets a row of its own. **Alternatives**: delete the old row, which contradicts FR-007; leave both rows, which is what the request asked to stop.

## Two ways to settle, one settlement

**Decision**: the return address and `checkout.session.completed` both call `SettleOrder`, which returns early on an order already settled. **Rationale**: the return is fast and gives the buyer the screen they expect, and the webhook covers the closed tab, the slow card and the unreachable browser. Neither may unlock twice, so idempotence lives in the action rather than in each caller. **Alternatives**: the return only, which loses a purchase whenever the buyer closes the page; the webhook only, which makes the buyer wait on a queue worker for the screen to change.

## Nothing translates a charge back into a session

**Decision**: no sixth column and no lookup. The account the charge names is what finds the order. **Rationale**: `charge.refunded` and `charge.dispute.created` carry a charge whose `customer` names the account, and an account holds at most one purchase, so the settled order is found without knowing which session made it. `StripeCharge::paymentFor()` still answers null for a purchase, since it matches on the charge's invoice and a purchase has none, and `EndAccess` already falls back to what the account holds. Money back on an account holding neither an order nor a subscription is logged at error level rather than dropped. **Alternatives**: keep the payment intent on the order, one indexed column, which would only matter the day an account can hold more than one purchase; ask Stripe which session carries the intent, an extra call per event that fails exactly when Stripe is unreachable.

## Where the grant goes

**Decision**: `accounts.access_granted_at`, a nullable datetime, and the administrator is read from `administrator_logs`. **Rationale**: the marker being removed is what a grant writes today, and a grant orders nothing at Stripe, so a row in `stripe_orders` would have no session, no status and no price. The log already records who granted and when, through `AdministratorActionEnum::LifetimeAccessGranted`. **Alternatives**: a `stripe_orders` row with three empty columns, which makes every reader ask whether an order is real.

## What the removal touches

**Decision**: edit `0000_01_01_000000_create_accounts_table.php` in place, no data migration. **Rationale**: the project edits its migrations until production and writes no `down()`, and the paywall itself is not merged, so there is no account holding the marker anywhere. FR-013 is met because there is nothing to carry over. Eleven readers change: `Account` (four reads plus the cast and the fillable), `CreateAccount`, `RecordPurchase`, `EndAccess`, `RestoreAccess`, `GrantLifetimeAccess`, `WithdrawLifetimeAccess`, `BillingViewModel`, `BillingResource`, `AccountFactory` and the admin account screen.

## Reading access

**Decision**: `hasFullAccess()` is a settled order, or a subscription Stripe still calls valid, or `access_granted_at`. `accessKind()` names which. **Rationale**: `AccessKindEnum` already has the three cases, and today it guesses `Granted` from the absence of a paid invoice. With the order and the grant each written down, the guess goes.

## Tests

**Decision**: extend `TestCase::fakeStripe()` with a session that answers `checkout/sessions/{id}` retrieval, and add `StripeOrderFactory`. **Rationale**: the fake client already intercepts through `ApiRequestor::setHttpClient()`, so the return route can be exercised without a network. The guard test in `StripeWebhookTest` scans `app/Listeners/HandleStripe*.php` for event names and fails unless each is in `config('cashier.webhook.events')`, which is what will force `checkout.session.completed` into the config.
