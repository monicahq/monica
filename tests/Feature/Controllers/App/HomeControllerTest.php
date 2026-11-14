<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_somebody_who_is_not_signed_in_to_the_sign_in_screen(): void
    {
        $this->get(route('home.index'))->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_sends_a_visitor_to_the_waiting_list_while_registration_is_closed(): void
    {
        config([
            'monica.preview_list_enabled' => true,
            'monica.registration_enabled' => false,
        ]);

        $this->get(route('home.index'))->assertRedirect(route('auth.previewList.new'));
    }

    /** @return array<string, array{bool, bool}> */
    public static function combinationsWithoutThePreviewList(): array
    {
        return [
            'the list is off and registration closed' => [false, false],
            'the list is on and registration open' => [true, true],
            'the list is off and registration open' => [false, true],
        ];
    }

    #[Test]
    #[DataProvider('combinationsWithoutThePreviewList')]
    public function it_sends_a_visitor_to_sign_in_in_every_other_case(bool $listEnabled, bool $registrationEnabled): void
    {
        config([
            'monica.preview_list_enabled' => $listEnabled,
            'monica.registration_enabled' => $registrationEnabled,
        ]);

        $this->get(route('home.index'))->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_sends_somebody_who_has_not_been_welcomed_to_the_welcome_screen(): void
    {
        $user = User::factory()->notWelcomed()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('home.index'))
            ->assertRedirect(route('welcome.index'));
    }

    #[Test]
    public function it_sends_somebody_who_has_been_welcomed_to_their_vaults(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('home.index'))
            ->assertRedirect(route('vaults.index'));
    }
}
