<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OverviewControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_to_count_again(): void
    {
        $response = $this->actingAs($this->administrator())->get(route('admin.overview.index'));

        $response->assertSee('Counted at', escape: false);
        $response->assertSeeInOrder(['href="'.route('admin.overview.index').'"', 'Count again'], escape: false);
    }

    private function administrator(): User
    {
        $michael = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott']);
        Administrator::factory()->create(['user_id' => $michael->id]);

        return $michael;
    }
}
