<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Admin;

use App\Enums\InstanceSettingEnum;
use App\Helpers\InstanceSettings;
use App\Models\Administrator;
use App\Models\InstanceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstanceSettingControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_configuration_screen(): void
    {
        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertStatus(200);
        $response->assertSee('Configuration');
        $response->assertSee('How many contacts an account gets on the free offer.');
    }

    #[Test]
    public function it_saves_a_change(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->administrator())
            ->put('/admin/configuration/'.InstanceSettingEnum::FreeContactLimit->value, ['value' => '5']);

        $response->assertRedirect(route('admin.configuration.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('instance_settings', ['name' => 'free_contact_limit']);
    }

    #[Test]
    public function it_governs_the_next_request(): void
    {
        Queue::fake();
        config()->set('monica.free_contact_limit', 30);
        InstanceSettings::stashDeploymentValues();

        $this->actingAs($this->administrator())
            ->put('/admin/configuration/'.InstanceSettingEnum::FreeContactLimit->value, ['value' => '5']);

        InstanceSettings::apply();

        $this->assertSame(5, config('monica.free_contact_limit'));
    }

    #[Test]
    public function it_refuses_a_value_the_setting_cannot_take_and_stores_nothing(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->administrator())
            ->put('/admin/configuration/'.InstanceSettingEnum::FreeContactLimit->value, ['value' => 'nonsense']);

        $response->assertSessionHasErrors('value');
        $this->assertDatabaseMissing('instance_settings', ['name' => 'free_contact_limit']);
    }

    #[Test]
    public function it_leaves_a_stored_value_untouched_when_it_refuses(): void
    {
        Queue::fake();
        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
            'value' => '30',
        ]);

        $this->actingAs($this->administrator())
            ->put('/admin/configuration/'.InstanceSettingEnum::FreeContactLimit->value, ['value' => 'nonsense']);

        $this->assertEquals('30', InstanceSetting::query()->firstWhere('name', 'free_contact_limit')->value);
    }

    #[Test]
    public function it_refuses_a_setting_the_deployment_fixes(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->administrator())
            ->put('/admin/configuration/'.InstanceSettingEnum::SelfHosted->value, ['value' => '1']);

        $response->assertSessionHasErrors('value');
        $this->assertDatabaseMissing('instance_settings', ['name' => 'self_hosted']);
    }

    #[Test]
    public function it_does_not_know_a_setting_the_catalogue_does_not_carry(): void
    {
        $response = $this->actingAs($this->administrator())
            ->put('/admin/configuration/a-setting-that-does-not-exist', ['value' => '5']);

        $response->assertStatus(404);
    }

    #[Test]
    public function it_puts_a_setting_back_to_what_the_deployment_supplies(): void
    {
        Queue::fake();
        config()->set('monica.free_contact_limit', 30);
        InstanceSettings::stashDeploymentValues();
        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
            'value' => '5',
        ]);

        $response = $this->actingAs($this->administrator())
            ->delete('/admin/configuration/'.InstanceSettingEnum::FreeContactLimit->value);

        $response->assertRedirect(route('admin.configuration.index'));
        $this->assertDatabaseMissing('instance_settings', ['name' => 'free_contact_limit']);

        InstanceSettings::apply();

        $this->assertSame(30, config('monica.free_contact_limit'));
    }

    #[Test]
    public function it_removes_a_value_the_product_no_longer_reads(): void
    {
        Queue::fake();
        InstanceSetting::factory()->create(['name' => 'a_setting_that_was_dropped']);

        $response = $this->actingAs($this->administrator())
            ->delete('/admin/configuration/orphans/a_setting_that_was_dropped');

        $response->assertRedirect(route('admin.configuration.index'));
        $this->assertDatabaseMissing('instance_settings', ['name' => 'a_setting_that_was_dropped']);
    }

    #[Test]
    public function it_refuses_to_remove_a_live_setting_through_the_orphan_route(): void
    {
        Queue::fake();
        InstanceSetting::factory()->create(['name' => InstanceSettingEnum::FreeContactLimit->value]);

        $response = $this->actingAs($this->administrator())
            ->delete('/admin/configuration/orphans/'.InstanceSettingEnum::FreeContactLimit->value);

        $response->assertStatus(404);
        $this->assertDatabaseHas('instance_settings', ['name' => 'free_contact_limit']);
    }

    #[Test]
    public function it_asks_for_a_credential_before_a_change(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->put('/admin/configuration/'.InstanceSettingEnum::FreeContactLimit->value, ['value' => '5']);

        $response->assertRedirect(route('auth.confirmPassword.new'));
        $this->assertDatabaseMissing('instance_settings', ['name' => 'free_contact_limit']);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);
        $this->session(['auth.password_confirmed_at' => time()]);

        return $user;
    }
}
