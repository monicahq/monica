<?php

declare(strict_types=1);

use App\ViewModels\Docs\ApiDocsFields;
use App\ViewModels\Docs\ApiDocsViewModel;

$base = ApiDocsViewModel::baseUrl();

$offer = [
    'price_id' => 'price_1QdundieMonthly',
    'kind' => 'monthly',
    'renews' => true,
    'amount' => 900,
    'currency' => 'eur',
    'prices' => [
        ['currency' => 'eur', 'amount' => 900],
        ['currency' => 'usd', 'amount' => 1000],
    ],
];

$billing = [
    'type' => 'billing',
    'attributes' => [
        'has_full_access' => true,
        'access_kind' => 'subscription',
        'renews_at' => 1759752000,
        'ends_at' => null,
        'purchased_at' => 1725710400,
        'order' => [
            'provider_id' => 'cs_1QdundieScranton',
            'status' => 'refunded',
            'offer' => 'lifetime',
            'price_id' => 'price_1QdundieLifetime',
            'ordered_at' => 1725710400,
            'settled_at' => null,
        ],
        'billing_email' => 'michael.scott@dundermifflin.com',
        'limits' => [
            'vaults' => null,
            'contacts' => null,
            'storage_in_bytes' => 10737418240,
        ],
        'usage' => [
            'vaults' => 3,
            'contacts' => 128,
            'storage_in_bytes' => 5242880,
        ],
        'payment' => [
            'provider_id' => 'in_1QdundieAwards',
            'offer' => 'monthly',
            'status' => 'paid',
            'total' => 900,
            'currency' => 'eur',
            'invoice_number' => 'DM-0042',
            'billed_at' => 1757073600,
        ],
        'offers_available' => true,
        'offers' => [$offer],
    ],
    'links' => [
        'self' => $base.'/billing',
    ],
];

$payment = [
    'type' => 'payment',
    'id' => 'in_1QdundieAwards',
    'attributes' => [
        'provider_id' => 'in_1QdundieAwards',
        'offer' => 'lifetime',
        'status' => 'paid',
        'total' => 19900,
        'tax' => 3317,
        'currency' => 'eur',
        'invoice_number' => 'DM-0042',
        'invoice_url' => 'https://invoice.stripe.com/i/test_a1dundermifflin',
        'invoice_pdf_url' => 'https://pay.stripe.com/invoice/test_a1dundermifflin/pdf',
        'billed_at' => 1757073600,
    ],
    'links' => [
        'self' => $base.'/billing/payments/in_1QdundieAwards',
    ],
];

$paymentAttributes = [
    ['name' => 'provider_id', 'type' => 'string', 'description' => 'The identifier of the invoice at Stripe, which is also this record\'s id. It is what makes a confirmation Stripe sends twice recognisable as one payment.'],
    ['name' => 'offer', 'type' => 'string', 'description' => 'Which plan was paid for: monthly, yearly or lifetime. It is kept as it stood on the day, so retagging a price at Stripe never rewrites the history, and it is null when the catalogue no longer knows the price that was charged.'],
    ['name' => 'status', 'type' => 'string', 'description' => 'The state of the invoice, as Stripe names it.'],
    ['name' => 'total', 'type' => 'integer', 'description' => 'What was paid, in the smallest unit of the currency, tax included.'],
    ['name' => 'tax', 'type' => 'integer', 'description' => 'The tax share of that total, worked out by Stripe from where the buyer was, and already part of it rather than added to it.'],
    ['name' => 'currency', 'type' => 'string', 'description' => 'The currency of both amounts, three letters as Stripe writes them.'],
    ['name' => 'invoice_number', 'type' => 'string', 'description' => 'The number Stripe gave the invoice, null when it gave none.'],
    ['name' => 'invoice_url', 'type' => 'string', 'description' => 'The page at Stripe where the invoice is read. Null when Stripe issued none.'],
    ['name' => 'invoice_pdf_url', 'type' => 'string', 'description' => 'The same invoice as a file. Null when Stripe issued none.'],
    ['name' => 'billed_at', 'type' => 'timestamp', 'description' => 'When Stripe finalised the invoice, as a count of seconds since 1970. It is what the list is ordered by.'],
];

