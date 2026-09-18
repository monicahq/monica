<?php

declare(strict_types=1);

namespace Tests\Feature\Views\App\Admin;

use App\Enums\InstanceSettingEnum;
use App\Helpers\InstanceSettings;
use App\Models\Administrator;
use App\Models\InstanceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_every_setting_and_points_its_form_at_the_route_that_saves_it(): void
    {
        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        foreach (InstanceSettingEnum::cases() as $setting) {
            $response->assertSee($setting->description());
            $response->assertSee(
                route('admin.configuration.update', ['setting' => $setting->value]),
                false
            );
        }
    }

    #[Test]
    public function it_shows_whether_the_instance_is_self_hosted_before_anything_else(): void
    {
        $content = $this->actingAs($this->administrator())->get('/admin/configuration')->getContent();

        $first = strpos($content, InstanceSettingEnum::SelfHosted->environmentVariable());

        foreach (InstanceSettingEnum::cases() as $setting) {
            if ($setting === InstanceSettingEnum::SelfHosted) {
                continue;
            }

            $this->assertGreaterThan(
                $first,
                strpos($content, $setting->environmentVariable()),
                "{$setting->value} is drawn before whether the instance is self hosted."
            );
        }
    }

    #[Test]
    public function it_names_the_environment_variable_of_every_setting(): void
    {
        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee('Environment variable');

        foreach (InstanceSettingEnum::cases() as $setting) {
            $response->assertSee($setting->environmentVariable());
        }
    }

    #[Test]
    public function it_submits_the_value_under_one_field_name(): void
    {
        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee('name="value"', false);
        $response->assertSee('name="_method"', false);
    }

    #[Test]
    public function it_points_a_refused_control_at_the_reason_it_is_refused(): void
    {
        config()->set('services.vonage.installed', false);

        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee('aria-describedby="reason-'.InstanceSettingEnum::SelfHosted->value.'"', false);
        $response->assertSee('id="reason-'.InstanceSettingEnum::SelfHosted->value.'"', false);
        $response->assertSee('aria-describedby="reason-'.InstanceSettingEnum::VonageSmsFrom->value.'"', false);
        $response->assertSee('Unavailable on this server, because its package is not installed.');
    }

    #[Test]
    public function it_says_when_the_deployment_value_is_no_longer_the_one_obeyed(): void
    {
        config()->set('monica.free_contact_limit', 30);
        InstanceSettings::stashDeploymentValues();
        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
            'value' => '5',
        ]);
        InstanceSettings::apply();

        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee('This instance was deployed with 30. That value is no longer the one obeyed.');
    }

    #[Test]
    public function it_never_prints_a_secret(): void
    {
        config()->set('mail.mailers.smtp.password', 'that-is-what-she-said');
        InstanceSettings::stashDeploymentValues();
        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::MailPassword->value,
            'value' => 'a-stored-one',
        ]);

        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertDontSee('that-is-what-she-said');
        $response->assertDontSee('a-stored-one');
        $response->assertSee('A secret is set. It is never shown back.');
    }

    #[Test]
    public function it_asks_before_a_change_that_takes_something_away(): void
    {
        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee('takesSomethingAway(', false);
        $response->assertSee('x-ref="value"', false);
        $response->assertSee('role="alert"', false);
        $response->assertSee('Any sign in link already emailed stops working');
    }

    #[Test]
    public function it_asks_nothing_before_a_change_that_takes_nothing_away(): void
    {
        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $this->assertEquals(
            3,
            substr_count($response->getContent(), 'takesSomethingAway($refs.value.value)'),
            'Only the three settings that can take something away carry the confirmation.'
        );
    }

    #[Test]
    public function it_keeps_a_setting_the_product_no_longer_carries_on_the_screen(): void
    {
        InstanceSetting::factory()->create(['name' => 'a_setting_that_was_dropped']);

        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee('No longer used');
        $response->assertSee('a_setting_that_was_dropped');
    }

    #[Test]
    public function it_offers_a_way_back_only_for_a_setting_somebody_changed(): void
    {
        config()->set('monica.free_contact_limit', 30);
        InstanceSettings::stashDeploymentValues();
        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
            'value' => '5',
        ]);

        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee('Put it back');

        $this->assertEquals(
            1,
            substr_count($response->getContent(), 'Put it back'),
            'Only the one setting somebody changed offers a way back.'
        );
    }

    #[Test]
    public function it_offers_to_remove_a_value_the_product_no_longer_reads(): void
    {
        InstanceSetting::factory()->create(['name' => 'a_setting_that_was_dropped']);

        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee(
            route('admin.configuration.orphans.destroy', ['name' => 'a_setting_that_was_dropped']),
            false
        );
    }

    #[Test]
    public function it_keeps_a_refused_control_reachable_rather_than_taking_it_out_of_the_tab_order(): void
    {
        config()->set('services.vonage.installed', false);

        $response = $this->actingAs($this->administrator())->get('/admin/configuration');
        $content = $response->getContent();

        $response->assertSee('aria-disabled="true"', false);

        $this->assertDoesNotMatchRegularExpression(
            '/\s(?<!aria-)disabled(\s*=|\s|>)/',
            $content,
            'A refused control carries aria-disabled and stays in the tab order, never the disabled attribute.'
        );
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
