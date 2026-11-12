<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Docs;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocsControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_portal_to_a_visitor_who_is_not_signed_in(): void
    {
        $response = $this->get(route('docs.index'));

        $response->assertOk();
        $response->assertSee(route('docs.api.index'), escape: false);
        $response->assertSee(route('auth.register.new'), escape: false);
        $response->assertSee(route('auth.login.new'), escape: false);
    }

    #[Test]
    public function it_does_not_offer_to_sign_in_to_somebody_signed_in(): void
    {
        $user = User::factory()->create(['first_name' => 'Dwight']);

        $response = $this->actingAs($user)->get(route('docs.index'));

        $response->assertOk();
        $response->assertDontSee(route('auth.login.new'), escape: false);
    }
}
