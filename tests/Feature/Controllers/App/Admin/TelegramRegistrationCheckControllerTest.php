<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TelegramRegistrationCheckControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        URL::forceScheme('https');
        URL::forceRootUrl('https://monica.dundermifflin.com');
    }

    #[Test]
    public function it_brings_back_what_telegram_holds(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => [
            'url' => 'https://monica.dundermifflin.com/telegram/webhook',
        ]])]);

        $response = $this->actingAs($this->administrator())->post('/admin/configuration/telegram/check');

        $response->assertRedirect(route('admin.configuration.index'));
        $response->assertSessionHas('telegram_check', [
            'url' => 'https://monica.dundermifflin.com/telegram/webhook',
            'ok' => true,
            'matches' => true,
            'lastError' => null,
        ]);
    }

    #[Test]
    public function it_says_telegram_could_not_be_reached(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $response = $this->actingAs($this->administrator())
            ->from('/admin/configuration')
            ->post('/admin/configuration/telegram/check');

        $response->assertRedirect('/admin/configuration');
        $response->assertSessionHasErrors(['telegram']);
    }

    #[Test]
    public function it_shows_the_answer_on_the_configuration_screen(): void
    {
        $response = $this->actingAs($this->administrator())
            ->withSession(['telegram_check' => ['url' => 'https://stamford.dundermifflin.com/telegram/webhook', 'matches' => false, 'lastError' => '403 Forbidden']])
            ->get('/admin/configuration');

        $response->assertSee('https://stamford.dundermifflin.com/telegram/webhook', false);
        $response->assertSee('403 Forbidden');
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
