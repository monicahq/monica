<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Jobs\RebuildSearchIndex;
use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SearchIndexRebuildControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_the_rebuild_of_an_index(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->administrator())
            ->post(route('admin.search.rebuild.create', ['kind' => 'contacts']));

        $response->assertRedirect(route('admin.search.index'));
        $response->assertSessionHas('status', 'The rebuild has started.');
        Queue::assertPushed(RebuildSearchIndex::class);
    }

    #[Test]
    public function it_asks_for_a_credential_first(): void
    {
        Queue::fake();
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);

        $this->actingAs($michael)
            ->post(route('admin.search.rebuild.create', ['kind' => 'contacts']))
            ->assertRedirect(route('auth.confirmPassword.new'));

        Queue::assertNotPushed(RebuildSearchIndex::class);
    }

    #[Test]
    public function it_answers_not_found_for_an_index_that_does_not_exist(): void
    {
        $this->actingAs($this->administrator())
            ->post('admin/search/dundie-awards/rebuild')
            ->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_for_somebody_who_is_not_an_administrator(): void
    {
        Queue::fake();
        $this->session(['auth.password_confirmed_at' => time()]);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.search.rebuild.create', ['kind' => 'contacts']))
            ->assertNotFound();

        Queue::assertNotPushed(RebuildSearchIndex::class);
    }

    private function administrator(): User
    {
        $michael = User::factory()->create();
        Administrator::factory()->create(['user_id' => $michael->id]);
        $this->session(['auth.password_confirmed_at' => time()]);

        return $michael;
    }
}
