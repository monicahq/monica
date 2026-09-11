<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Account;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationPreferenceControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $jsonStructure = [
        'type',
        'id',
        'attributes' => [
            'quiet_hours_are_enabled',
            'quiet_hours_start',
            'quiet_hours_end',
            'delivery_mode',
            'digest_time',
            'digest_weekdays',
            'timezone',
        ],
        'links' => ['self'],
    ];

    #[Test]
    public function it_returns_the_quiet_hours_and_the_delivery_mode(): void
    {
        $this->signedIn();

        $response = $this->json('GET', route('api.v1.notificationPreferences.show'));

        $response->assertOk();
        $response->assertJsonStructure(['data' => $this->jsonStructure]);
        $response->assertJsonPath('data.attributes.delivery_mode', 'immediate');
    }

    #[Test]
    public function it_changes_them(): void
    {
        $this->signedIn();

        $response = $this->json('PUT', route('api.v1.notificationPreferences.update'), [
            'quiet_hours_are_enabled' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
            'delivery_mode' => 'digest',
            'digest_time' => '18:00',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.delivery_mode', 'digest');
        $response->assertJsonPath('data.attributes.quiet_hours_are_enabled', true);
    }

    #[Test]
    public function a_daily_summary_needs_an_hour(): void
    {
        $this->signedIn();

        $response = $this->json('PUT', route('api.v1.notificationPreferences.update'), [
            'quiet_hours_are_enabled' => false,
            'delivery_mode' => 'digest',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('delivery_mode');
    }

    #[Test]
    public function it_refuses_a_caller_who_has_not_signed_in(): void
    {
        $this->json('GET', route('api.v1.notificationPreferences.show'))->assertUnauthorized();
    }

    private function signedIn(): User
    {
        $account = Account::factory()->create();
        $user = User::factory()->create(['account_id' => $account->id]);
        NotificationPreference::factory()->create(['account_id' => $account->id]);

        Sanctum::actingAs($user);

        return $user;
    }
}
