<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FullAccessOfferControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hides_the_offer_and_returns_to_the_screen_the_reader_was_on(): void
    {
        $user = User::factory()->create(['first_name' => 'Dwight']);

        $response = $this->actingAs($user)
            ->from(route('vaults.index'))
            ->put(route('fullAccessOffer.update'));

        $response->assertRedirect(route('vaults.index'));
        $this->assertNotNull($user->refresh()->full_access_offer_dismissed_at);
    }
}
