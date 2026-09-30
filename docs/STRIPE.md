# Configuring Stripe

Everything an operator has to set up at Stripe, and in `.env`, before the paywall works on a hosted
instance.

A self hosted instance needs none of it. With `MONICA_SELF_HOSTED=true` nothing is capped, no plan,
price or upgrade prompt appears on any screen, and no key is ever read. The rest of this page is for
the cloud instance only.

## How the work is divided

Stripe owns the catalogue, the checkout page, the invoices, the customer portal, the tax computation
and the retrying of failed payments. The product owns the free caps, the order behind a purchase
bought outright, and a copy of every payment so that no screen waits on Stripe.

Nothing about pricing lives here. There is no price, no currency and no plan name in the code
or in the configuration. Adding or removing a plan is done at Stripe alone, by tagging a
price, and takes no deploy.

## 1. Keys

Work in **test mode** first. Never point a development instance at live keys.

| Variable | Where it comes from |
| --- | --- |
| `STRIPE_KEY` | Developers, API keys, the publishable key |
| `STRIPE_SECRET` | the same screen, the secret key |
| `STRIPE_WEBHOOK_SECRET` | the signing secret of the endpoint you create in step 4 |
| `CASHIER_CURRENCY` | the currency an amount is quoted in when nothing has picked one, lower case, for example `eur` |

Without `STRIPE_WEBHOOK_SECRET` no confirmation can be proven to have come from Stripe, so nothing
is acted on and no payment ever unlocks anything.

The caps of the free plan sit beside them:

| Variable | Default | What it caps |
| --- | --- | --- |
| `MONICA_FREE_VAULT_LIMIT` | 1 | vaults, on an account without full access |
| `MONICA_FREE_CONTACT_LIMIT` | 30 | contacts across every vault, leaving out each vault's Me contact and anybody still partial |
| `MONICA_PAID_STORAGE_LIMIT_IN_BYTES` | 10737418240 | the storage ceiling once full access is held |
| `MONICA_OFFER_TAG` | `offer` | the metadata key a price carries to be on sale |
| `MONICA_PRODUCT_TAG_KEY` | `version` | the metadata key a product carries for its prices to be on sale, empty to sell every product |
| `MONICA_PRODUCT_TAG_VALUE` | none | the value that key has to hold, unset to ask only that the key is there |

## 2. The plans

Create at least three prices on one product, and tag each one in its **metadata**. The key is
`MONICA_OFFER_TAG`, which is `offer` unless you changed it. The value says which plan it is.

| Value | The price has to bill |
| --- | --- |
| `monthly` | recurring, every 1 month |
| `yearly` | recurring, every 1 year |
| `lifetime` | one time |

Three rules follow from that, and each is worth knowing before you wonder why a plan is missing:

* A price carrying no tag is not for sale here, silently. That is how you keep other prices on the
  same account out of the product.
* A price whose billing cycle disagrees with its tag is left out and the mismatch is written to the
  log. A monthly price tagged `lifetime` would otherwise sell permanent access for nine euros.
* A price tagged with a word the product does not know is left out and logged too.

### Selling only some of the products on the account

The tag above says which plan a price is. It says nothing about which product the price hangs off,
so an account that also sells something else needs a second filter, and that one reads the
**metadata of the product** rather than of its prices. Marking a product once covers every price it
has.

Name the key in `MONICA_PRODUCT_TAG_KEY` and the value in `MONICA_PRODUCT_TAG_VALUE`, then add that
pair to the product's metadata at Stripe. The key is `version` unless you changed it, so an instance
that configures nothing already asks a product to carry a `version` key.

| What is configured | What is for sale |
| --- | --- |
| an empty key | every product on the account |
| both | only a product whose metadata holds that key with that value |
| the key alone | only a product whose metadata holds that key, whatever it holds |

The key alone is allowed because setting a key and forgetting the value would otherwise empty the
catalogue without saying why.

A price whose product does not carry what is asked for is left out silently, like an untagged price,
and for the same reason: a product belonging to something else is not a mistake worth reporting.
Nothing reaches the log. If the billing screen says there is nothing for sale at all, check this
filter and the tags on the prices: those two are the only reasons a catalogue comes back empty with
nothing written anywhere.

Give every plan an amount in each currency you want to sell in, through Stripe's multi currency
prices. The currency picker on the billing screen offers exactly the currencies the catalogue quotes,
and suggests the one the reader's language points at.

The catalogue is read once an hour and cached, so a change at Stripe takes up to an hour to show. To
see it at once, clear the cache. A read that fails is not cached: the screen says the plans cannot
be shown and tries again on the next visit.

### How the lifetime plan is confirmed

A subscription raises an invoice on its own. A one time payment does not, and the product no longer
asks Stripe for one, so nothing about the purchase depends on your business profile being complete
enough for Stripe to issue an invoice.

What the product does instead is write an order of its own the moment the checkout page opens, keyed
on the checkout session, and settle it when Stripe says that session is paid. It hears that twice
over: the buyer comes back to the product carrying the session identifier, and Stripe sends
`checkout.session.completed`. Either settles the order, never both, so a buyer who closes the tab
still gets what they paid for.

That second path is why `checkout.session.completed` is not an optional event, and why the queue
worker matters. Without it, a buyer who pays and never comes back stays on the free plan.

An abandoned checkout leaves an order behind that nobody paid. It is shown nowhere and it holds
nothing, and no event of Stripe's is listened for to tidy it. Opening another checkout is what
replaces it: the old session is closed at Stripe first, so a page left open in another tab cannot be
paid afterwards, and then the same row is written over. A session Stripe refuses to close is one it
has already settled, and its order is left alone so that the money still has somewhere to land.

## 3. Tax

