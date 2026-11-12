<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Models\EmailSent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Resend\Laravel\Events\EmailBounced;
use Tests\TestCase;

class RecordEmailBouncedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_that_the_email_came_back(): void
    {
        config(['resend.webhook.secret' => 'whsec_'.base64_encode('scranton')]);
        EmailSent::factory()->create(['uuid' => 'a1b2c3']);

        EmailBounced::dispatch([
            'type' => 'email.bounced',
            'created_at' => '2026-09-04T10:00:00.000Z',
            'data' => ['email_id' => 'a1b2c3'],
        ]);

        $this->assertDatabaseHas('emails_sent', [
            'uuid' => 'a1b2c3',
            'bounced_at' => '2026-09-04 10:00:00',
        ]);
    }
}
