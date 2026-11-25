<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateInstanceSetting;
use App\Enums\AdministratorActionEnum;
use App\Enums\InstanceSettingEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\InstanceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateInstanceSettingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stores_a_value(): void
    {
        Queue::fake();

        $setting = new UpdateInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::FreeContactLimit,
            value: '5',
        )->execute();

        $this->assertInstanceOf(InstanceSetting::class, $setting);
        $this->assertDatabaseHas('instance_settings', ['name' => 'free_contact_limit']);
        $this->assertEquals('5', $setting->fresh()->value);
    }

    #[Test]
    public function it_refuses_a_value_the_kind_cannot_take(): void
    {
        Queue::fake();

        $this->expectException(ValidationException::class);

        new UpdateInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::FreeContactLimit,
            value: 'Dunder Mifflin',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_count_below_one(): void
    {
        Queue::fake();

        $this->expectException(ValidationException::class);

        new UpdateInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::FreeVaultLimit,
            value: '-3',
        )->execute();
    }

    #[Test]
    public function it_refuses_an_address_that_is_not_one(): void
    {
        Queue::fake();

        $this->expectException(ValidationException::class);

        new UpdateInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::TelegramBotUrl,
            value: 'not an address',
        )->execute();
    }

    #[Test]
    public function it_leaves_the_stored_value_untouched_when_it_refuses(): void
    {
        Queue::fake();
        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
            'value' => '30',
        ]);

        try {
            new UpdateInstanceSetting(
                administrator: User::factory()->create(),
                setting: InstanceSettingEnum::FreeContactLimit,
                value: 'nonsense',
            )->execute();
        } catch (ValidationException) {
        }

        $this->assertEquals('30', InstanceSetting::query()->firstWhere('name', 'free_contact_limit')->value);
    }

    #[Test]
    public function it_refuses_a_default_page_size_above_the_ceiling(): void
    {
        Queue::fake();
        config()->set('monica.api_maximum_items_per_page', 100);

        $this->expectException(ValidationException::class);

        new UpdateInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::ApiDefaultItemsPerPage,
            value: '250',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_ceiling_below_the_default_page_size(): void
    {
        Queue::fake();
        config()->set('monica.api_default_items_per_page', 25);

        $this->expectException(ValidationException::class);

        new UpdateInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::ApiMaximumItemsPerPage,
            value: '10',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_free_storage_limit_above_the_paid_one(): void
    {
        Queue::fake();
        config()->set('monica.paid_storage_limit_in_bytes', 10737418240);

        $this->expectException(ValidationException::class);

        new UpdateInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::FreeStorageLimitInBytes,
            value: '99999999999999',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_setting_the_deployment_fixes(): void
    {
        Queue::fake();

        $this->expectException(ValidationException::class);

        new UpdateInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::SelfHosted,
            value: '1',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_provider_whose_package_is_absent(): void
    {
        Queue::fake();
        config()->set('monica.sso_installed', []);

        $this->expectException(ValidationException::class);

        new UpdateInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::AppleClientId,
            value: 'com.dundermifflin.monica',
        )->execute();
    }

    #[Test]
    public function it_records_the_change_against_the_administrator(): void
    {
        Queue::fake();

        new UpdateInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::FreeContactLimit,
            value: '5',
        )->execute();

        Queue::assertPushedOn('low', LogAdministratorAction::class, function (LogAdministratorAction $job): bool {
            return $job->action === AdministratorActionEnum::InstanceSettingUpdated
                && $job->parameters['after'] === '5';
        });
    }

    #[Test]
    public function it_records_that_a_secret_changed_and_never_what_it_changed_to(): void
    {
        Queue::fake();

        new UpdateInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::MailPassword,
            value: 'that-is-what-she-said',
        )->execute();

        Queue::assertPushedOn('low', LogAdministratorAction::class, function (LogAdministratorAction $job): bool {
            return $job->action === AdministratorActionEnum::InstanceSettingUpdated
                && ! array_key_exists('before', $job->parameters)
                && ! array_key_exists('after', $job->parameters);
        });
    }

    #[Test]
    public function it_leaves_a_secret_alone_when_the_field_comes_back_empty(): void
    {
        Queue::fake();
        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::MailPassword->value,
            'value' => 'that-is-what-she-said',
        ]);

        new UpdateInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::MailPassword,
            value: '',
        )->execute();

        $this->assertEquals(
            'that-is-what-she-said',
            InstanceSetting::query()->firstWhere('name', 'mail_password')->value
        );
        Queue::assertNotPushed(LogAdministratorAction::class);
    }

    #[Test]
    public function it_attributes_a_change_made_with_nobody_signed_in_to_the_system(): void
    {
        Queue::fake();

        new UpdateInstanceSetting(
            administrator: null,
            setting: InstanceSettingEnum::FreeContactLimit,
            value: '5',
        )->execute();

        Queue::assertPushed(
            LogAdministratorAction::class,
            fn (LogAdministratorAction $job): bool => $job->administrator === null
                && $job->action === AdministratorActionEnum::InstanceSettingUpdated,
        );
    }
}
