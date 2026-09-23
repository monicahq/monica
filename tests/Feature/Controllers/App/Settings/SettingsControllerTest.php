<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings;

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
}
