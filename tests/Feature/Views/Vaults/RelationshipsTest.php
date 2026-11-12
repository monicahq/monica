<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Vaults;

use App\Models\Contact;
use App\Models\Relationship;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vault $vault;

    private Contact $judy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id]);
        $this->judy = $this->personIn('Judy', 'Geller');
    }

    #[Test]
    public function it_names_each_group_and_leads_to_each_person(): void
    {
        $ross = $this->personIn('Ross', 'Geller');
        $this->parentage($this->judy, $ross);

        $this->tab()
            ->assertSee('Ross Geller')
            ->assertSee(route('contacts.show', ['vault' => $this->vault, 'contact' => $ross]));
    }

    #[Test]
    public function the_manage_chip_says_whether_it_is_pressed(): void
    {
        $this->parentage($this->judy, $this->personIn('Ross', 'Geller'));

        $this->tab()
            ->assertSee('x-data="{ manage: false }"', escape: false)
            ->assertSee('x-bind:aria-pressed', escape: false);
    }

    #[Test]
    public function the_form_that_corrects_a_link_shows_what_it_says_today(): void
    {
        $relationship = $this->parentage($this->judy, $this->personIn('Ross', 'Geller'));

        $this->actingAs($this->user)
            ->get(route('contacts.relationships.edit', [
                'vault' => $this->vault,
                'contact' => $this->judy,
                'relationship' => $relationship,
            ]))
            ->assertStatus(200)
            ->assertSee('name="relationship"', escape: false)
            ->assertSee('name="has_ended"', escape: false)
            ->assertSee('name="started_on"', escape: false)
            ->assertSee('name="ended_on"', escape: false);
    }

    private function tab(): TestResponse
    {
        return $this->actingAs($this->user)
            ->get(route('contacts.show', [
                'vault' => $this->vault,
                'contact' => $this->judy,
                'page' => ContactViewModel::PAGE_RELATIONSHIPS,
            ]))
            ->assertStatus(200);
    }

    private function personIn(string $first, string $last): Contact
    {
        return Contact::factory()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'first_name' => $first,
            'last_name' => $last,
        ]);
    }

    private function parentage(Contact $parent, Contact $child): Relationship
    {
        return Relationship::factory()->parentage()->create([
            'vault_id' => $this->vault->id,
            'account_id' => $this->vault->account_id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);
    }
}
