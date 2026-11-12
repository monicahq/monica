<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Vaults;

use App\Models\Contact;
use App\Models\Relationship;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImmediateFamilyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vault $vault;

    private Contact $michael;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id]);
        $this->michael = $this->personIn('Michael', 'Scott');
    }

    #[Test]
    public function it_names_the_block_and_offers_the_way_to_record_a_relationship(): void
    {
        $this->parentage($this->michael, $this->personIn('Pam', 'Beesly'));

        $this->overview()
            ->assertSee(__('Immediate family'))
            ->assertSee(route('contacts.relationships.new', ['vault' => $this->vault, 'contact' => $this->michael]));
    }

    #[Test]
    public function it_does_not_name_somebody_whose_partnership_ended(): void
    {
        $jan = $this->personIn('Jan', 'Levinson');

        Relationship::factory()->romantic()->ended()->create([
            'vault_id' => $this->vault->id,
            'account_id' => $this->vault->account_id,
            'contact_a_id' => $this->michael->id,
            'contact_b_id' => $jan->id,
        ]);

        $this->overview()->assertDontSee('Jan Levinson');
    }

    #[Test]
    public function it_says_what_would_land_there_when_nothing_is_written_down(): void
    {
        $this->overview()
            ->assertSee(__('Immediate family'))
            ->assertSee(__(':name’s children and current partner land here, once you write down who they are. This says nothing about the family :name has.', ['name' => 'Michael']), escape: false)
            ->assertSee(__('Add a relationship'))
            ->assertDontSee(__('No children recorded'));
    }

    private function overview(): TestResponse
    {
        return $this->actingAs($this->user)
            ->get(route('contacts.show', ['vault' => $this->vault, 'contact' => $this->michael]))
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

    private function parentage(Contact $parent, Contact $child): void
    {
        Relationship::factory()->parentage()->create([
            'vault_id' => $this->vault->id,
            'account_id' => $this->vault->account_id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);
    }
}
