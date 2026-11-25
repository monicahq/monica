<?php

declare(strict_types=1);

namespace Tests\Feature\Views\App\Admin;

use App\Enums\InstanceSettingEnum;
use App\Helpers\InstanceSettings;
use App\Models\Administrator;
use App\Models\InstanceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
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
            $response->assertSee(
                route('admin.configuration.update', ['setting' => $setting->value]),
                false
            );
        }
    }

    #[Test]
    public function it_points_a_refused_control_at_the_reason_it_is_refused(): void
    {
        config()->set('monica.sso_installed', []);

        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee('aria-describedby="reason-'.InstanceSettingEnum::SelfHosted->value.'"', false);
        $response->assertSee('id="reason-'.InstanceSettingEnum::SelfHosted->value.'"', false);
        $response->assertSee('aria-describedby="reason-'.InstanceSettingEnum::AppleClientId->value.'"', false);
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
    }

    #[Test]
    public function it_asks_before_a_change_that_takes_something_away(): void
    {
        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee('takesSomethingAway(', false);
        $response->assertSee('x-ref="value"', false);
        $response->assertSee('role="alert"', false);
    }

    #[Test]
    public function it_asks_for_a_credential_on_the_screen_itself(): void
    {
        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee('x-data="confirmPassword(', false);
    }

    #[Test]
    public function it_asks_no_credential_from_a_setting_that_cannot_be_changed(): void
    {
        config()->set('monica.sso_installed', []);

        $content = $this->actingAs($this->administrator())->get('/admin/configuration')->getContent();

        $form = $this->formAt($content, route('admin.configuration.update', ['setting' => InstanceSettingEnum::AppleClientId->value]));

        $this->assertStringContainsString('aria-disabled="true"', $form);
        $this->assertStringNotContainsString('confirm-password', $form);
    }

    #[Test]
    public function it_keeps_a_refused_control_reachable_rather_than_taking_it_out_of_the_tab_order(): void
    {
        config()->set('monica.sso_installed', []);

        $response = $this->actingAs($this->administrator())->get('/admin/configuration');
        $content = $response->getContent();

        $response->assertSee('aria-disabled="true"', false);

        $this->assertDoesNotMatchRegularExpression(
            '/\s(?<!aria-)disabled(\s*=|\s|>)/',
            $content,
            'A refused control carries aria-disabled and stays in the tab order, never the disabled attribute.'
        );
    }

    #[Test]
    public function it_keeps_the_registration_control_and_says_what_is_missing(): void
    {
        config(['services.telegram.token' => null]);

        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee('id="telegram-unavailable"', false);
        $response->assertSee('aria-describedby="telegram-unavailable"', false);
    }

    #[Test]
    public function it_asks_for_a_credential_before_registering_the_telegram_bot_but_not_before_checking_it(): void
    {
        $this->makeTelegramAvailable();

        $content = $this->actingAs($this->administrator())->get('/admin/configuration')->getContent();

        $register = $this->formAt($content, route('admin.configuration.telegram.registration.create'));
        $check = $this->formAt($content, route('admin.configuration.telegram.check.create'));

        $this->assertStringContainsString("@click.prevent=\"\$dispatch('confirm-password', { form: \$el.form })\"", $register);
        $this->assertStringNotContainsString('confirm-password', $check);
    }

    private function makeTelegramAvailable(): void
    {
        config([
            'services.telegram.token' => 'dwight-schrute-beet-farm',
            'services.telegram.webhook_secret' => 'bears-beets-battlestar-galactica',
        ]);
        URL::forceScheme('https');
        URL::forceRootUrl('https://monica.dundermifflin.com');
    }

    private function formAt(string $content, string $action, bool $last = false): string
    {
        $needle = 'action="'.$action.'"';
        $start = $last ? strrpos($content, $needle) : strpos($content, $needle);

        $this->assertNotFalse($start, "No form posts to {$action}.");

        return substr($content, $start, strpos($content, '</form>', $start) - $start);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    #[Test]
    public function it_asks_for_a_credential_before_every_change_the_screen_offers(): void
    {
        $this->makeTelegramAvailable();
        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
            'value' => '5',
        ]);
        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::MailPassword->value,
            'value' => 'a-stored-one',
        ]);
        InstanceSetting::factory()->create(['name' => 'a_setting_that_was_dropped']);

        $content = $this->actingAs($this->administrator())->get('/admin/configuration')->getContent();

        $forms = [
            $this->formAt($content, route('admin.configuration.update', ['setting' => InstanceSettingEnum::FreeContactLimit->value])),
            $this->formAt($content, route('admin.configuration.update', ['setting' => InstanceSettingEnum::RegistrationEnabled->value])),
            $this->formAt($content, route('admin.configuration.destroy', ['setting' => InstanceSettingEnum::FreeContactLimit->value]), last: true),
            $this->formAt($content, route('admin.configuration.destroy', ['setting' => InstanceSettingEnum::MailPassword->value]), last: true),
            $this->formAt($content, route('admin.configuration.orphans.destroy', ['name' => 'a_setting_that_was_dropped'])),
            $this->formAt($content, route('admin.configuration.telegram.secret.create')),
        ];

        foreach ($forms as $form) {
            $this->assertStringContainsString("\$dispatch('confirm-password', { form: \$el.form })", $form);
        }
    }
}
