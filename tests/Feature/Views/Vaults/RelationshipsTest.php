<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Vaults;

use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
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
    public function it_offers_each_category_a_first_relationship_can_go_under(): void
    {
        $family = RelationshipCategory::factory()->create(['account_id' => $this->user->account_id, 'name' => 'Family', 'position' => 1]);
        $work = RelationshipCategory::factory()->create(['account_id' => $this->user->account_id, 'name' => 'Dunder Mifflin', 'position' => 2]);
        $sibling = RelationshipType::factory()->create(['account_id' => $this->user->account_id, 'relationship_category_id' => $family->id, 'name' => 'Sibling']);
        RelationshipType::factory()->create(['account_id' => $this->user->account_id, 'relationship_category_id' => $work->id, 'name' => 'Regional manager']);

        $this->tab()
            ->assertSeeInOrder(['Family', 'Sibling', 'Dunder Mifflin', 'Regional manager'])
            ->assertSee('aria-label="Add to Family"', false)
            ->assertSee(route('contacts.relationships.new', ['vault' => $this->vault, 'contact' => $this->judy, 'type' => $sibling->id]), false);
    }

    #[Test]
    public function it_points_to_the_relationship_types_when_none_is_offered(): void
    {
        $this->tab()
            ->assertSee('Nobody is written down as connected to Judy yet.')
            ->assertSee(route('settings.relationshipTypes.index'));
    }

    #[Test]
    public function it_names_each_group_and_leads_to_each_person(): void
    {
        $ross = $this->personIn('Ross', 'Geller');
        $this->parentage($this->judy, $ross);

        $this->tab()
            ->assertSee('Ross Geller')
            ->assertSee(route('contacts.show', ['vault' => $this->vault, 'contact' => $ross]))
            ->assertSee(__('Manage'));
    }

    #[Test]
    public function it_leads_nowhere_for_somebody_who_is_only_a_name(): void
    {
        $ruth = $this->personIn('Ruth', 'Geller');
        $ruth->update(['is_partial' => true]);
        $this->parentage($this->judy, $ruth);

        $this->tab()
            ->assertSee('Ruth Geller')
            ->assertSee(__('Name only, no profile yet'))
            ->assertDontSee(route('contacts.show', ['vault' => $this->vault, 'contact' => $ruth]));
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
    public function it_offers_the_way_to_correct_and_to_remove_each_link(): void
    {
        $relationship = $this->parentage($this->judy, $this->personIn('Ross', 'Geller'));

        $this->tab()
            ->assertSee(__('Edit'))
            ->assertSee(__('Delete'))
            ->assertSee(route('contacts.relationships.edit', [
                'vault' => $this->vault,
                'contact' => $this->judy,
                'relationship' => $relationship,
            ]));
    }

    #[Test]
    public function it_warns_before_a_link_is_removed(): void
    {
        $this->parentage($this->judy, $this->personIn('Ross', 'Geller'));

        $this->tab()
            ->assertSee(__('Remove this relationship?'))
            ->assertSee(__('This cannot be undone.'))
            ->assertSee(__('The link with :name is taken off both records. Neither person is deleted.', ['name' => 'Ross Geller']));
    }

    #[Test]
    public function it_says_when_a_link_has_ended(): void
    {
        Relationship::factory()->parentage()->ended()->create([
            'vault_id' => $this->vault->id,
            'account_id' => $this->vault->account_id,
            'contact_a_id' => $this->judy->id,
            'contact_b_id' => $this->personIn('Ross', 'Geller')->id,
        ]);

        $this->tab()->assertSee(__('Ended'));
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
            ->assertSee(__('Edit this relationship'))
            ->assertSee(__('Relationship'))
            ->assertSee('Judy Geller is Ross Geller’s parent.')
            ->assertSee(__('This relationship has ended'))
            ->assertSee(__('Started on'))
            ->assertSee(__('Ended on'))
            ->assertDontSee(__('Create a contact record for this person'))
            ->assertSee('name="relationship"', escape: false)
            ->assertSee('name="has_ended"', escape: false)
            ->assertSee('name="started_on"', escape: false)
            ->assertSee('name="ended_on"', escape: false);
    }

    #[Test]
    public function the_form_renames_a_partial_contact_and_says_why(): void
    {
        $ross = $this->personIn('Ross', 'Geller');
        $ross->update(['is_partial' => true]);
        $relationship = $this->parentage($this->judy, $ross);

        $this->actingAs($this->user)
            ->get(route('contacts.relationships.edit', [
                'vault' => $this->vault,
                'contact' => $this->judy,
                'relationship' => $relationship,
            ]))
            ->assertStatus(200)
            ->assertSee(__(':name has no contact record, so their name is changed here.', ['name' => 'Ross']))
            ->assertSee('aria-describedby="first_name-help"', escape: false)
            ->assertSee('name="mode" value="known"', escape: false)
            ->assertSee(__('Create a contact record for this person'));
    }

    #[Test]
    public function the_form_offers_to_name_a_nameless_contact(): void
    {
        $ross = $this->personIn('Ross', 'Geller');
        $ross->update(['is_partial' => true, 'first_name' => null, 'last_name' => null]);
        $relationship = $this->parentage($this->judy, $ross);

        $this->actingAs($this->user)
            ->get(route('contacts.relationships.edit', [
                'vault' => $this->vault,
                'contact' => $this->judy,
                'relationship' => $relationship,
            ]))
            ->assertStatus(200)
            ->assertSee(__('I do not know their name'))
            ->assertSee(__('I know their name now'))
            ->assertSee(__('Create a contact record for this person'))
            ->assertSee('name="mode"', escape: false)
            ->assertDontSee('name="mode" value="known"', escape: false);
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
