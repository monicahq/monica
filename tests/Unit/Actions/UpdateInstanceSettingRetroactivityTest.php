<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateAccount;
use App\Actions\UpdateInstanceSetting;
use App\Enums\InstanceSettingEnum;
use App\Helpers\InstanceSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateInstanceSettingRetroactivityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_governs_only_the_accounts_created_after_it(): void
    {
        Queue::fake();
        config()->set('monica.self_hosted', false);
        config()->set('monica.free_storage_limit_in_bytes', 104857600);
        config()->set('monica.paid_storage_limit_in_bytes', 10737418240);
        InstanceSettings::stashDeploymentValues();

        $michael = new CreateAccount(
            firstName: 'Michael',
            lastName: 'Scott',
            email: 'michael@dundermifflin.test',
        )->execute();

        new UpdateInstanceSetting(
            administrator: User::factory()->create(),
            setting: InstanceSettingEnum::FreeStorageLimitInBytes,
            value: '1048576',
        )->execute();
        InstanceSettings::apply();

        $dwight = new CreateAccount(
            firstName: 'Dwight',
            lastName: 'Schrute',
            email: 'dwight@dundermifflin.test',
        )->execute();

        $this->assertEquals(104857600, $michael->account->fresh()->storage_limit_in_bytes);
        $this->assertEquals(1048576, $dwight->account->fresh()->storage_limit_in_bytes);
    }
}
