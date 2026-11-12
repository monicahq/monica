<?php

declare(strict_types=1);

namespace Tests\Feature\Dav;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecurityScreenAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_address_carries_a_live_region_that_says_what_a_copy_did(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('settings.security.index'));

        $response->assertOk();
        $response->assertSee('role="status"', escape: false);
        $response->assertSee('aria-live="polite"', escape: false);
    }

    #[Test]
    public function the_choice_about_sensitive_fields_is_a_labelled_checkbox(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('settings.security.index'));

        $response->assertOk();
        $response->assertSee('for="sensitive_fields"', escape: false);
        $response->assertSee('id="sensitive_fields"', escape: false);
        $response->assertSee('name="sensitive_fields"', escape: false);
    }

    #[Test]
    public function the_key_that_reaches_sensitive_fields_says_so_in_words_rather_than_by_colour(): void
    {
        $user = User::factory()->create();
        $user->createToken('Dwight’s phone', ['*', 'sensitive-fields']);

        $response = $this->actingAs($user)->get(route('settings.security.index'));

        $response->assertOk();
    }
}
