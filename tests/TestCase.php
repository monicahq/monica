<?php

declare(strict_types=1);

namespace Tests;

use App\Actions\AddSectionToStructureVersion;
use App\Actions\CreateStructureVersionDraft;
use App\Actions\PublishStructureVersion;
use App\Actions\ReplaceLifeEventTypeFields;
use App\Enums\FieldTypeEnum;
use App\Enums\SsoProviderEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Account;
use App\Models\Contact;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\Reminder;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithCachedConfig;
use Illuminate\Foundation\Testing\WithCachedRoutes;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Laravel\Passport\Client;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Laravel\Pennant\Feature;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\MeilisearchEngine;
use Meilisearch\Contracts\TasksQuery;
use Override;
use Stripe\ApiRequestor;
use Tests\Fixtures\Scout\RecordingEngine;
use Tests\Fixtures\Stripe\FakeStripeClient;

abstract class TestCase extends BaseTestCase
{
    use WithCachedConfig;
    use WithCachedRoutes;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->clearConfig();
        Account::forgetAddressPreferences();

        // A failed sign in otherwise waits 200 ms, so that its duration reveals nothing to an attacker.
        config(['auth.timebox_duration' => 0]);

        // The test client parses addresses with parse_url, which turns the bytes a UTF-8 locale on macOS
        // calls control characters into underscores and so breaks addresses written in another alphabet.
        setlocale(LC_CTYPE, 'C');

