<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Jobs\DetectDuplicateContacts;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactDuplicateSearchControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_a_search_for_the_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->post(route('vaults.duplicates.search.create', $vault));

        $response->assertRedirect(route('vaults.duplicates.index', $vault));
        $response->assertSessionHas('status');
        Queue::assertPushedOn('low', DetectDuplicateContacts::class);
    }

    #[Test]
    public function it_offers_the_search_on_the_screen(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->get(route('vaults.duplicates.index', $vault))
            ->assertSee(route('vaults.duplicates.search.create', $vault));
    }

    #[Test]
    public function it_does_not_search_a_vault_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $stranger = Vault::factory()->create();

        $this->actingAs($user)
            ->post(route('vaults.duplicates.search.create', $stranger))
            ->assertStatus(404);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function a_visitor_is_sent_to_sign_in(): void
    {
        Queue::fake();

        $vault = Vault::factory()->create();

        $this->post(route('vaults.duplicates.search.create', $vault))
            ->assertRedirect(route('auth.login.new'));

        Queue::assertNothingPushed();
    }
}
