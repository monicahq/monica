<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\ContactDuplicateCandidate;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactDuplicateControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_pairs_of_the_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->candidateIn($vault, 'Jim', 'Jimmy');

        $response = $this->actingAs($user)->get(route('vaults.duplicates.index', $vault));

        $response->assertStatus(200);
        $response->assertSee('Jim Halpert');
        $response->assertSee('Jimmy Halpert');
        $response->assertSee('their names are alike');
    }

    #[Test]
    public function it_draws_the_numbered_pages_under_the_pairs(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        for ($index = 1; $index <= 25; $index++) {
            $this->candidateIn($vault, 'Jim', 'Jimmy');
        }

        $response = $this->actingAs($user)->get(route('vaults.duplicates.index', $vault));

        $response->assertStatus(200);
        $response->assertSee(__('Rows per page'));
        $response->assertSee('aria-current="page"', escape: false);
        $response->assertDontSee('pagination.previous');
    }

    #[Test]
    public function it_says_so_when_nothing_looks_doubled(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('vaults.duplicates.index', $vault));

        $response->assertStatus(200);
        $response->assertSee('Nothing looks doubled');
    }

    #[Test]
    public function it_dismisses_a_pair_for_good(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $candidate = $this->candidateIn($vault, 'Jim', 'Jimmy');

        $response = $this->actingAs($user)->put(
            route('vaults.duplicates.dismissal.update', ['vault' => $vault, 'candidate' => $candidate->id])
        );

        $response->assertRedirect(route('vaults.duplicates.index', $vault));
        $response->assertSessionHas('status');
        $this->assertNotNull($candidate->fresh()->dismissed_at);

        $this->actingAs($user)
            ->get(route('vaults.duplicates.index', $vault))
            ->assertDontSee('Jimmy Halpert');
    }

    #[Test]
    public function it_does_not_find_a_pair_reached_through_the_wrong_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $candidate = $this->candidateIn($elsewhere, 'Jim', 'Jimmy');

        $response = $this->actingAs($user)->put(
            route('vaults.duplicates.dismissal.update', ['vault' => $vault, 'candidate' => $candidate->id])
        );

        $response->assertStatus(404);
    }

    private function candidateIn(Vault $vault, string $first, string $second): ContactDuplicateCandidate
    {
        $one = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => $first, 'last_name' => 'Halpert']);
        $two = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => $second, 'last_name' => 'Halpert']);

        return ContactDuplicateCandidate::factory()->create([
            'contact_id' => min($one->id, $two->id),
            'other_contact_id' => max($one->id, $two->id),
        ]);
    }
}