$billingFields = [
    ['name' => 'data', 'type' => 'object', 'description' => 'What this account is paying, and what it is allowed.'],
    ['name' => 'data.type', 'type' => 'string', 'description' => 'Always billing. It names the kind of record, so a caller holding several kinds can tell them apart without looking at where they came from.'],
    ['name' => 'data.attributes', 'type' => 'object', 'description' => 'Everything the record holds.'],
    ['name' => 'data.attributes.has_full_access', 'type' => 'boolean', 'description' => 'Whether every ceiling but storage is lifted, however the account came to hold that: bought once, subscribed, or opened by us.'],
    ['name' => 'data.attributes.access_kind', 'type' => 'string', 'description' => 'How full access is held: lifetime, subscription or granted, and null on the free plan. On a self hosted instance it is always null, because nothing is sold there.'],
    ['name' => 'data.attributes.renews_at', 'type' => 'timestamp', 'description' => 'When the subscription next bills, as a count of seconds since 1970. Null when nothing renews, which includes a subscription already on its way out.'],
    ['name' => 'data.attributes.ends_at', 'type' => 'timestamp', 'description' => 'When a cancelled subscription stops, as a count of seconds. Null while it renews, and until that moment the cancellation can still be taken back.'],
    ['name' => 'data.attributes.purchased_at', 'type' => 'timestamp', 'description' => 'When the one time purchase was ordered, as a count of seconds, null when it never was.'],
    ['name' => 'data.attributes.order', 'type' => 'object', 'description' => 'The one time purchase, null when nothing was ever ordered. A checkout nobody paid is not one, and an order that stopped holding stays here so that the end of access can be read.'],
    ['name' => 'data.attributes.order.provider_id', 'type' => 'string', 'description' => 'The checkout session at Stripe the order was placed through.'],
    ['name' => 'data.attributes.order.status', 'type' => 'string', 'description' => 'The state in Stripe\'s own words: paid while it holds full access, then refunded or disputed once money went back.'],
    ['name' => 'data.attributes.order.offer', 'type' => 'string', 'description' => 'Which plan was bought, read from the price, null once Stripe no longer knows that price. A paid order holds full access whatever the answer.'],
    ['name' => 'data.attributes.order.price_id', 'type' => 'string', 'description' => 'The price at Stripe the order was placed against, kept as it was at the sale.'],
    ['name' => 'data.attributes.order.ordered_at', 'type' => 'timestamp', 'description' => 'When the checkout was opened, as a count of seconds.'],
    ['name' => 'data.attributes.order.settled_at', 'type' => 'timestamp', 'description' => 'When Stripe called the payment paid, as a count of seconds, null once the order no longer holds.'],
    ['name' => 'data.attributes.billing_email', 'type' => 'string', 'description' => 'The address the invoices went to, null before anything was paid.'],
    ['name' => 'data.attributes.limits', 'type' => 'object', 'description' => 'What the account is allowed. Null in any of these means no ceiling at all.'],
    ['name' => 'data.attributes.limits.vaults', 'type' => 'integer', 'description' => 'How many vaults may exist, null once full access is held.'],
    ['name' => 'data.attributes.limits.contacts', 'type' => 'integer', 'description' => 'How many contacts may exist across every vault, null once full access is held. Each vault owner and anybody still marked partial is left out of the count.'],
    ['name' => 'data.attributes.limits.storage_in_bytes', 'type' => 'integer', 'description' => 'How many bytes of files may be held. Full access lifts this to the paid figure rather than removing it, and null means the instance meters nothing.'],
    ['name' => 'data.attributes.usage', 'type' => 'object', 'description' => 'What the account holds right now, measured the same way the ceilings are.'],
    ['name' => 'data.attributes.usage.vaults', 'type' => 'integer', 'description' => 'How many vaults exist.'],
    ['name' => 'data.attributes.usage.contacts', 'type' => 'integer', 'description' => 'How many contacts the ceiling is measured against.'],
    ['name' => 'data.attributes.usage.storage_in_bytes', 'type' => 'integer', 'description' => 'How many bytes of files are held.'],
    ['name' => 'data.attributes.payment', 'type' => 'object', 'description' => 'The most recent payment, null when nothing was ever paid, which is what an account we opened access on looks like.'],
    ['name' => 'data.attributes.payment.provider_id', 'type' => 'string', 'description' => 'The identifier of the invoice at Stripe.'],
    ['name' => 'data.attributes.payment.offer', 'type' => 'string', 'description' => 'Which plan was paid for: monthly, yearly or lifetime. Read off the invoice rather than off the price it charged, and null when the catalogue no longer knows that price.'],
    ['name' => 'data.attributes.payment.status', 'type' => 'string', 'description' => 'The state of the invoice, as Stripe names it.'],
    ['name' => 'data.attributes.payment.total', 'type' => 'integer', 'description' => 'What was paid, in the smallest unit of the currency, tax included.'],
    ['name' => 'data.attributes.payment.currency', 'type' => 'string', 'description' => 'The currency of that amount, three letters as Stripe writes them.'],
    ['name' => 'data.attributes.payment.invoice_number', 'type' => 'string', 'description' => 'The number Stripe gave the invoice, null when it gave none.'],
    ['name' => 'data.attributes.payment.billed_at', 'type' => 'timestamp', 'description' => 'When Stripe finalised the invoice, as a count of seconds.'],
    ['name' => 'data.attributes.offers_available', 'type' => 'boolean', 'description' => 'Whether the catalogue could be read. False means Stripe did not answer and the list below is empty for that reason rather than because nothing is for sale.'],
    ['name' => 'data.attributes.offers', 'type' => 'array', 'description' => 'What this account may still take up, shortest commitment first. Empty once it owns the product outright, and on a self hosted instance.'],
    ['name' => 'data.attributes.offers[]', 'type' => 'object', 'description' => 'One plan.'],
    ['name' => 'data.attributes.offers[].price_id', 'type' => 'string', 'description' => 'The identifier of the price at Stripe. It is what you send to open a checkout, and it is the only handle on a plan: prices, names and currencies all live at Stripe.'],
    ['name' => 'data.attributes.offers[].kind', 'type' => 'string', 'description' => 'monthly, yearly or lifetime. Every one of them unlocks exactly the same thing, so the kind says how often it bills and nothing else.'],
    ['name' => 'data.attributes.offers[].renews', 'type' => 'boolean', 'description' => 'Whether taking it up starts a subscription. False is the one time purchase.'],
    ['name' => 'data.attributes.offers[].amount', 'type' => 'integer', 'description' => 'The price in the smallest unit of the currency below.'],
    ['name' => 'data.attributes.offers[].currency', 'type' => 'string', 'description' => 'The currency Stripe quotes the plan in by default.'],
    ['name' => 'data.attributes.offers[].prices', 'type' => 'array', 'description' => 'Every currency this plan is quoted in, the default one included. Send one of them as currency when you open a checkout.'],
    ['name' => 'data.attributes.offers[].prices[]', 'type' => 'object', 'description' => 'One amount in one currency.'],
    ['name' => 'data.attributes.offers[].prices[].currency', 'type' => 'string', 'description' => 'Three letters as Stripe writes them.'],
    ['name' => 'data.attributes.offers[].prices[].amount', 'type' => 'integer', 'description' => 'The price in the smallest unit of that currency.'],
    ['name' => 'data.links', 'type' => 'object', 'description' => 'Where to find this record.'],
    ['name' => 'data.links.self', 'type' => 'string', 'description' => 'The absolute address this record is read from.'],
];