Turn Stripe Tax on and give it an origin address, and set it there to treat prices as **tax
inclusive**. Every amount the product shows is then what the buyer pays, the same figure in every
country, and Stripe works the tax share out of it at checkout and shows the split on the invoice.

Nothing checks this price by price. A price created without setting its tax behaviour reads
`unspecified` and inherits the account setting, so a check here would reject perfectly good prices
instead of catching a mistake. It is configuration, not data.

## 4. The webhook endpoint

Access moves only when Stripe says a payment happened, never when a buyer returns from the checkout
page. This step is therefore the one that makes the paywall work at all.

The URL is `https://your-instance/stripe/webhook`, or `CASHIER_PATH` in place of `stripe` if you
changed it.

The quickest way to create it, which subscribes it to exactly the right events:

```bash
php artisan cashier:webhook
```

That reads the list in `config/cashier.php`. Copy the signing secret it tells you about into
`STRIPE_WEBHOOK_SECRET`. If you would rather create the endpoint by hand in the dashboard, these are
the events to enable and what each one does:

| Event | What happens |
| --- | --- |
| `checkout.session.completed` | the order behind a one time purchase is settled and full access opens, which is what covers a buyer who never comes back to the product |
| `invoice.paid` | the payment is written down, so a screen can show what was charged. It opens nothing |
| `customer.subscription.created` | the subscription is recorded and the owner is told access is open |
| `customer.subscription.updated` | keeps the local subscription in step: its state, its price, its end date. This is how a card going past due arrives, and how a change made in the dashboard reaches the product |
| `customer.subscription.deleted` | access ends, and the owner is told whether it was cancelled or whether Stripe gave up on the payments |
| `charge.refunded` | a refund of the whole amount withdraws access, and the order stays, marked refunded |
| `charge.refund.updated` | a refund that then fails or is called off puts access back, since the money never left |
| `charge.dispute.created` | a dispute withdraws access while it is open |
| `charge.dispute.closed` | a dispute won puts access back |
| `customer.updated` | the card brand and last four digits shown to the owner |
| `customer.deleted` | the local subscriptions are cancelled and the customer reference cleared |
| `payment_method.automatically_updated` | the same card details, when the network replaces an expiring card |
| `invoice.payment_action_required` | the package's own notification, which this product leaves off |
| `invoice.payment_succeeded` | bookkeeping the package does on a subscription it opened through a checkout |

Two things about that list are easy to get wrong.

The first is that the package's own default list holds only the last five. The five that carry money,
`invoice.paid` and the four charge events, are added by `config/cashier.php`. An endpoint subscribed
to the defaults alone looks perfectly healthy in the dashboard and never opens access for anybody.
A test refuses to pass if a listener acts on an event the configuration does not name.

The second is that a subscription cancelled at Stripe cannot be brought back. A dispute won over a
renewal, or a refunded renewal that is then reversed, leaves the owner to subscribe again. Only the
one time purchase can be put back.

### The queue

Every one of those events is handled on a queue, so that a slow Stripe never holds up a screen, and
so that a confirmation is retried before it is abandoned. A worker has to be running:

```bash
php artisan queue:work
```

Without one, payments are recorded nowhere and no access is opened.

### Locally

```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

Take the signing secret it prints and put it in `STRIPE_WEBHOOK_SECRET`.

Be aware that `stripe listen` forwards **every** event by default. Local development therefore works
even when the deployed endpoint is subscribed to the wrong events, which is exactly how a missing
event reaches production unnoticed. Check the deployed endpoint's event list separately.

## 5. Retrying failed payments

In Subscriptions settings, turn Smart Retries on, and set what happens after every retry fails to
**cancel the subscription**.

This matters more than it looks. Access is meant to survive a card being refused: it ends only when
Stripe gives up on the subscription. If Stripe is set to leave a subscription unpaid instead of
cancelling it, a lapsed card never ends access and the account keeps everything for free.

## 6. The customer portal

Configure the portal to show invoice history and to let somebody update their payment method.

Turn cancelling and plan changes **off** there. The product is the one place those decisions are
made, so that the warning before a cancellation, the end date, and the promise that nothing is
deleted are all shown, and so that moving to a longer subscription charges nothing on the day.

## 7. Emails

Turn on the successful payment and failed payment emails in Stripe.

The product sends two emails of its own and no more: one when access opens, one when it ends with
the reason why. Receipts, invoices and payment reminders come from Stripe, which is the only party
that knows the money changed hands.

## Checking it works

```bash
php artisan monica:localize --check && vendor/bin/pint --dirty --format agent && vendor/bin/phpstan analyse
php artisan test --compact --filter='Purchase|Subscription|Access|Payment|Catalogue|Billing|Offer|Paywall|Checkout|Invoice|StripeWebhook|PortalSession'
```

Then, with a queue worker running, walk the scenarios in `specs/024-stripe-paywall/quickstart.md` and
`specs/025-stripe-products/quickstart.md`. Together they go through buying each plan, closing the tab
instead of coming back, an abandoned checkout, moving up, cancelling and changing your mind, a card
that keeps failing, a full and a partial refund, and deleting an account that holds a subscription.

## What the product never does

* It never receives, stores or sends card details. Payment is taken on Stripe's page.
* It never holds a price, a currency or a plan name.
* It never decides what a payment unlocked from the tag on a price alone. A price whose billing cycle
  disagrees with its tag is not on sale at all, so a monthly price tagged `lifetime` never opens
  anything, and a renewal is told from a purchase by the invoice behind it.
* It never unlocks anything because a buyer came back from the checkout page. Coming back only makes
  the product ask Stripe whether that session was paid, and Stripe's answer is what unlocks.
* It never deletes an order. Money given back leaves it in place, carrying Stripe's word for what
  happened, which is how a screen can say when access ended and why.
