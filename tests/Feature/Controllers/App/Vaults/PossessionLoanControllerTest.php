<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\StructureAppliesToEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Possession;
use App\Models\PossessionLoan;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionLoanControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_loans_of_a_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);
        $dwight = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
        PossessionLoan::factory()->active()->create(['possession_id' => $mug->id, 'contact_id' => $dwight->id]);

        $this->actingAs($user)->get(route('possessions.loans.index', [$vault, $mug]))
            ->assertOk()
            ->assertSee('Every loan')
            ->assertSee('Add a loan')
            ->assertSee('x-data="recordPicker(', escape: false);
    }

    #[Test]
    public function it_records_a_loan(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);
        $dwight = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.loans.create', [$vault, $mug]), [
            'direction' => 'lent',
            'status' => 'active',
            'contact_id' => $dwight->uuid,
            'started_on' => '2024-06-12',
            'due_on' => '2024-06-15',
            'deposit_amount' => '50',
            'deposit_currency' => 'usd',
            'is_in_provenance' => '1',
        ])
            ->assertRedirect(route('possessions.loans.index', [$vault, $mug]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('possession_loans', [
            'possession_id' => $mug->id,
            'contact_id' => $dwight->id,
            'status' => 'active',
            'deposit_amount' => '50.00',
            'is_in_provenance' => true,
        ]);
    }

    #[Test]
    public function it_records_a_loan_with_a_new_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $version->update(['status' => VersionStatusEnum::Published]);
        VaultDefaultTemplate::query()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'applies_to' => StructureAppliesToEnum::Contact,
            'structure_version_id' => $version->id,
        ]);
        $guide = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.loans.create', [$vault, $guide]), [
            'direction' => 'lent',
            'status' => 'active',
            'new_contact' => '1',
            'new_contact_first_name' => 'Nate',
            'started_on' => '2024-06-12',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Nate', PossessionLoan::query()->sole()->contact?->name);
    }

    #[Test]
    public function it_returns_the_form_when_the_loan_is_refused(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);
        PossessionLoan::factory()->active()->create(['possession_id' => $mug->id]);
        $dwight = Contact::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->from(route('possessions.loans.index', [$vault, $mug]))->post(route('possessions.loans.create', [$vault, $mug]), [
            'direction' => 'lent',
            'status' => 'active',
            'contact_id' => $dwight->uuid,
            'started_on' => '2024-06-12',
        ])
            ->assertRedirect(route('possessions.loans.index', [$vault, $mug]))
            ->assertSessionHasErrors('status');
    }

    #[Test]
    public function it_refuses_a_contact_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);
        $elsewhere = Contact::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->actingAs($user)->post(route('possessions.loans.create', [$vault, $mug]), [
            'direction' => 'lent',
            'status' => 'active',
            'contact_id' => $elsewhere->uuid,
            'started_on' => '2024-06-12',
        ])->assertNotFound();

        $this->assertDatabaseCount('possession_loans', 0);
    }

    #[Test]
    public function it_opens_a_loan_to_mark_it_as_returned(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $camera = Possession::factory()->create(['vault_id' => $vault->id]);
        $loan = PossessionLoan::factory()->active()->create(['possession_id' => $camera->id]);

        $this->actingAs($user)->get(route('possessions.loans.return.new', [$vault, $camera, $loan]))
            ->assertOk()
            ->assertSee('Edit loan')
            ->assertSee('name="returned_on"', escape: false);
    }

    #[Test]
    public function it_changes_a_loan(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $banjo = Possession::factory()->create(['vault_id' => $vault->id]);
        $loan = PossessionLoan::factory()->active()->create(['possession_id' => $banjo->id]);

        $this->actingAs($user)->get(route('possessions.loans.edit', [$vault, $banjo, $loan]))
            ->assertOk()
            ->assertSee('Save changes');

        $this->actingAs($user)->put(route('possessions.loans.update', [$vault, $banjo, $loan]), [
            'direction' => 'lent',
            'status' => 'returned',
            'started_on' => '2024-06-10',
            'returned_on' => '2024-06-14',
        ])
            ->assertRedirect(route('possessions.loans.index', [$vault, $banjo]))
            ->assertSessionHas('status');

        $this->assertEquals('returned', $loan->refresh()->status->value);
        $this->assertEquals('2024-06-14', $loan->returned_on?->toDateString());
    }

    #[Test]
    public function it_deletes_a_loan(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $banjo = Possession::factory()->create(['vault_id' => $vault->id]);
        $loan = PossessionLoan::factory()->create(['possession_id' => $banjo->id]);

        $this->actingAs($user)->delete(route('possessions.loans.destroy', [$vault, $banjo, $loan]))
            ->assertRedirect(route('possessions.loans.index', [$vault, $banjo]))
            ->assertSessionHas('status');

        $this->assertModelMissing($loan);
    }

    #[Test]
    public function a_loan_reached_through_another_possession_is_not_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $banjo = Possession::factory()->create(['vault_id' => $vault->id]);
        $loan = PossessionLoan::factory()->create(['possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id]);

        $this->actingAs($user)->get(route('possessions.loans.edit', [$vault, $banjo, $loan]))->assertNotFound();
        $this->actingAs($user)->get(route('possessions.loans.return.new', [$vault, $banjo, $loan]))->assertNotFound();
        $this->actingAs($user)->delete(route('possessions.loans.destroy', [$vault, $banjo, $loan]))->assertNotFound();
    }

    #[Test]
    public function the_contact_search_offers_the_vault_contacts_and_a_new_one(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $mug = Possession::factory()->create(['vault_id' => $vault->id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Dwight', 'last_name' => 'Schrute']);
        Contact::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'first_name' => 'Dwight', 'last_name' => 'Elsewhere']);

        $this->actingAs($user)->get(route('possessions.loanContactCandidates.index', [$vault, $mug, 'query' => 'Dwight']))
            ->assertOk()
            ->assertSee('Dwight Schrute')
            ->assertDontSee('Dwight Elsewhere')
            ->assertSee('Create “Dwight” as a new contact');
    }
}
