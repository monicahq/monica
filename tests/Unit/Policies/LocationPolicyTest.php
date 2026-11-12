<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Location;
use App\Models\PossessionLocationStay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocationPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_deleting_an_empty_location(): void
    {
        $user = User::factory()->create();
        $shelf = Location::factory()->create();

        $this->assertTrue(Gate::forUser($user)->allows('delete', $shelf));
    }

    #[Test]
    public function it_refuses_deleting_a_location_holding_others_and_says_how_many(): void
    {
        $user = User::factory()->create();
        $warehouse = Location::factory()->create();
        Location::factory()->inside($warehouse)->count(2)->create();

        $response = Gate::forUser($user)->inspect('delete', $warehouse);

        $this->assertTrue($response->denied());
    }

    #[Test]
    public function it_refuses_deleting_a_location_a_possession_stayed_at_and_says_how_many(): void
    {
        $user = User::factory()->create();
        $stay = PossessionLocationStay::factory()->create(['left_on' => '2024-06-04']);

        $response = Gate::forUser($user)->inspect('delete', $stay->location);

        $this->assertTrue($response->denied());
        $this->assertEquals('1 stay of a possession is here. Correct or delete it first.', $response->message());
    }

    #[Test]
    public function it_refuses_deleting_when_nobody_is_signed_in(): void
    {
        $shelf = Location::factory()->create();

        $this->assertTrue(Gate::inspect('delete', $shelf)->denied());
    }
}
