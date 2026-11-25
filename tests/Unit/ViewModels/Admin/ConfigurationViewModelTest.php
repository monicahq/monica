<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Admin;

use App\Enums\InstanceSettingEnum;
use App\Enums\InstanceSettingGroupEnum;
use App\Helpers\InstanceSettings;
use App\Models\InstanceSetting;
use App\Models\TelegramWebhookRegistration;
use App\Models\User;
use App\ViewModels\Admin\ConfigurationViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConfigurationViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_every_setting_in_a_group(): void
    {
        $groups = new ConfigurationViewModel(user: User::factory()->create())->groups();

        $this->assertCount(count(InstanceSettingGroupEnum::cases()), $groups);

        $shown = array_sum(array_map(fn (array $g): int => count($g['settings']), $groups));

        $this->assertEquals(count(InstanceSettingEnum::cases()), $shown);
    }

    #[Test]
    public function it_opens_the_screen_with_whether_the_instance_is_self_hosted(): void
    {
        $groups = new ConfigurationViewModel(user: User::factory()->create())->groups();

        $this->assertEquals(InstanceSettingGroupEnum::Instance->value, $groups[0]['key']);
        $this->assertEquals(InstanceSettingEnum::SelfHosted->value, $groups[0]['settings'][0]['name']);
    }

    #[Test]
    public function it_marks_a_setting_nobody_changed_as_holding_the_deployment_value(): void
    {
        InstanceSettings::stashDeploymentValues();

        $setting = $this->find(InstanceSettingEnum::FreeContactLimit);

        $this->assertFalse($setting['changed']);
        $this->assertFalse($setting['disagreesWithDeployment']);
    }

    #[Test]
    public function it_marks_a_changed_setting_and_names_the_deployment_value(): void
    {
        config()->set('monica.free_contact_limit', 30);
        InstanceSettings::stashDeploymentValues();

        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
            'value' => '5',
        ]);
        InstanceSettings::apply();

        $setting = $this->find(InstanceSettingEnum::FreeContactLimit);

        $this->assertTrue($setting['changed']);
        $this->assertTrue($setting['disagreesWithDeployment']);
        $this->assertEquals(5, $setting['value']);
        $this->assertEquals(30, $setting['deploymentValue']);
    }

    #[Test]
    public function it_reports_a_setting_the_deployment_fixes_as_unchangeable_with_a_reason(): void
    {
        $setting = $this->find(InstanceSettingEnum::SelfHosted);

        $this->assertFalse($setting['changeable']);
        $this->assertNotNull($setting['reason']);
    }

    #[Test]
    public function it_reports_a_provider_whose_package_is_absent_as_unchangeable_with_a_reason(): void
    {
        config()->set('monica.sso_installed', []);

        $setting = $this->find(InstanceSettingEnum::AppleClientId);

        $this->assertFalse($setting['changeable']);
        $this->assertNotNull($setting['reason']);
    }

    #[Test]
    public function it_reports_a_provider_whose_package_is_present_as_changeable(): void
    {
        config()->set('monica.sso_installed', ['apple']);

        $setting = $this->find(InstanceSettingEnum::AppleClientId);

        $this->assertTrue($setting['changeable']);
        $this->assertNull($setting['reason']);
    }

    #[Test]
    public function it_never_carries_a_secret_value(): void
    {
        config()->set('mail.mailers.smtp.password', 'that-is-what-she-said');
        InstanceSettings::stashDeploymentValues();

        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::MailPassword->value,
            'value' => 'a-new-one',
        ]);

        $setting = $this->find(InstanceSettingEnum::MailPassword);

        $this->assertNull($setting['value']);
        $this->assertNull($setting['deploymentValue']);
        $this->assertTrue($setting['secretIsSet']);
    }

    #[Test]
    public function it_keeps_a_row_the_product_no_longer_carries(): void
    {
        InstanceSetting::factory()->create(['name' => 'a_setting_that_was_dropped']);

        $viewModel = new ConfigurationViewModel(user: User::factory()->create());

        $this->assertTrue($viewModel->hasOrphaned());
        $this->assertEquals([['name' => 'a_setting_that_was_dropped']], $viewModel->orphaned());
    }

    #[Test]
    public function it_reports_no_orphans_when_every_row_is_a_setting(): void
    {
        InstanceSetting::factory()->create(['name' => InstanceSettingEnum::FreeVaultLimit->value]);

        $this->assertFalse(new ConfigurationViewModel(user: User::factory()->create())->hasOrphaned());
    }

    #[Test]
    public function it_warns_before_a_telegram_credential_is_replaced_while_the_bot_is_registered(): void
    {
        URL::forceScheme('https');
        URL::forceRootUrl('https://monica.dundermifflin.com');
        TelegramWebhookRegistration::factory()->current()->create();

        $this->assertSame('replaced', $this->find(InstanceSettingEnum::TelegramBotWebhookSecret)['warnsWhen']);
    }

    #[Test]
    public function it_does_not_warn_about_a_telegram_credential_while_nothing_is_registered(): void
    {
        $this->assertNull($this->find(InstanceSettingEnum::TelegramBotToken)['warning']);
        $this->assertNull($this->find(InstanceSettingEnum::TelegramBotWebhookSecret)['warning']);
    }

    #[Test]
    public function it_offers_neither_credential_to_an_administrator_who_holds_neither(): void
    {
        $jan = User::factory()->singleSignOn()->create();

        $credentials = new ConfigurationViewModel(user: $jan)->credentials();

        $this->assertFalse($credentials->offersPassword());
        $this->assertFalse($credentials->offersPasskey());
    }

    /** @return array<string, mixed> */
    private function find(InstanceSettingEnum $setting): array
    {
        foreach (new ConfigurationViewModel(user: User::factory()->create())->groups() as $group) {
            foreach ($group['settings'] as $shown) {
                if ($shown['name'] === $setting->value) {
                    return $shown;
                }
            }
        }

        $this->fail("{$setting->value} is on no group of the screen.");
    }
}
