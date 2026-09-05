<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings;

use App\Models\EntityType;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_settings_screen(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            'email' => 'michael.scott@dundermifflin.com',
        ]);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Signed in as Michael Scott · michael.scott@dundermifflin.com', escape: false);
    }

    #[Test]
    public function it_points_to_everything_it_names(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee(route('settings.profile.index'), escape: false);
        $response->assertSee(route('settings.security.index'), escape: false);
        $response->assertSee(route('settings.preferences.index'), escape: false);
        $response->assertSee(route('settings.templates.index'), escape: false);
        $response->assertSee(route('settings.entityTypes.index'), escape: false);
        $response->assertSee(route('settings.valueLists.index'), escape: false);
        $response->assertSee(route('settings.logs.index'), escape: false);
        $response->assertSee(route('auth.login.destroy'), escape: false);
    }

    #[Test]
    public function it_sends_whoever_wants_to_delete_their_account_to_the_confirmation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee(route('settings.profile.index').'#delete-your-account', escape: false);
    }

    #[Test]
    public function it_draws_what_has_not_been_built_without_offering_it(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Notifications', escape: false);
        $response->assertSee('Export everything', escape: false);

        $content = str($response->getContent())->after('</header>')->before('</main>')->toString();

        $this->assertStringNotContainsString('href="#"', $content);
    }

    #[Test]
    public function it_says_whether_two_factor_authentication_is_on(): void
    {
        $without = User::factory()->create();

        $this->actingAs($without)
            ->get(route('settings.index'))
            ->assertSee('Two-factor off', escape: false);

        $with = User::factory()->create([
            'two_factor_secret' => encrypt('DWIGHTSCHRUTE'),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($with)
            ->get(route('settings.index'))
            ->assertSee('Two-factor on', escape: false);
    }

    #[Test]
    public function it_counts_what_the_account_holds(): void
    {
        $user = User::factory()->create();

        Template::factory()->count(3)->create(['account_id' => $user->account_id]);
        EntityType::factory()->count(2)->create(['account_id' => $user->account_id]);

        $other = User::factory()->create();
        Template::factory()->create(['account_id' => $other->account_id]);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('3 templates', escape: false);
        $response->assertSee('2 types', escape: false);
    }

    #[Test]
    public function it_navigates_the_settings_rather_than_any_vault(): void
    {
        $user = User::factory()->create();

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'position' => 1,
            'is_default' => true,
        ]);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee(route('settings.profile.index'), escape: false);
        $response->assertSee(route('settings.templates.index'), escape: false);
        $response->assertDontSee('Dunder Mifflin', escape: false);
    }

    #[Test]
    public function it_leads_back_out_to_the_vaults_whatever_the_default_is(): void
    {
        $user = User::factory()->create();

        Vault::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        Vault::factory()->create(['account_id' => $user->account_id, 'position' => 2, 'is_default' => true]);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee(route('vaults.index'), escape: false);
        $response->assertSee('Leave the settings', escape: false);
    }

    #[Test]
    public function it_navigates_an_account_that_has_made_no_vault_at_all(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee(route('settings.index'), escape: false);
        $response->assertSee('Sign out', escape: false);
        $response->assertSee(config('app.name'), escape: false);
    }

    #[Test]
    public function it_marks_no_destination_as_current_on_the_settings_home(): void
    {
        $user = User::factory()->create();

        Vault::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertDontSee('aria-current="page"', escape: false);
    }

    #[Test]
    public function it_only_reaches_the_vaults_of_the_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $theirs = Vault::factory()->create([
            'account_id' => $other->account_id,
            'name' => 'Sabre',
        ]);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Sabre', escape: false);
        $response->assertDontSee(route('vaults.show', $theirs), escape: false);
    }

    #[Test]
    public function it_turns_a_guest_away(): void
    {
        $response = $this->get(route('settings.index'));

        $response->assertRedirect(route('auth.login.new'));
    }
}
