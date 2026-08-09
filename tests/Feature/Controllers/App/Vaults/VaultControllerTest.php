<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\TemplateAppliesToEnum;
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

        $response->assertRedirect(route('home.index'));
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
