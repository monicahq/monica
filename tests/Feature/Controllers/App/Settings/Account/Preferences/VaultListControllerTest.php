<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Preferences;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultListControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hides_the_list_of_vaults(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.vaultList.update'), [
            'shows_vault_list' => '0',
        ]);

        $response->assertRedirect(route('settings.preferences.index'));
        $response->assertSessionHas('status', 'You now go straight to your default vault.');

        $this->assertFalse($user->refresh()->shows_vault_list);
    }

    #[Test]
    public function it_refuses_a_request_without_a_value(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('settings.vaultList.update'));

        $response->assertSessionHasErrors('shows_vault_list');

        $this->assertTrue($user->refresh()->shows_vault_list);
    }
}
