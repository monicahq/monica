<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Listeners\RecordEmailFailed;
use App\Models\EmailSent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Resend\Laravel\Events\EmailFailed;
use Tests\TestCase;

class RecordEmailFailedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_send_that_never_happened_as_a_bounce(): void
    {
        config(['resend.webhook.secret' => 'whsec_'.base64_encode('scranton')]);
        EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        EmailFailed::dispatch([
            'type' => 'email.failed',
            'created_at' => '2026-09-04T10:00:00.000Z',
            'data' => ['email_id' => 'a1b2c3'],
        ]);

        $this->assertDatabaseHas('emails_sent', [
            'uuid' => 'a1b2c3',
            'bounced_at' => '2026-09-04 10:00:00',
        ]);
    }

    #[Test]
    public function it_is_the_listener_the_provider_event_reaches(): void
    {
        $this->assertContains(
            RecordEmailFailed::class.'@handle',
            array_map(
                fn (object|string $listener): string => is_string($listener) ? $listener : $listener::class,
                app('events')->getRawListeners()[EmailFailed::class] ?? [],
            ),
        );
    }
}
