<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\TemplateAppliesToEnum;
use App\Jobs\RecordVaultVisit;
use App\Models\Contact;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_the_vaults_of_the_account(): void
    {
        $user = User::factory()->create();
        Vault::factory()->default()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch',
        ]);
        Vault::factory()->create(['name' => 'Schrute Farms']);

        $this->actingAs($user)->get(route('vaults.index'))
            ->assertStatus(200)
            ->assertSee('Dunder Mifflin')
            ->assertSee('Everybody at the Scranton branch')
            ->assertDontSee('Schrute Farms');
    }

    #[Test]
    public function it_says_the_list_is_empty_rather_than_showing_nothing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('vaults.index'))
            ->assertStatus(200)
            ->assertSee('No vault yet');
    }

    #[Test]
    public function it_marks_the_vault_you_were_last_in_and_puts_it_first(): void
    {
        $user = User::factory()->create();

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Travail',
            'position' => 1,
            'last_visited_at' => now()->subWeek(),
        ]);

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Famille',
            'position' => 2,
            'last_visited_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->get(route('vaults.index'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Famille', 'Travail']);
        $response->assertSee('Last opened', escape: false);
    }

    #[Test]
    public function it_offers_a_way_to_the_account_since_it_has_no_dock_to_reach_it_from(): void
    {
        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('vaults.index'));

        $response->assertStatus(200);
        $response->assertSee(route('settings.index'), escape: false);
        $response->assertDontSee('erica-dock', escape: false);
    }

    #[Test]
    public function it_writes_down_that_the_vault_was_opened(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('vaults.show', $vault))->assertStatus(200);

        Queue::assertPushedOn('low', RecordVaultVisit::class);
    }

    #[Test]
    public function it_opens_a_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch',
            'contact_count' => 148,
        ]);

        $this->actingAs($user)->get(route('vaults.show', $vault))
            ->assertStatus(200)
            ->assertSee('Dunder Mifflin')
            ->assertSee('Everybody at the Scranton branch')
            ->assertSee('148 people')
            ->assertSee('Contacts')
            ->assertSee('Vault settings');
    }

    #[Test]
    public function it_shows_the_last_people_written_down_and_leads_to_all_of_them(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 1]);
        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);

        $this->actingAs($user)->get(route('vaults.show', $vault))
            ->assertStatus(200)
            ->assertSee('Pam Beesly')
            ->assertSee(route('contacts.index', $vault));
    }

    #[Test]
    public function it_asks_for_the_first_person_rather_than_leading_to_an_empty_list(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('vaults.show', $vault))
            ->assertStatus(200)
            ->assertSee('Nobody here yet')
            ->assertSee('Add the first person');
    }

    #[Test]
    public function it_draws_the_parts_of_a_vault_that_have_no_model_yet_without_offering_them(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('vaults.show', $vault))
            ->assertStatus(200)
            ->assertSee('No days written down')
            ->assertSee('Nothing you owe anyone')
            ->assertSee('No dates to keep')
            ->assertSee('No files yet');
    }

    #[Test]
    public function it_leads_to_the_settings_of_the_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('vaults.show', $vault))
            ->assertStatus(200)
            ->assertSee('All vault settings')
            ->assertSee(route('vaults.settings.index', $vault));
    }

    #[Test]
    public function it_offers_every_vault_of_the_account_in_the_launcher(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms', 'position' => 2]);
        Vault::factory()->create(['name' => 'Vance Refrigeration']);

        $this->actingAs($user)->get(route('vaults.show', $vault))
            ->assertStatus(200)
            ->assertSee('Schrute Farms')
            ->assertDontSee('Vance Refrigeration');
    }

    #[Test]
    public function it_marks_the_dashboard_as_where_you_are_in_the_dock(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('vaults.show', $vault))
            ->assertStatus(200)
            ->assertSee('aria-current="page"', escape: false);
    }

    #[Test]
    public function it_leads_from_the_bar_to_the_screen_that_writes_somebody_down(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('vaults.show', $vault))
            ->assertStatus(200)
            ->assertSee('Add someone')
            ->assertSee(route('contacts.new', $vault));
    }

    #[Test]
    public function it_leads_back_to_the_list_of_vaults(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('vaults.show', $vault))
            ->assertStatus(200)
            ->assertSee('Your vaults')
            ->assertSee(route('vaults.index'));
    }

    #[Test]
    public function it_signs_the_screen_with_the_mark_of_the_application(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('vaults.show', $vault))
            ->assertStatus(200)
            ->assertSee(asset('images/logo.svg'));
    }

    #[Test]
    public function it_reaches_the_account_settings_from_the_launcher_behind_the_dock(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('vaults.show', $vault))
            ->assertStatus(200)
            ->assertSee(route('settings.index'));
    }

    #[Test]
    public function it_does_not_find_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $elsewhere = Vault::factory()->create(['name' => 'Vance Refrigeration']);

        $this->actingAs($user)->get(route('vaults.show', $elsewhere))->assertNotFound();
    }

    #[Test]
    public function it_shows_the_form_that_opens_a_vault(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('vaults.new'))
            ->assertStatus(200)
            ->assertSee('Create a vault')
            ->assertSee('Make this the default vault');
    }

    #[Test]
    public function it_keeps_somebody_who_is_not_signed_in_out(): void
    {
        $this->get(route('vaults.new'))->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_says_what_ticking_the_default_box_would_cost(): void
    {
        $user = User::factory()->create();
        Vault::factory()->default()->create(['account_id' => $user->account_id, 'name' => 'Personal']);

        $this->actingAs($user)->get(route('vaults.new'))
            ->assertStatus(200)
            ->assertSee('Personal stays the default.');
    }

    #[Test]
    public function it_creates_a_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('vaults.create'), [
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch',
            'colour' => '#4f8a49',
        ]);

        $response->assertRedirect(route('vaults.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('vaults', [
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch',
            'colour' => '#4f8a49',
        ]);
    }

    #[Test]
    public function it_makes_the_new_vault_the_one_records_land_in_when_it_is_asked_to(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $existing = Vault::factory()->default()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('vaults.create'), [
            'name' => 'Schrute Farms',
            'colour' => '#4f8a49',
            'is_default' => '1',
        ]);

        $created = Vault::query()->where('name', 'Schrute Farms')->sole();

        $this->assertTrue($created->is_default);
        $this->assertFalse($existing->fresh()->is_default);
    }

    #[Test]
    public function it_leaves_the_default_where_it_is_when_the_box_is_left_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $existing = Vault::factory()->default()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->post(route('vaults.create'), [
            'name' => 'Schrute Farms',
            'colour' => '#4f8a49',
        ]);

        $this->assertFalse(Vault::query()->where('name', 'Schrute Farms')->sole()->is_default);
        $this->assertTrue($existing->fresh()->is_default);
    }

    #[Test]
    public function it_starts_the_contacts_of_the_vault_on_the_template_that_was_picked(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $chosen = $this->publishedContactTemplate($user, 'Work contact');

        $this->actingAs($user)->post(route('vaults.create'), [
            'name' => 'Dunder Mifflin',
            'colour' => '#4f8a49',
            'template_version_id' => $chosen->published_version_id,
        ]);

        $this->assertDatabaseHas('vault_default_templates', [
            'account_id' => $user->account_id,
            'vault_id' => Vault::query()->where('name', 'Dunder Mifflin')->sole()->id,
            'applies_to' => TemplateAppliesToEnum::Contact->value,
            'template_version_id' => $chosen->published_version_id,
        ]);
    }

    #[Test]
    public function it_asks_which_template_only_once_there_is_a_choice(): void
    {
        $user = User::factory()->create();
        $this->publishedContactTemplate($user, 'Standard contact');

        $this->actingAs($user)->get(route('vaults.new'))
            ->assertStatus(200)
            ->assertDontSee('Default template');

        $this->publishedContactTemplate($user, 'Work contact');

        $this->actingAs($user)->get(route('vaults.new'))
            ->assertStatus(200)
            ->assertSee('Default template')
            ->assertSee('Work contact');
    }

    #[Test]
    public function it_refuses_a_template_belonging_to_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        $elsewhere = $this->publishedContactTemplate($dwight, 'Beet varieties');

        $this->actingAs($michael)->post(route('vaults.create'), [
            'name' => 'Dunder Mifflin',
            'colour' => '#4f8a49',
            'template_version_id' => $elsewhere->published_version_id,
        ])->assertSessionHasErrors('template_version_id');

        $this->assertEquals(0, Vault::query()->where('account_id', $michael->account_id)->count());
    }

    #[Test]
    public function it_refuses_a_colour_that_is_not_on_offer(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('vaults.create'), [
            'name' => 'Dunder Mifflin',
            'colour' => 'rebeccapurple',
        ])->assertSessionHasErrors('colour');
    }

    #[Test]
    public function it_refuses_a_vault_with_no_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('vaults.create'), [
            'name' => '',
            'colour' => '#4f8a49',
        ])->assertSessionHasErrors('name');

        $this->assertEquals(0, Vault::query()->count());
    }

    #[Test]
    public function it_gives_the_vault_the_template_the_account_was_seeded_with_when_nothing_is_picked(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $supplied = $this->publishedContactTemplate($user, 'Basic contact', system: true);

        $this->actingAs($user)->post(route('vaults.create'), [
            'name' => 'Dunder Mifflin',
            'colour' => '#4f8a49',
        ]);

        $default = VaultDefaultTemplate::query()->sole();

        $this->assertEquals($supplied->published_version_id, $default->template_version_id);
    }

    #[Test]
    public function it_deletes_a_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);
        Vault::factory()->default()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms']);

        $response = $this->actingAs($user)->delete(route('vaults.destroy', $vault));

        $response->assertRedirect(route('vaults.index'));
        $response->assertSessionHas('status');
        $this->assertSoftDeleted('vaults', ['id' => $vault->id]);
    }

    #[Test]
    public function it_sends_somebody_who_deleted_their_last_vault_back_to_the_start(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);

        $response = $this->actingAs($user)->delete(route('vaults.destroy', $vault));

        $response->assertRedirect(route('gettingStarted.index'));
        $response->assertSessionHas('status');
        $this->assertSoftDeleted('vaults', ['id' => $vault->id]);
    }

    #[Test]
    public function it_does_not_delete_a_vault_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $elsewhere = Vault::factory()->create(['name' => 'Vance Refrigeration']);

        $this->actingAs($user)->delete(route('vaults.destroy', $elsewhere))->assertNotFound();

        $this->assertNotSoftDeleted('vaults', ['id' => $elsewhere->id]);
    }

    private function publishedContactTemplate(User $user, string $name, bool $system = false): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
            'is_system' => $system,
        ]);

        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $template->published_version_id = $version->id;
        $template->save();

        return $template;
    }
}