$checkoutSessionFields = [
    ['name' => 'data', 'type' => 'object', 'description' => 'The checkout that was opened.'],
    ['name' => 'data.type', 'type' => 'string', 'description' => 'Always checkout_session.'],
    ['name' => 'data.attributes', 'type' => 'object', 'description' => 'Everything the record holds.'],
    ['name' => 'data.attributes.url', 'type' => 'string', 'description' => 'The page at Stripe to send the buyer to. It is short lived, it is the only place a card is ever entered, and opening it charges nothing on its own.'],
];

$portalSessionFields = [
    ['name' => 'data', 'type' => 'object', 'description' => 'The portal session that was opened.'],
    ['name' => 'data.type', 'type' => 'string', 'description' => 'Always portal_session.'],
    ['name' => 'data.attributes', 'type' => 'object', 'description' => 'Everything the record holds.'],
    ['name' => 'data.attributes.url', 'type' => 'string', 'description' => 'The page at Stripe where the owner reads their invoices and changes the card on file. It is short lived, so open it rather than storing it.'],
];

return [
    'name' => 'Billing',
    'description' => 'What the account pays, what it has paid, and the way to change or stop paying.',
    'sections' => [
        [
            'id' => 'billing-show',
            'returnFields' => $billingFields,
            'title' => 'Read what is being paid',
            'label' => 'Read billing',
            'method' => 'GET',
            'path' => '/billing',
            'description' => 'Retrieve whether this account holds full access, how it holds it, what it is allowed against what it holds, the last payment, and the plans it may still take up.',
            'body' => [
                'There is no identifier in the URL: a token acts as one person, and one person has one account.',
                'Nothing here is bought. Access moves only when Stripe confirms a payment, so a client that has just opened a checkout should read this again a moment later rather than assume anything.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'returns' => 'A billing object. On a self hosted instance every ceiling is null, the payment is null, offers is empty and access_kind is null, because nothing is sold there.',
            'response' => ['data' => $billing],
        ],
        [
            'id' => 'billing-payments-index',
            'returnFields' => ApiDocsFields::page('payment', 'payment', 'payments', $paymentAttributes),
            'title' => 'List the payments',
            'label' => 'List payments',
            'method' => 'GET',
            'path' => '/billing/payments',
            'description' => 'Retrieve every payment this account made, newest first, each with the invoice Stripe issued for it.',
            'body' => [
                'One row per invoice, whether it was the one time purchase or a renewal. They are kept here rather than read from Stripe on every call, so this never waits on Stripe and never stops working when Stripe does.',
                'An account we opened access on has no payment at all, and so answers an empty list.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'queryParams' => [
                [
                    'name' => 'per_page',
                    'type' => 'integer',
                    'required' => false,
                    'description' => 'How many payments one page holds. Brought into the allowed range rather than refused.',
                    'default' => '25',
                    'example' => '50',
                ],
            ],
            'returns' => 'A page of payment objects, an empty list rather than an error when there are none.',
            'response' => [
                'data' => [$payment],
                'meta' => [
                    'page' => 1,
                    'per_page' => 25,
                    'total' => 1,
                ],
            ],
        ],
        [
            'id' => 'billing-payments-show',
            'returnFields' => ApiDocsFields::one('payment', 'payment', $paymentAttributes),
            'title' => 'Get one payment',
            'label' => 'Get a payment',
            'method' => 'GET',
            'path' => '/billing/payments/{payment}',
            'examplePath' => '/billing/payments/in_1QdundieAwards',
            'description' => 'Retrieve one payment by the identifier Stripe gave its invoice.',
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'pathParams' => [
                [
                    'name' => 'payment',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The identifier of the invoice at Stripe.',
                    'example' => 'in_1QdundieAwards',
                ],
            ],
            'returns' => 'A payment object. Another account\'s payment answers 404, the same as one that does not exist.',
            'response' => ['data' => $payment],
        ],
        [
            'id' => 'billing-checkout-sessions-create',
            'returnFields' => $checkoutSessionFields,
            'title' => 'Open a checkout',
            'label' => 'Open a checkout',
            'method' => 'POST',
            'path' => '/billing/checkout-sessions',
            'description' => 'Open a Stripe Checkout session for one of the plans and answer the page to send the buyer to.',
            'body' => [
                'Payment is taken at Stripe and nowhere else: this API never receives card details. Access opens when Stripe confirms the payment, which is usually seconds after the buyer finishes, so read the billing object again rather than trusting their return.',
                'A price this account may not take up is refused before any session is opened. That covers an account that already owns the product outright, and a subscriber asking for a plan that does not sit above the one they are on.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'bodyParams' => [
                [
                    'name' => 'price_id',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The identifier of the price at Stripe, from offers in the billing object. Maximum 255 characters.',
                    'example' => 'price_1QdundieMonthly',
                ],
                [
                    'name' => 'currency',
                    'type' => 'string',
                    'required' => false,
                    'description' => 'Which of the currencies the plan is quoted in to charge, three letters. Left out, Stripe charges in the currency of the price.',
                    'example' => 'eur',
                ],
            ],
            'returns' => 'A checkout_session object carrying the URL. 422 when the price is not a plan this account may take up, and 503 when Stripe did not answer, in which case nothing was charged and nothing changed.',
            'responseStatus' => 201,
            'response' => [
                'data' => [
                    'type' => 'checkout_session',
                    'attributes' => [
                        'url' => 'https://checkout.stripe.com/c/pay/cs_test_a1dundermifflin',
                    ],
                ],
            ],
        ],
        [
            'id' => 'billing-subscription-update',
            'returnFields' => $billingFields,
            'title' => 'Move the subscription up',
            'label' => 'Move up',
            'method' => 'PUT',
            'path' => '/billing/subscription',
            'description' => 'Change the running subscription to a longer one, monthly to yearly, in place.',
            'body' => [
                'Nothing is charged on the day of the change. The longer price is billed at the renewal that was already planned, and that date does not move.',
                'There is one subscription throughout: no second one is opened, and access never gaps. Only a longer subscription is accepted, so a yearly subscriber cannot move back to monthly here, and lifetime access is bought through a checkout rather than swapped into.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'bodyParams' => [
                [
                    'name' => 'price_id',
                    'type' => 'string',
                    'required' => true,
                    'description' => 'The identifier of the price at Stripe to move to, from offers in the billing object. Maximum 255 characters.',
                    'example' => 'price_1QdundieYearly',
                ],
            ],
            'returns' => 'The billing object as it now stands. 422 when there is no subscription, when it is already ending, or when the price is not a longer subscription. 402 when the bank asks for the payment to be confirmed, with a payment_url to open in a browser: the subscription has already moved and stays past due until the payment is confirmed there.',
            'response' => ['data' => $billing],
        ],
        [
            'id' => 'billing-subscription-destroy',
            'returnFields' => [],
            'title' => 'Cancel the subscription',
            'label' => 'Cancel',
            'method' => 'DELETE',
            'path' => '/billing/subscription',
            'description' => 'Stop the subscription renewing at the end of the period already paid for.',
            'body' => [
                'It never takes effect at once. Full access stays to the last day that was paid for, the billing object then carries ends_at, and nothing the account holds is removed after that day: the free ceilings simply refuse additions again.',
                'Until that day the cancellation can be taken back, at no charge, with the endpoint below.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'returns' => 'Nothing. 422 when there is no subscription to cancel, or when it is already ending.',
            'responseStatus' => 204,
        ],
        [
            'id' => 'billing-subscription-cancellation-destroy',
            'returnFields' => $billingFields,
            'title' => 'Take a cancellation back',
            'label' => 'Keep the subscription',
            'method' => 'DELETE',
            'path' => '/billing/subscription/cancellation',
            'description' => 'Undo a cancellation that has not taken effect yet, so the same subscription goes on renewing.',
            'body' => [
                'The renewal date does not move and nothing is charged for changing your mind. It works only while the paid period is still running, which is exactly while the billing object carries ends_at.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'returns' => 'The billing object, with ends_at back to null. 422 when there is no subscription, or when it is not ending.',
            'response' => ['data' => $billing],
        ],
        [
            'id' => 'billing-portal-sessions-create',
            'returnFields' => $portalSessionFields,
            'title' => 'Open the customer portal',
            'label' => 'Open the portal',
            'method' => 'POST',
            'path' => '/billing/portal-sessions',
            'description' => 'Open Stripe\'s own customer portal and answer the page to send the owner to.',
            'body' => [
                'The portal is where invoices are read and the card on file is changed. Cancelling, resuming and moving up are turned off there on purpose, so that those decisions are made in one place, through this API or the product.',
            ],
            'rateLimit' => 'Sixty requests per minute, per user, shared by every key and device.',
            'returns' => 'A portal_session object carrying the URL. 422 when Stripe does not know the account yet, which is the case until something has been paid, and 503 when Stripe did not answer.',
            'responseStatus' => 201,
            'response' => [
                'data' => [
                    'type' => 'portal_session',
                    'attributes' => [
                        'url' => 'https://billing.stripe.com/p/session/test_a1dundermifflin',
                    ],
                ],
            ],
        ],
    ],
];
