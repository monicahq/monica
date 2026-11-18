<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CheckStripeConnection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckStripeConnectionTest extends TestCase
{
    #[Test]
    public function it_reports_not_configured_and_sends_nothing_without_a_secret_key(): void
    {
        Http::fake();
        config(['cashier.secret' => null, 'cashier.key' => 'pk_test_dundermifflin', 'cashier.webhook.secret' => null]);

        $this->assertSame('notConfigured', new CheckStripeConnection()->execute()['connection']);
        Http::assertNothingSent();
    }

    #[Test]
    public function it_reports_reachable_when_stripe_answers(): void
    {
        $this->fakeEndpoints([]);
        config(['cashier.secret' => 'sk_test_scranton']);

        $status = new CheckStripeConnection()->execute();

        $this->assertSame('reachable', $status['connection']);
        $this->assertTrue($status['endpointsReadable']);
        Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer sk_test_scranton'));
    }

    #[Test]
    public function it_reports_refused_when_stripe_answers_401(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['error' => ['type' => 'invalid_request_error']], 401)]);
        config(['cashier.secret' => 'sk_test_scranton']);

        $this->assertSame('refused', new CheckStripeConnection()->execute()['connection']);
    }

    #[Test]
    public function it_reports_restricted_when_stripe_answers_403(): void
    {
        Http::fake(['api.stripe.com/*' => Http::response(['error' => ['type' => 'invalid_request_error']], 403)]);
        config(['cashier.secret' => 'rk_live_scranton']);

        $status = new CheckStripeConnection()->execute();

        $this->assertSame('reachable', $status['connection']);
        $this->assertFalse($status['endpointsReadable']);
        $this->assertSame([], $status['missingEvents']);
    }

    #[Test]
    public function it_reports_unreachable_and_never_returns_a_key_value(): void
    {
        config([
            'cashier.key' => 'pk_test_threat_level_midnight',
            'cashier.secret' => 'sk_test_threat_level_midnight',
            'cashier.webhook.secret' => 'whsec_threat_level_midnight',
        ]);

        $this->fakeEndpoints([]);
        $reachable = json_encode(new CheckStripeConnection()->execute());

        Http::fake(['api.stripe.com/*' => fn () => throw new ConnectionException('refused for sk_test_threat_level_midnight')]);
        $unreachable = json_encode(new CheckStripeConnection()->execute());

        $this->assertStringContainsString('"connection":"unreachable"', (string) $unreachable);

        foreach ([$reachable, $unreachable] as $output) {
            $this->assertStringNotContainsString('threat_level_midnight', (string) $output);
        }
    }

    #[Test]
    public function it_lists_the_endpoints_at_this_instance_address(): void
    {
        $this->fakeEndpoints([$this->endpoint(route('cashier.webhook'), ['*'])]);

        $endpoints = new CheckStripeConnection()->execute()['endpoints'];

        $this->assertSame([['url' => route('cashier.webhook'), 'isThisInstance' => true, 'isEnabled' => true]], $endpoints);
    }

    #[Test]
    public function it_names_every_kind_no_enabled_endpoint_of_this_instance_covers(): void
    {
        $everyKindButInvoicePaid = array_values(array_diff(config('cashier.webhook.events'), ['invoice.paid']));
        $this->fakeEndpoints([$this->endpoint(route('cashier.webhook'), $everyKindButInvoicePaid)]);

        $this->assertSame(['invoice.paid'], new CheckStripeConnection()->execute()['missingEvents']);
    }

    #[Test]
    public function it_treats_a_wildcard_endpoint_as_covering_every_kind(): void
    {
        $this->fakeEndpoints([$this->endpoint(route('cashier.webhook'), ['*'])]);

        $this->assertSame([], new CheckStripeConnection()->execute()['missingEvents']);
    }

    #[Test]
    public function it_ignores_a_disabled_endpoint_when_naming_what_is_missing(): void
    {
        $this->fakeEndpoints([$this->endpoint(route('cashier.webhook'), ['*'], 'disabled')]);

        $status = new CheckStripeConnection()->execute();

        $this->assertFalse($status['endpoints'][0]['isEnabled']);
        $this->assertSame(config('cashier.webhook.events'), $status['missingEvents']);
    }

    #[Test]
    public function it_does_not_count_an_endpoint_at_another_address_and_hides_its_address(): void
    {
        $this->fakeEndpoints([$this->endpoint('https://staging.dundermifflin.test/stripe/webhook', ['*'])]);

        $status = new CheckStripeConnection()->execute();

        $this->assertSame([['url' => null, 'isThisInstance' => false, 'isEnabled' => true]], $status['endpoints']);
        $this->assertSame(config('cashier.webhook.events'), $status['missingEvents']);
    }

    /** @param  list<array<string, mixed>>  $endpoints */
    private function fakeEndpoints(array $endpoints): void
    {
        config(['cashier.secret' => config('cashier.secret') ?? 'sk_test_scranton']);

        Http::fake(['api.stripe.com/v1/webhook_endpoints*' => Http::response(['object' => 'list', 'data' => $endpoints])]);
    }

    /**
     * @param  list<string>  $events
     * @return array<string, mixed>
     */
    private function endpoint(string $url, array $events, string $status = 'enabled'): array
    {
        return ['id' => 'we_dundermifflin', 'object' => 'webhook_endpoint', 'url' => $url, 'status' => $status, 'enabled_events' => $events];
    }
}
