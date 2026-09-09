<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Override;
use Stripe\ApiRequestor;
use Tests\Fixtures\Stripe\FakeStripeClient;

abstract class TestCase extends BaseTestCase
{
    #[Override]
    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);

        parent::tearDown();
    }

    /**
     * Put a fake Stripe in place of the network, answering the calls this application makes.
     *
     * The answers below are the shapes Cashier reads. A test overrides one, or adds a path of its
     * own, by passing it in `$endpoints` or by calling `respondTo()` on what comes back.
     *
     * The instance is put in a known state at the same time: it sells every product, whatever the
     * environment the suite runs in names. A test about that filter says so with `config()` of its
     * own, after this call.
     *
     * @param  array<string, array<string, mixed>>  $endpoints
     * @param  list<string>  $kinds
     */
    protected function fakeStripe(array $endpoints = [], array $kinds = ['monthly', 'yearly', 'lifetime']): FakeStripeClient
    {
        config([
            'cashier.secret' => 'sk_test_dundermifflin',
            'cashier.key' => 'pk_test_dundermifflin',
            'monica.product_tag.key' => null,
            'monica.product_tag.value' => null,
        ]);

        $client = new FakeStripeClient;

        $client->respondTo('prices*', $this->stripePrices($kinds));
        $client->respondTo('customers*', $this->stripeCustomer());
        $client->respondTo('checkout/sessions*', $this->stripeCheckoutSession());
        $client->respondTo('billing_portal/sessions*', [
            'id' => 'bps_test_dundermifflin',
            'object' => 'billing_portal.session',
            'url' => 'https://billing.stripe.com/p/session/test_dundermifflin',
        ]);
        $client->respondTo('charges*', [
            'id' => 'ch_dundermifflin',
            'object' => 'charge',
            'customer' => 'cus_dundermifflin',
            'amount' => 19900,
            'amount_refunded' => 19900,
            'invoice' => 'in_dundermifflin',
        ]);
        $client->respondTo('subscriptions*', $this->stripeSubscription());
        $client->respondTo('subscription_items*', [
            'id' => 'si_dundermifflin',
            'object' => 'subscription_item',
            'current_period_end' => CarbonImmutable::parse('2026-10-07 12:00:00')->getTimestamp(),
            'current_period_start' => CarbonImmutable::parse('2026-09-07 12:00:00')->getTimestamp(),
        ]);

        foreach ($endpoints as $pattern => $body) {
            $client->respondTo($pattern, $body);
        }

        ApiRequestor::setHttpClient($client);

        return $client;
    }

    /**
     * A checkout session as Stripe answers it, both when one is created and when
     * one is retrieved by the identifier the buyer carries back. Unpaid by
     * default, which is what a session looks like before the buyer pays.
     *
     * @return array<string, mixed>
     */
    protected function stripeCheckoutSession(
        string $id = 'cs_test_dundermifflin',
        string $paymentStatus = 'unpaid',
        string $customer = 'cus_dundermifflin',
    ): array {
        return [
            'id' => $id,
            'object' => 'checkout.session',
            'customer' => $customer,
            'mode' => 'payment',
            'status' => $paymentStatus === 'paid' ? 'complete' : 'open',
            'payment_status' => $paymentStatus,
            'currency' => 'eur',
            'amount_total' => 19900,
            'url' => 'https://checkout.stripe.com/c/pay/'.$id,
        ];
    }

    /**
     * The catalogue as Stripe lists it, one tagged price per kind asked for.
     *
     * @param  list<string>  $kinds
     * @return array<string, mixed>
     */
    protected function stripePrices(array $kinds = ['monthly', 'yearly', 'lifetime'], string $tag = 'offer'): array
    {
        $recurring = [
            'monthly' => ['interval' => 'month', 'interval_count' => 1, 'usage_type' => 'licensed'],
            'yearly' => ['interval' => 'year', 'interval_count' => 1, 'usage_type' => 'licensed'],
            'lifetime' => null,
        ];

        $amounts = ['monthly' => 900, 'yearly' => 9000, 'lifetime' => 19900];
        $dollars = ['monthly' => 1000, 'yearly' => 10000, 'lifetime' => 21900];

        return [
            'object' => 'list',
            'url' => '/v1/prices',
            'has_more' => false,
            'data' => array_map(fn (string $kind): array => [
                'id' => "price_{$kind}",
                'object' => 'price',
                'active' => true,
                'product' => ['id' => 'prod_full_access', 'object' => 'product', 'metadata' => []],
                'currency' => 'eur',
                'unit_amount' => $amounts[$kind],
                'type' => $kind === 'lifetime' ? 'one_time' : 'recurring',
                'recurring' => $recurring[$kind],
                'metadata' => [$tag => $kind],
                'currency_options' => [
                    'eur' => ['unit_amount' => $amounts[$kind]],
                    'usd' => ['unit_amount' => $dollars[$kind]],
                ],
            ], $kinds),
        ];
    }

    /** @return array<string, mixed> */
    protected function stripeCustomer(string $email = 'accounts@dundermifflin.com'): array
    {
        return [
            'id' => 'cus_dundermifflin',
            'object' => 'customer',
            'email' => $email,
            'name' => 'Dunder Mifflin',
            'address' => null,
            'invoice_settings' => ['default_payment_method' => null],
        ];
    }

    /**
     * A subscription as Stripe answers it, which is what `swap()`, `cancel()` and `resume()` read.
     *
     * @return array<string, mixed>
     */
    protected function stripeSubscription(string $priceId = 'price_monthly', string $status = 'active'): array
    {
        return [
            'id' => 'sub_dundermifflin',
            'object' => 'subscription',
            'status' => $status,
            'customer' => 'cus_dundermifflin',
            'cancel_at_period_end' => false,
            'latest_invoice' => null,
            'items' => [
                'object' => 'list',
                'has_more' => false,
                'data' => [[
                    'id' => 'si_dundermifflin',
                    'object' => 'subscription_item',
                    'quantity' => 1,
                    'current_period_end' => CarbonImmutable::parse('2026-10-07 12:00:00')->getTimestamp(),
                    'price' => [
                        'id' => $priceId,
                        'object' => 'price',
                        'product' => 'prod_full_access',
                        'recurring' => ['interval' => 'month', 'interval_count' => 1, 'usage_type' => 'licensed'],
                    ],
                ]],
            ],
        ];
    }

    /**
     * The `invoice.paid` payload, whose shape decides what a payment unlocked: an invoice with no
     * subscription behind it is the one time purchase.
     *
     * @return array<string, mixed>
     */
    protected function paidInvoicePayload(string $customerId = 'cus_dundermifflin', ?string $subscriptionId = null, ?CarbonImmutable $paidAt = null): array
    {
        $paidAt ??= CarbonImmutable::parse('2026-09-07 12:00:00');

        return [
            'id' => 'in_'.Str::lower(Str::random(16)),
            'object' => 'invoice',
            'customer' => $customerId,
            'number' => 'DM-'.Str::upper(Str::random(6)),
            'status' => 'paid',
            'currency' => 'eur',
            'total' => 19900,
            'total_taxes' => [['amount' => 3317]],
            'hosted_invoice_url' => 'https://invoice.stripe.com/i/test_dundermifflin',
            'invoice_pdf' => 'https://pay.stripe.com/invoice/test_dundermifflin/pdf',
            'status_transitions' => ['paid_at' => $paidAt->getTimestamp()],
            'created' => $paidAt->getTimestamp(),
            'parent' => $subscriptionId === null ? null : [
                'type' => 'subscription_details',
                'subscription_details' => ['subscription' => $subscriptionId, 'metadata' => null],
            ],
        ];
    }

    /** Give the account a subscription the way a landed webhook would have left it. */
    protected function subscribeAccount(Account $account, string $priceId = 'price_monthly', string $status = 'active', ?CarbonImmutable $endsAt = null, string $stripeId = 'sub_dundermifflin'): Subscription
    {
        $account->forceFill(['stripe_id' => 'cus_dundermifflin'])->save();

        $subscription = $account->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => $stripeId,
            'stripe_status' => $status,
            'stripe_price' => $priceId,
            'quantity' => 1,
            'ends_at' => $endsAt,
        ]);

        $subscription->items()->create([
            'stripe_id' => 'si_'.$stripeId,
            'stripe_product' => 'prod_full_access',
            'stripe_price' => $priceId,
            'quantity' => 1,
        ]);

        return $subscription;
    }

    protected function assignUserToAccount(User $user, Account $account): User
    {
        $user->account_id = $account->id;
        $user->save();

        return $user;
    }

    protected function reminderAbout(User $user, ?Contact $contact = null, ?Field $field = null): Reminder
    {
        $contact ??= Contact::factory()->create(['account_id' => $user->account_id]);
        $field ??= Field::factory()->recurringDate()->create(['account_id' => $user->account_id]);

        $value = FieldValue::factory()->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => null,
            'value_date' => '1984-02-03',
        ]);

        return Reminder::factory()->for($value, 'fieldValue')->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'remindable_type' => Contact::class,
            'remindable_id' => $contact->id,
            'field_id' => $field->id,
            'created_by_user_id' => $user->id,
        ]);
    }
}
