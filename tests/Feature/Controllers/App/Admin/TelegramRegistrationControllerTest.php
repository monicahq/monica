<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TelegramRegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Http::preventStrayRequests();
        URL::forceScheme('https');
        URL::forceRootUrl('https://monica.dundermifflin.com');
    }

    #[Test]
    public function it_registers_the_bot(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true])]);

        $response = $this->actingAs($this->administrator())
            ->post('/admin/configuration/telegram/registration');

        $response->assertRedirect(route('admin.configuration.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseCount('telegram_webhook_registrations', 1);
    }

    #[Test]
    public function it_brings_back_what_telegram_refused(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401)]);

        $response = $this->actingAs($this->administrator())
            ->from('/admin/configuration')
            ->post('/admin/configuration/telegram/registration');

        $response->assertRedirect('/admin/configuration');
        $response->assertSessionHasErrors(['telegram']);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);
        $this->session(['auth.password_confirmed_at' => time()]);

        return $user;
    }
}
