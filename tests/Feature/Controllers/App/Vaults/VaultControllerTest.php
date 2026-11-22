<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Jobs\CreateDefaultTemplate;
use App\Jobs\RecordVaultVisit;
use App\Models\LifeEvent;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
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
        ]);
        Vault::factory()->create(['name' => 'Schrute Farms']);

        $this->actingAs($user)->get(route('vaults.index'))
            ->assertStatus(200)
            ->assertSee('Dunder Mifflin')
            ->assertDontSee('Schrute Farms');
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
            ->assertSee('148 people');
    }

    #[Test]
    public function it_draws_the_timeline_alone_when_only_the_timeline_is_asked_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Dundies', 'happened_on' => '2019-05-01']);

        $this->actingAs($user)
            ->withHeaders(['X-Alpine-Request' => 'true', 'X-Alpine-Target' => 'dashboard-timeline'])
            ->get(route('vaults.show', ['vault' => $vault, 'slice' => '2019']))
            ->assertOk()
            ->assertSee('id="dashboard-timeline"', escape: false)
            ->assertSee('Dundies')
            ->assertDontSee('Needs your attention')
            ->assertDontSee('Favourite people');

        Queue::assertNotPushed(RecordVaultVisit::class);
    }

    #[Test]
    public function it_shows_the_form_that_opens_a_vault(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('vaults.new'))
            ->assertStatus(200);
    }

    #[Test]
    public function it_creates_a_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultTemplate(user: $user)->handle();

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
    public function it_starts_the_vault_and_its_owner_on_the_template_chosen_on_the_form(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultTemplate(user: $user)->handle();
        $chosen = Template::factory()->published()->create(['account_id' => $user->account_id, 'name' => 'Paper sales']);

        $this->actingAs($user)->post(route('vaults.create'), [
            'name' => 'Dunder Mifflin',
            'colour' => '#4f8a49',
            'structure_version_id' => $chosen->structure->published_version_id,
        ])->assertRedirect();

        $vault = Vault::query()->where('name', 'Dunder Mifflin')->sole();

        $this->assertDatabaseHas('vault_default_templates', [
            'vault_id' => $vault->id,
            'applies_to' => 'contact',
            'structure_version_id' => $chosen->structure->published_version_id,
        ]);
        $this->assertDatabaseHas('contacts', [
            'vault_id' => $vault->id,
            'is_me' => true,
            'structure_version_id' => $chosen->structure->published_version_id,
        ]);
    }

    #[Test]
    public function it_makes_the_new_vault_the_one_records_land_in_when_it_is_asked_to(): void
    {
        config(['monica.free_vault_limit' => 5]);
        Queue::fake();

        $user = User::factory()->create();

        new CreateDefaultTemplate(user: $user)->handle();
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
    public function it_renames_a_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'The Scranton branch',
            'colour' => '#4f8a49',
        ]);

        $response = $this->actingAs($user)->put(route('vaults.update', $vault), [
            'name' => 'Schrute Farms',
            'description' => 'Beets, bears, bed and breakfast',
            'colour' => '#9a3f2b',
        ]);

        $response->assertRedirect(route('vaults.settings.index', $vault->fresh()));
        $response->assertSessionHas('status');

        $vault->refresh();

        $this->assertEquals('Schrute Farms', $vault->name);
        $this->assertEquals('Beets, bears, bed and breakfast', $vault->description);
        $this->assertEquals('#9a3f2b', $vault->colour);
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
    public function it_refuses_a_vault_past_the_ceiling_and_names_it(): void
    {
        Queue::fake();
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)
            ->from(route('vaults.new'))
            ->post(route('vaults.create'), ['name' => 'Sabre', 'colour' => '#4f8a49']);

        $response->assertRedirect(route('vaults.new'));
        $response->assertSessionHasErrors('name');
        $this->assertSame(1, $user->account->vaults()->count());
    }

    #[Test]
    public function the_vaults_screen_marks_the_creation_unavailable_at_the_ceiling(): void
    {
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create(['full_access_offer_dismissed_at' => now()]);
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);

        $this->actingAs($user)->get(route('vaults.index'))
            ->assertStatus(200)
            ->assertSee('aria-disabled="true" aria-describedby="vault-ceiling"', false)
            ->assertSee(route('settings.billing.index'), false);
    }

    #[Test]
    public function it_opens_the_default_vault_in_place_of_the_list_when_the_list_is_hidden(): void
    {
        $user = User::factory()->create(['shows_vault_list' => false]);
        $vault = Vault::factory()->default()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
        ]);

        $this->actingAs($user)->get(route('vaults.index'))
            ->assertRedirect(route('vaults.show', $vault));
    }

    #[Test]
    public function it_opens_the_new_vault_after_creating_it_when_the_list_is_hidden(): void
    {
        config(['monica.free_vault_limit' => 5]);
        Queue::fake();

        $user = User::factory()->create(['shows_vault_list' => false]);

        new CreateDefaultTemplate(user: $user)->handle();

        Vault::factory()->default()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
        ]);

        $response = $this->actingAs($user)->post(route('vaults.create'), [
            'name' => 'Schrute Farms',
            'colour' => '#4f8a49',
        ]);

        $vault = Vault::query()->where('name', 'Schrute Farms')->firstOrFail();

        $response->assertRedirect(route('vaults.show', $vault));
        $response->assertSessionHas('status');
    }

    #[Test]
    public function it_shows_the_list_when_the_list_is_hidden_but_there_is_no_vault(): void
    {
        $user = User::factory()->create(['shows_vault_list' => false]);

        $this->actingAs($user)->get(route('vaults.index'))
            ->assertStatus(200)
            ->assertSee('No vault yet');
    }

    #[Test]
    public function it_keeps_the_message_on_the_way_to_the_default_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create(['shows_vault_list' => false]);
        Vault::factory()->default()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms']);

        $this->actingAs($user)
            ->followingRedirects()
            ->delete(route('vaults.destroy', $vault))
            ->assertStatus(200)
            ->assertSee('The vault is deleted.');
    }

    #[Test]
    public function it_refuses_to_delete_the_default_vault_while_the_list_is_hidden(): void
    {
        $user = User::factory()->create(['shows_vault_list' => false]);
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)
            ->from(route('vaults.settings.index', $vault))
            ->delete(route('vaults.destroy', $vault));

        $response->assertRedirect(route('vaults.settings.index', $vault));
        $response->assertSessionHasErrors('vault');
        $this->assertNotSoftDeleted('vaults', ['id' => $vault->id]);
    }
}
