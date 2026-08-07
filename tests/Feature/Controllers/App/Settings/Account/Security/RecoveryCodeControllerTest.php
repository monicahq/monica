<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecoveryCodeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hands_out_a_new_set_of_recovery_codes(): void
    {
        Queue::fake();

        $user = User::factory()->twoFactor()->create();

        $response = $this->actingAs($user)->post(route('settings.recoveryCodes.create'));

        $response->assertRedirect(route('settings.security.index'));
        $response->assertSessionHas('status', 'Your recovery codes are new.');

        $this->assertCount(8, $user->refresh()->two_factor_recovery_codes);
    }

    #[Test]
    public function it_is_not_found_when_two_factor_authentication_is_not_in_use(): void
    {
        $user = User::factory()->create(['two_factor_confirmed_at' => null]);

        $response = $this->actingAs($user)->post(route('settings.recoveryCodes.create'));

        $response->assertNotFound();
    }
}
