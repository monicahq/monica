<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RevertInstanceSetting;
use App\Enums\AdministratorActionEnum;
use App\Enums\InstanceSettingEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\InstanceSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RevertInstanceSettingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_drops_what_was_stored(): void
    {
        Queue::fake();
        $row = InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
        ]);

        new RevertInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::FreeContactLimit,
        )->execute();

        $this->assertModelMissing($row);
    }

    #[Test]
    public function it_records_the_reversion(): void
    {
        Queue::fake();
        InstanceSetting::factory()->create(['name' => InstanceSettingEnum::FreeContactLimit->value]);

        new RevertInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::FreeContactLimit,
        )->execute();

        Queue::assertPushedOn('low', LogAdministratorAction::class, function (LogAdministratorAction $job): bool {
            return $job->action === AdministratorActionEnum::InstanceSettingReverted;
        });
    }

    #[Test]
    public function it_treats_a_setting_nobody_changed_as_already_reverted(): void
    {
        Queue::fake();

        new RevertInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::FreeContactLimit,
        )->execute();

        $this->assertDatabaseMissing('instance_settings', ['name' => 'free_contact_limit']);
        Queue::assertNotPushed(LogAdministratorAction::class);
    }

    #[Test]
    public function it_clears_a_secret_that_cannot_be_read_back_to_be_edited(): void
    {
        Queue::fake();
        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::MailPassword->value,
            'value' => 'that-is-what-she-said',
        ]);

        new RevertInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::MailPassword,
        )->execute();

        $this->assertDatabaseMissing('instance_settings', ['name' => 'mail_password']);
    }
}
