<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchEngineControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_search_engine_and_offers_a_rebuild(): void
    {
        config(['scout.driver' => 'database']);
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        $this->actingAs($michael)
            ->get(route('admin.search.index'))
            ->assertOk()
            ->assertSee('Which search engine this instance uses')
            ->assertSee(route('admin.search.rebuild.create', ['kind' => 'contacts']), escape: false);
    }
}
