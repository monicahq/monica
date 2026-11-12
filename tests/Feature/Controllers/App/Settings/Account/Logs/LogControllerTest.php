<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Logs;

use App\Enums\UserActionEnum;
use App\Models\Log;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LogControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_logs_screen(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        Log::factory()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'action' => UserActionEnum::UserProfileUpdate->value,
            'parameters' => ['name' => 'Michael Scott'],
        ]);

        $response = $this->actingAs($user)->get(route('settings.logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Michael Scott', escape: false);
        $response->assertSee('user_profile_updated', escape: false);
        $response->assertSee('Updated the profile of Michael Scott', escape: false);
    }
}
