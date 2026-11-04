<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Vaults;

use App\Enums\RelationshipTypeEnum;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FamilyTreeTest extends TestCase
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
    public function it_heads_the_panel_and_says_what_it_leaves_out(): void
    {
        $this->parentage($this->personIn('Ruth', 'Geller'), $this->judy);

        $this->tab()
            ->assertSee(__('Family tree'))
            ->assertSee(__('Only parentage and partnerships are drawn here. Friends and colleagues stay in the list.'));
    }

    #[Test]
    public function it_draws_the_parents_the_partner_and_the_children(): void
    {
        $ruth = $this->personIn('Ruth', 'Geller');
        $jack = $this->personIn('Jack', 'Geller');
        $ross = $this->personIn('Ross', 'Geller');

        $this->parentage($ruth, $this->judy);
        $this->romantic($this->judy, $jack);
        $this->parentage($this->judy, $ross);

        $this->tab()
            ->assertSee(__('Parents'))
            ->assertSee(__('Current partners'))
            ->assertSee(__('Children'))
            ->assertSee('Ruth Geller')
            ->assertSee('Jack Geller')
            ->assertSee('Ross Geller')
            ->assertSee(route('contacts.show', ['vault' => $this->vault, 'contact' => $ruth]))
            ->assertSee(route('contacts.show', ['vault' => $this->vault, 'contact' => $jack]))
            ->assertSee(route('contacts.show', ['vault' => $this->vault, 'contact' => $ross]));
    }

    #[Test]
    public function it_names_the_drawing_as_a_region(): void
    {
        $this->parentage($this->judy, $this->personIn('Ross', 'Geller'));

        $this->tab()
            ->assertSee('role="group"', escape: false)
            ->assertSee('aria-labelledby="family-tree"', escape: false)
            ->assertSee('tabindex="0"', escape: false)
            ->assertSee(__('The person this page is about'));
    }

    #[Test]
    public function it_leads_nowhere_for_somebody_who_is_only_a_name(): void
    {
        $iris = $this->personIn('Aunt', 'Iris');
        $iris->update(['is_partial' => true]);
        $this->parentage($iris, $this->judy);

        $this->tab()
            ->assertSee('Aunt Iris')
            ->assertSee(__('Name only, no profile yet'))
            ->assertDontSee(route('contacts.show', ['vault' => $this->vault, 'contact' => $iris]));
    }

    #[Test]
    public function it_draws_no_friend_though_the_groups_beside_it_do(): void
    {
        $gloria = $this->personIn('Gloria', 'Tribbiani');
        $type = RelationshipType::factory()->system(RelationshipTypeEnum::Friend)
            ->create(['account_id' => $this->vault->account_id]);

        Relationship::factory()->create([
            'account_id' => $this->vault->account_id,
            'vault_id' => $this->vault->id,
            'relationship_type_id' => $type->id,
            'contact_a_id' => $this->judy->id,
            'contact_b_id' => $gloria->id,
        ]);

        $this->tab()
            ->assertSee('Gloria Tribbiani')
            ->assertSee($this->emptyLine())
            ->assertDontSee(__('Parents'))
            ->assertDontSee(__('Children'));
    }

    #[Test]
    public function it_says_there_is_no_line_yet_and_offers_the_first_relationship(): void
    {
        $gloria = $this->personIn('Gloria', 'Tribbiani');
        $type = RelationshipType::factory()->system(RelationshipTypeEnum::Friend)
            ->create(['account_id' => $this->vault->account_id]);

        Relationship::factory()->create([
            'account_id' => $this->vault->account_id,
            'vault_id' => $this->vault->id,
            'relationship_type_id' => $type->id,
            'contact_a_id' => $this->judy->id,
            'contact_b_id' => $gloria->id,
        ]);

        $this->tab()
            ->assertSee($this->emptyLine())
            ->assertSee(route('contacts.relationships.new', ['vault' => $this->vault, 'contact' => $this->judy]));
    }

    #[Test]
    public function it_draws_every_child_of_a_large_family(): void
    {
        $names = ['Ross', 'Monica', 'Erica', 'Jack', 'Emma', 'Ben', 'Leonard'];

        foreach ($names as $name) {
            $this->parentage($this->judy, $this->personIn($name, 'Geller'));
        }

        $response = $this->tab();

        foreach ($names as $name) {
            $response->assertSee("{$name} Geller");
        }
    }

    #[Test]
    public function it_asks_the_records_no_more_often_for_a_large_family_than_for_a_small_one(): void
    {
        Queue::fake();
        $this->user->account->features()->active('marketplace');
        $this->user->account->features()->active('vcard-export');
        $this->user->account->features()->active('groups');
        $this->user->account->hasFullAccess();

        $this->parentage($this->judy, $this->personIn('Ross', 'Geller'));

        $small = $this->countQueries();

        foreach (['Monica', 'Erica', 'Jack', 'Emma', 'Ben', 'Leonard'] as $name) {
            $this->parentage($this->judy, $this->personIn($name, 'Geller'));
        }

        $this->assertSame($small, $this->countQueries());
    }

    private function countQueries(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->tab();

        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $queries;
    }

    private function emptyLine(): string
    {
        return __('Nobody is written down as :name’s parent, partner or child yet, so there is no line to draw.', [
            'name' => 'Judy',
        ]);
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

    private function parentage(Contact $parent, Contact $child): void
    {
        Relationship::factory()->parentage()->create([
            'vault_id' => $this->vault->id,
            'account_id' => $this->vault->account_id,
            'contact_a_id' => $parent->id,
            'contact_b_id' => $child->id,
        ]);
    }

    private function romantic(Contact $one, Contact $other): void
    {
        Relationship::factory()->romantic()->create([
            'vault_id' => $this->vault->id,
            'account_id' => $this->vault->account_id,
            'contact_a_id' => $one->id,
            'contact_b_id' => $other->id,
        ]);
    }
}
