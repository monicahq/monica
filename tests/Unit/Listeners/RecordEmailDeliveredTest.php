<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Models\EmailSent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Resend\Laravel\Events\EmailDelivered;
use Tests\TestCase;

class RecordEmailDeliveredTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_that_the_email_arrived(): void
    {
        config(['resend.webhook.secret' => 'whsec_'.base64_encode('scranton')]);
        EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        EmailDelivered::dispatch([
            'type' => 'email.delivered',
            'created_at' => '2026-09-04T10:00:00.000Z',
            'data' => ['email_id' => 'a1b2c3'],
        ]);

        $this->assertDatabaseHas('emails_sent', [
            'uuid' => 'a1b2c3',
            'delivered_at' => '2026-09-04 10:00:00',
        ]);
    }
}
