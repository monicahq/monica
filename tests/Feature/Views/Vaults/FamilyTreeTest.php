<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Vaults;

use App\Models\Contact;
use App\Models\Relationship;
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
    public function it_draws_the_parents_the_partner_and_the_children(): void
    {
        $ruth = $this->personIn('Ruth', 'Geller');
        $jack = $this->personIn('Jack', 'Geller');
        $ross = $this->personIn('Ross', 'Geller');

        $this->parentage($ruth, $this->judy);
        $this->romantic($this->judy, $jack);
        $this->parentage($this->judy, $ross);

        $this->tab()
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
            ->assertSee('tabindex="0"', escape: false);
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
