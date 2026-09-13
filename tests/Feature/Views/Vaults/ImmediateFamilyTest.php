<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Vaults;

use App\Enums\DefaultFieldEnum;
use App\Models\Contact;
use App\Models\FieldValue;
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
    public function it_lists_the_children_under_their_own_heading_and_links_to_each_one(): void
    {
        $child = $this->personIn('Pam', 'Beesly');
        $this->parentage($this->michael, $child);

        $this->overview()
            ->assertSee(__('Children'))
            ->assertSee('Pam Beesly')
            ->assertSee(route('contacts.show', ['vault' => $this->vault, 'contact' => $child]));
    }

    #[Test]
    public function it_lists_the_current_partner_under_their_own_heading(): void
    {
        $holly = $this->personIn('Holly', 'Flax');
        $this->romance($this->michael, $holly);

        $this->overview()
            ->assertSee(__('Current partner'))
            ->assertSee('Holly Flax')
            ->assertSee(route('contacts.show', ['vault' => $this->vault, 'contact' => $holly]));
    }

    #[Test]
    public function it_names_somebody_who_is_only_a_name_without_offering_a_way_through_to_them(): void
    {
        $jack = Contact::factory()->create([
            'account_id' => $this->user->account_id,
            'vault_id' => $this->vault->id,
            'first_name' => 'Jack',
            'last_name' => null,
            'is_partial' => true,
        ]);

        $this->parentage($this->michael, $jack);

        $this->overview()
            ->assertSee('Jack')
            ->assertSee(__('Name only, no profile yet'))
            ->assertDontSee(route('contacts.show', ['vault' => $this->vault, 'contact' => $jack]));
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

    #[Test]
    public function it_says_that_no_partner_is_recorded_while_naming_the_children(): void
    {
        $this->parentage($this->michael, $this->personIn('Pam', 'Beesly'));

        $this->overview()
            ->assertSee('Pam Beesly')
            ->assertSee(__('No current partner recorded'))
            ->assertDontSee(__('No children recorded'));
    }

    #[Test]
    public function it_says_that_no_children_are_recorded_while_naming_the_partner(): void
    {
        $this->romance($this->michael, $this->personIn('Holly', 'Flax'));

        $this->overview()
            ->assertSee('Holly Flax')
            ->assertSee(__('No children recorded'))
            ->assertDontSee(__('No current partner recorded'));
    }

    #[Test]
    public function it_reads_out_an_age_rather_than_leaving_a_bare_number_beside_a_name(): void
    {
        $child = $this->personIn('Pam', 'Beesly');
        $this->parentage($this->michael, $child);

        FieldValue::factory()->recurringDate(now()->subYears(22)->subMonth()->format('Y-m-d'))->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $child->id,
            'field_key' => DefaultFieldEnum::Birthday->value,
        ]);

        $this->overview()->assertSee(trans_choice(':count year old|:count years old', 22));
    }

    #[Test]
    public function it_offers_no_destination_that_does_not_exist_yet(): void
    {
        $this->parentage($this->michael, $this->personIn('Pam', 'Beesly'));

        $this->overview()
            ->assertDontSee(__('Family tree'))
            ->assertDontSee(__('View all'));
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

    private function romance(Contact $one, Contact $other): void
    {
        Relationship::factory()->romantic()->create([
            'vault_id' => $this->vault->id,
            'account_id' => $this->vault->account_id,
            'contact_a_id' => $one->id,
            'contact_b_id' => $other->id,
        ]);
    }
}
