<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use ReflectionNamedType;
use Tests\TestCase;

class HandleStripeWebhookTest extends TestCase
{
    #[Test]
    public function every_stripe_listener_is_retried_before_it_is_abandoned(): void
    {
        $listeners = $this->stripeListeners();

        $this->assertNotEmpty($listeners);

        foreach ($listeners as $class) {
            $listener = new $class;

            $this->assertGreaterThan(1, $listener->tries, $class);
            $this->assertNotEmpty($listener->backoff, $class);
        }
    }

    #[Test]
    public function every_stripe_listener_ignores_an_event_it_does_not_handle(): void
    {
        Notification::fake();

        foreach ($this->stripeListeners() as $class) {
            $type = new ReflectionMethod($class, 'handle')->getParameters()[0]->getType();

            $this->assertInstanceOf(ReflectionNamedType::class, $type, $class);

            $eventClass = $type->getName();

            DB::flushQueryLog();
            DB::enableQueryLog();

            new $class()->handle(new $eventClass([
                'type' => 'customer.updated',
                'data' => ['object' => ['id' => 'cus_dundermifflin', 'customer' => 'cus_dundermifflin']],
            ]));

            $this->assertSame([], DB::getQueryLog(), $class);
        }

        Notification::assertNothingSent();
    }

    /** @return list<class-string> */
    private function stripeListeners(): array
    {
        return array_map(
            fn (string $path): string => 'App\\Listeners\\'.basename($path, '.php'),
            glob(app_path('Listeners/HandleStripe*.php')) ?: [],
        );
    }
}