        // The capabilities that ship withdrawn are on for the suite. A test of a gate switches its flag off.
        foreach (['carddav', 'file-upload', 'groups', 'sms-notifications', 'vcard-export'] as $flag) {
            Feature::define($flag, true);
        }
    }

    #[Override]
    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);

        parent::tearDown();
    }

    /**
     * Put the instance in a known state: no provider signs anybody in, whatever the environment the
     * suite runs in supplies. A test that needs one gives it credentials of its own with `config()`.
     */
    private function clearConfig(): void
    {
        foreach (SsoProviderEnum::values() as $provider) {
            config([
                "services.{$provider}.client_id" => null,
                "services.{$provider}.client_secret" => null,
            ]);
        }
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
            'payment_intent' => 'pi_dundermifflin',
        ]);
        $client->respondTo('invoice_payments*', $this->stripeInvoicePayments('in_dundermifflin'));
        $client->respondTo('subscriptions*', $this->stripeSubscription());
        $client->respondTo('subscription_items*', [
            'id' => 'si_dundermifflin',
            'object' => 'subscription_item',
            'current_period_end' => $this->stripePeriodEnd()->getTimestamp(),
            'current_period_start' => $this->stripePeriodEnd()->subMonth()->getTimestamp(),
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

    /**
     * The invoice payments Stripe lists for one payment intent, which is how a charge leads back
     * to its invoice.
     *
     * @return array<string, mixed>
     */
    protected function stripeInvoicePayments(string $invoiceId): array
    {
        return [
            'object' => 'list',
            'url' => '/v1/invoice_payments',
            'has_more' => false,
            'data' => [[
                'id' => "inpay_{$invoiceId}",
                'object' => 'invoice_payment',
                'invoice' => $invoiceId,
            ]],
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
    protected function stripePeriodEnd(): CarbonImmutable
    {
        return CarbonImmutable::today()->addMonth()->setTime(12, 0);
    }

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
                    'current_period_end' => $this->stripePeriodEnd()->getTimestamp(),
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

    /**
     * Put a credential confirmation in the session, given a moment ago, which is what the sensitive
     * routes ask for. Travelling past `auth.password_timeout` in a test expires it.
     */
    protected function withConfirmedPassword(): static
    {
        return $this->withSession(['auth.password_confirmed_at' => time()]);
    }

    /**
     * Send every record saved from here on to an engine that only remembers what it was given, so a
     * test can say which records a change reached. Syncing runs inline, as phpunit.xml turns the
     * queue off for search.
     */
    protected function useRecordingSearchEngine(): RecordingEngine
    {
        $engine = new RecordingEngine;

        resolve(EngineManager::class)->extend('recording', fn (): RecordingEngine => $engine);
        config(['scout.driver' => 'recording']);

        return $engine;
    }

    /**
     * Search against the reference engine, in indexes only this test uses, removed afterwards.
     * Matching, tolerance of typing mistakes and ranking are the engine's, so only a real one can
     * prove them. On a workstation where MEILISEARCH_HOST is not set, or names an engine that does
     * not answer, the test is skipped. On CI it never is: an engine that does not answer there fails.
     */
    protected function useSearchEngine(): void
    {
        $host = (string) getenv('MEILISEARCH_HOST');
        $runsOnContinuousIntegration = getenv('CI') !== false;

        if ($host === '' && ! $runsOnContinuousIntegration) {
            $this->markTestSkipped('Needs the reference search engine: set MEILISEARCH_HOST and MEILISEARCH_KEY.');
        }

        config([
            'scout.driver' => 'meilisearch',
            'scout.prefix' => 'test_'.Str::lower(Str::random(10)).'_',
            'scout.meilisearch.host' => $host,
            'scout.meilisearch.key' => getenv('MEILISEARCH_KEY') ?: null,
        ]);

        /** @var MeilisearchEngine $engine */
        $engine = resolve(EngineManager::class)->engine('meilisearch');

        if (! $engine->isHealthy() && ! $runsOnContinuousIntegration) {
            $this->markTestSkipped("Needs the reference search engine, and none answers at {$host}.");
        }

        $this->artisan('scout:sync-index-settings')->assertSuccessful();
        $this->waitForSearchEngine();

        $this->beforeApplicationDestroyed(function (): void {
            $engine = resolve(EngineManager::class)->engine('meilisearch');

            foreach ($engine->getIndexes()->getResults() as $index) {
                if (str_starts_with($index->getUid(), (string) config('scout.prefix'))) {
                    $engine->deleteIndex($index->getUid());
                }
            }
        });
    }

    /** Wait until the reference engine has applied everything it was sent. */
    protected function waitForSearchEngine(): void
    {
        /** @var MeilisearchEngine $engine */
        $engine = resolve(EngineManager::class)->engine('meilisearch');
        $deadline = microtime(true) + 10;

        while (microtime(true) < $deadline) {
            $pending = $engine->getTasks((new TasksQuery)->setStatuses(['enqueued', 'processing']));

            if ($pending->getTotal() === 0) {
                return;
            }

            usleep(50_000);
        }

        $this->fail('The search engine did not finish indexing within ten seconds.');
    }

    protected function assignUserToAccount(User $user, Account $account): User
    {
        $user->account_id = $account->id;
        $user->save();

        return $user;
    }

    protected function oauthTokenFor(User $user, ?Client $client = null, bool $revoked = false, ?CarbonImmutable $expiresAt = null, ?CarbonImmutable $refreshExpiresAt = null): Token
    {
        $client ??= Client::factory()->asPublic()->create(['name' => 'Schrute Farms Assistant']);

        $token = Token::query()->forceCreate([
            'id' => Str::random(80),
            'user_id' => $user->id,
            'client_id' => $client->id,
            'scopes' => ['mcp:use'],
            'revoked' => $revoked,
            'expires_at' => $expiresAt ?? now()->addDay(),
        ]);

        RefreshToken::query()->forceCreate([
            'id' => Str::random(80),
            'access_token_id' => $token->id,
            'revoked' => false,
            'expires_at' => $refreshExpiresAt ?? now()->addDays(30),
        ]);

        return $token;
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

    /**
     * A life event type of the user's account, in a category of its own, whose published version asks
     * for one ungrouped field under each label given, of the type given.
     *
     * @param  array<string, FieldTypeEnum>  $fields
     */
    protected function lifeEventTypeAsking(User $user, string $name, array $fields = []): LifeEventType
    {
        $lifeEventType = LifeEventType::factory()->create([
            'account_id' => $user->account_id,
            'life_event_category_id' => LifeEventCategory::factory()->create(['account_id' => $user->account_id])->id,
            'name' => $name,
        ]);

        if ($fields === []) {
            return $lifeEventType;
        }

        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $lifeEventType,
            groups: [],
            fields: array_map(fn (string $label, FieldTypeEnum $type): array => [
                'key' => null,
                'group' => '',
                'label' => $label,
                'type' => $type->value,
                'is_required' => false,
                'help_text' => null,
                'settings' => [],
                'choices' => [],
            ], array_keys($fields), array_values($fields)),
        )->execute();

        return $lifeEventType->refresh();
    }

    /**
     * A life event type whose published version adds one section holding the fields the callback
     * writes, which is how an imported type comes to carry a sensitive field or a repeater with
     * fields inside it.
     *
     * @param  Closure(SectionVersion): void  $fields
     */
    protected function lifeEventTypeHolding(User $user, string $name, Closure $fields): LifeEventType
    {
        $lifeEventType = $this->lifeEventTypeAsking($user, $name);

        $draft = new CreateStructureVersionDraft(
            user: $user,
            structureVersion: $lifeEventType->structure->publishedVersion,
        )->execute();

        $sectionVersion = SectionVersion::factory()->create([
            'account_id' => $user->account_id,
            'section_id' => Section::factory()->create(['account_id' => $user->account_id])->id,
        ]);

        $fields($sectionVersion);

        new AddSectionToStructureVersion(user: $user, structureVersion: $draft, sectionVersion: $sectionVersion)->execute();
        new PublishStructureVersion(user: $user, structureVersion: $draft, changeNote: 'Imported.')->execute();

        return $lifeEventType->refresh();
    }

    /**
     * A contact template whose published version places the given sections, each section named
     * after a key of the array and holding a text field under each of its keys.
     *
     * @param  array<string, list<string>>  $sections
     * @return array<string, Section>
     */
    protected function contactTemplateCarrying(User $user, string $name, array $sections): array
    {
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => $name]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $placed = [];

        foreach ($sections as $sectionName => $keys) {
            $section = Section::query()->firstWhere(['account_id' => $user->account_id, 'name' => $sectionName])
                ?? Section::factory()->create(['account_id' => $user->account_id, 'name' => $sectionName]);
            $sectionVersion = SectionVersion::query()->firstWhere('section_id', $section->id)
                ?? SectionVersion::factory()->create(['section_id' => $section->id]);

            foreach ($keys as $key) {
                if (! $sectionVersion->fields()->where('key', $key)->exists()) {
                    Field::factory()->create(['section_version_id' => $sectionVersion->id, 'key' => $key, 'label' => Str::headline($key)]);
                }
            }

            StructureVersionSection::factory()->create([
                'structure_version_id' => $version->id,
                'section_version_id' => $sectionVersion->id,
            ]);

            $placed[$sectionName] = $section;
        }

        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $template->structure->update(['published_version_id' => $version->id]);

        return $placed;
    }

    /**
     * A published template for the entity type, set as its default in the vault, placing one
     * section per key of the array, each holding a field per key of its own, of the type given.
     *
     * @param  array<string, array<string, FieldTypeEnum>>  $sections
     */
    protected function entityDefaultCarrying(Vault $vault, EntityType $entityType, array $sections): StructureVersion
    {
        $template = Template::factory()->forEntity($entityType)->create(['account_id' => $vault->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $sectionPosition = 1;

        foreach ($sections as $sectionName => $fields) {
            $section = Section::factory()->create(['account_id' => $vault->account_id, 'name' => $sectionName]);
            $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
            $position = 1;

            foreach ($fields as $key => $type) {
                Field::factory()->create([
                    'section_version_id' => $sectionVersion->id,
                    'key' => $key,
                    'label' => Str::headline($key),
                    'type' => $type,
                    'position' => $position++,
                ]);
            }

            StructureVersionSection::factory()->create([
                'structure_version_id' => $version->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $sectionPosition++,
            ]);
        }

        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $template->structure->update(['published_version_id' => $version->id]);

        VaultDefaultTemplate::factory()->forEntity($entityType)->create([
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
        ]);

        return $version->refresh();
    }
}
