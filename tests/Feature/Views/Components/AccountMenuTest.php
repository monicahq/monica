<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountMenuTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_a_menu_from_the_picture_of_whoever_is_signed_in(): void
    {
        $user = User::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);

        $rendered = $this->actingAs($user)->blade('<x-account-menu :user="$user" />', ['user' => $user]);

        $rendered->assertSee('PB');
        $rendered->assertSee('aria-haspopup="menu"', escape: false);
        $rendered->assertSee('aria-label="Account"', escape: false);
        $rendered->assertSee('role="menu"', escape: false);
    }

    #[Test]
    public function it_leads_to_the_account_settings(): void
    {
        $user = User::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);

        $rendered = $this->actingAs($user)->blade('<x-account-menu :user="$user" />', ['user' => $user]);

        $rendered->assertSee('href="'.route('settings.index').'"', escape: false);
        $rendered->assertSee('Account settings');
    }

    #[Test]
    public function it_signs_out_from_a_real_button_in_the_menu(): void
    {
        $user = User::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);

        $rendered = $this->actingAs($user)->blade('<x-account-menu :user="$user" />', ['user' => $user]);

        $rendered->assertSee('action="'.route('auth.login.destroy').'"', escape: false);
        $rendered->assertSee('method="post"', escape: false);
        $rendered->assertSee('name="_token"', escape: false);
        $rendered->assertSee('type="submit"', escape: false);
        $rendered->assertSee('role="menuitem"', escape: false);
        $rendered->assertSee('Sign out');
    }
}
