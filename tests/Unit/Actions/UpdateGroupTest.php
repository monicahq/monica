<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateGroup;
use App\Enums\DatePrecisionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Group;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateGroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_describes_and_dates_a_group(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Stamford branch']);

        new UpdateGroup(
            user: $user,
            vault: $vault,
            group: $group,
            name: 'Scranton branch',
            description: 'After the merger',
            startedOn: null,
            startedPrecision: DatePrecisionEnum::Unknown,
            endedOn: CarbonImmutable::parse('2013-05-16'),
            endedPrecision: DatePrecisionEnum::Day,
        )->execute();

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'name' => 'Scranton branch',
            'description' => 'After the merger',
            'ended_precision' => 'day',
        ]);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupUpdate);
    }

    #[Test]
    public function it_refuses_a_group_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        $this->expectException(ModelNotFoundException::class);

        new UpdateGroup(
            user: $user,
            vault: $vault,
            group: $group,
            name: 'Scranton branch',
            description: null,
            startedOn: null,
            startedPrecision: DatePrecisionEnum::Unknown,
            endedOn: null,
            endedPrecision: DatePrecisionEnum::Unknown,
        )->execute();
    }
}
