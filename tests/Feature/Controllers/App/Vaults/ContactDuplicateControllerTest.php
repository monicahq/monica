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
    public function it_says_so_when_nothing_looks_doubled(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('vaults.duplicates.index', $vault));

        $response->assertStatus(200);
        $response->assertSee('Nothing looks doubled');
    }

    #[Test]
    public function it_opens_when_one_of_a_pair_has_been_deleted(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $candidate = $this->candidateIn($vault, 'Jim', 'Jimmy');
        $candidate->otherContact->delete();

        $response = $this->actingAs($user)->get(route('vaults.duplicates.index', $vault));

        $response->assertStatus(200);
        $response->assertDontSee('Jimmy Halpert');
        $response->assertSee('Nothing looks doubled');
    }

    #[Test]
    public function it_lists_nothing_from_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->candidateIn($elsewhere, 'Jim', 'Jimmy');

        $response = $this->actingAs($user)->get(route('vaults.duplicates.index', $vault));

        $response->assertStatus(200);
        $response->assertDontSee('Jimmy Halpert');
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

    #[Test]
    public function it_does_not_find_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $stranger = Vault::factory()->create();

        $this->actingAs($user)
            ->get(route('vaults.duplicates.index', $stranger))
            ->assertStatus(404);
    }

    #[Test]
    public function a_visitor_is_sent_to_sign_in(): void
    {
        $vault = Vault::factory()->create();

        $this->get(route('vaults.duplicates.index', $vault))
            ->assertRedirect(route('auth.login.new'));
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
