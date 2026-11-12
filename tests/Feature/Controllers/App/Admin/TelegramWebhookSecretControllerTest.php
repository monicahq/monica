<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TelegramWebhookSecretControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_generates_a_secret(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);
        $this->session(['auth.password_confirmed_at' => time()]);

        $response = $this->actingAs($user)->post('/admin/configuration/telegram/secret');

        $response->assertRedirect(route('admin.configuration.index'));
        $response->assertSessionHas('status', 'A new secret is set. It is never shown back.');
        $this->assertDatabaseHas('instance_settings', ['name' => 'telegram_bot_webhook_secret']);
    }
}
