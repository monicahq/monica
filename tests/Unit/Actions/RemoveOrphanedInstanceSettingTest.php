<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RemoveOrphanedInstanceSetting;
use App\Enums\AdministratorActionEnum;
use App\Enums\InstanceSettingEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\InstanceSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemoveOrphanedInstanceSettingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_drops_a_value_the_product_no_longer_reads(): void
    {
        Queue::fake();
        $row = InstanceSetting::factory()->create(['name' => 'a_setting_that_was_dropped']);

        new RemoveOrphanedInstanceSetting(
            administrator: User::factory()->create(),
            name: 'a_setting_that_was_dropped',
        )->execute();

        $this->assertModelMissing($row);

        Queue::assertPushedOn('low', LogAdministratorAction::class, function (LogAdministratorAction $job): bool {
            return $job->action === AdministratorActionEnum::InstanceSettingReverted
                && $job->parameters['name'] === 'a_setting_that_was_dropped';
        });
    }

    #[Test]
    public function it_refuses_a_name_the_catalogue_still_carries(): void
    {
        Queue::fake();
        $row = InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::FreeContactLimit->value,
        ]);

        $this->expectException(ModelNotFoundException::class);

        try {
            new RemoveOrphanedInstanceSetting(
                administrator: User::factory()->create(),
                name: InstanceSettingEnum::FreeContactLimit->value,
            )->execute();
        } finally {
            $this->assertModelExists($row);
        }
    }

    #[Test]
    public function it_refuses_a_name_nothing_is_stored_under(): void
    {
        Queue::fake();

        $this->expectException(ModelNotFoundException::class);

        new RemoveOrphanedInstanceSetting(
            administrator: User::factory()->create(),
            name: 'nothing_was_ever_stored_here',
        )->execute();
    }
}
