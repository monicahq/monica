<?php

declare(strict_types=1);

namespace Tests\Feature\Views\App\Admin;

use App\Enums\InstanceSettingEnum;
use App\Helpers\InstanceSettings;
use App\Models\Administrator;
use App\Models\InstanceSetting;
use App\Models\TelegramWebhookRegistration;
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
        $content = $this->actingAs($this->administrator())->get('/admin/configuration')->getContent();

        $form = $this->formAt($content, route('admin.configuration.update', ['setting' => InstanceSettingEnum::FreeContactLimit->value]));

        $this->assertStringNotContainsString('takesSomethingAway', $form);
        $this->assertStringContainsString("@click.prevent=\"\$dispatch('confirm-password', { form: \$el.form })\"", $form);
    }

    #[Test]
    public function it_asks_for_a_credential_on_the_screen_itself(): void
    {
        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee('x-data="confirmPassword(', false);
        $response->assertSee('Confirm it is you');
        $response->assertSee('What you are about to change decides how this instance behaves for everybody, so we ask for a credential first. You will not be asked again for a little while.');
    }

    #[Test]
    public function it_shows_the_warning_before_it_asks_for_a_credential(): void
    {
        $content = $this->actingAs($this->administrator())->get('/admin/configuration')->getContent();

        $form = $this->formAt($content, route('admin.configuration.update', ['setting' => InstanceSettingEnum::RegistrationEnabled->value]));

        $this->assertStringContainsString(
            "@click.prevent=\"if (! confirming && takesSomethingAway(\$refs.value.value)) { confirming = true } else { \$dispatch('confirm-password', { form: \$el.form }) }\"",
            $form,
        );
        $this->assertStringNotContainsString('@submit', $content);
    }

    #[Test]
    public function it_asks_no_credential_from_a_setting_that_cannot_be_changed(): void
    {
        config()->set('services.vonage.installed', false);

        $content = $this->actingAs($this->administrator())->get('/admin/configuration')->getContent();

        $form = $this->formAt($content, route('admin.configuration.update', ['setting' => InstanceSettingEnum::VonageSmsFrom->value]));

        $this->assertStringContainsString('aria-disabled="true"', $form);
        $this->assertStringNotContainsString('confirm-password', $form);
    }

    #[Test]
    public function it_asks_for_a_credential_before_a_setting_is_put_back(): void
    {
        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
            'value' => '5',
        ]);

        $content = $this->actingAs($this->administrator())->get('/admin/configuration')->getContent();

        $form = $this->formAt($content, route('admin.configuration.destroy', ['setting' => InstanceSettingEnum::FreeContactLimit->value]), last: true);

        $this->assertStringContainsString('Put it back', $form);
        $this->assertStringContainsString("\$dispatch('confirm-password', { form: \$el.form })", $form);
    }

    #[Test]
    public function it_asks_for_a_credential_before_a_secret_is_cleared(): void
    {
        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::MailPassword->value,
            'value' => 'a-stored-one',
        ]);

        $content = $this->actingAs($this->administrator())->get('/admin/configuration')->getContent();

        $form = $this->formAt($content, route('admin.configuration.destroy', ['setting' => InstanceSettingEnum::MailPassword->value]), last: true);

        $this->assertStringContainsString('Clear it', $form);
        $this->assertStringContainsString("\$dispatch('confirm-password', { form: \$el.form })", $form);
    }

    #[Test]
    public function it_asks_for_a_credential_before_a_value_nothing_reads_is_removed(): void
    {
        InstanceSetting::factory()->create(['name' => 'a_setting_that_was_dropped']);

        $content = $this->actingAs($this->administrator())->get('/admin/configuration')->getContent();

        $form = $this->formAt($content, route('admin.configuration.orphans.destroy', ['name' => 'a_setting_that_was_dropped']));

        $this->assertStringContainsString("\$dispatch('confirm-password', { form: \$el.form })", $form);
    }

    #[Test]
    public function it_asks_before_registration_is_switched_on(): void
    {
        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee("mode: 'switchedOn'", false);
        $response->assertSee('Anybody who reaches this instance can create an account until it is switched off again.');
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

    #[Test]
    public function it_offers_to_register_the_telegram_bot(): void
    {
        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee('Register with Telegram');
        $response->assertSee(route('admin.configuration.telegram.registration.create'), false);
    }

    #[Test]
    public function it_keeps_the_registration_control_and_says_what_is_missing(): void
    {
        config(['services.telegram.token' => null]);

        $response = $this->actingAs($this->administrator())->get('/admin/configuration');

        $response->assertSee('Register with Telegram');
        $response->assertSee('id="telegram-unavailable"', false);
        $response->assertSee('aria-describedby="telegram-unavailable"', false);
        $response->assertSee('Set the bot token first.');
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

    #[Test]
    public function it_asks_for_a_credential_before_generating_a_telegram_secret(): void
    {
        $this->makeTelegramAvailable();

        $content = $this->actingAs($this->administrator())->get('/admin/configuration')->getContent();

        $form = $this->formAt($content, route('admin.configuration.telegram.secret.create'));

        $this->assertStringContainsString("@click.prevent=\"\$dispatch('confirm-password', { form: \$el.form })\"", $form);
    }

    #[Test]
    public function it_warns_before_it_asks_for_a_credential_when_a_new_telegram_secret_breaks_the_registration(): void
    {
        $this->makeTelegramAvailable();
        TelegramWebhookRegistration::factory()->current()->create();

        $content = $this->actingAs($this->administrator())->get('/admin/configuration')->getContent();

        $form = $this->formAt($content, route('admin.configuration.telegram.secret.create'));

        $this->assertStringContainsString(
            "@click.prevent=\"if (! confirming) { confirming = true } else { \$dispatch('confirm-password', { form: \$el.form }) }\"",
            $form,
        );
        $this->assertStringContainsString('Generate again to go ahead.', $form);
    }

    #[Test]
    public function it_asks_no_credential_from_a_telegram_control_that_is_unavailable(): void
    {
        config(['services.telegram.installed' => false]);

        $content = $this->actingAs($this->administrator())->get('/admin/configuration')->getContent();

        $register = $this->formAt($content, route('admin.configuration.telegram.registration.create'));
        $secret = $this->formAt($content, route('admin.configuration.telegram.secret.create'));

        $this->assertStringContainsString('aria-disabled="true"', $register);
        $this->assertStringNotContainsString('confirm-password', $register);
        $this->assertStringContainsString('aria-disabled="true"', $secret);
        $this->assertStringNotContainsString('confirm-password', $secret);
    }

    private function makeTelegramAvailable(): void
    {
        config([
            'services.telegram.installed' => true,
            'services.telegram.token' => 'dwight-schrute-beet-farm',
            'services.telegram.webhook_secret' => 'bears-beets-battlestar-galactica',
        ]);
        URL::forceScheme('https');
        URL::forceRootUrl('https://erica.dundermifflin.com');
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
}
