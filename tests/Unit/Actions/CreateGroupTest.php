<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateGroup;
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

class CreateGroupTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_an_empty_group_in_the_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $group = new CreateGroup(
            user: $user,
            vault: $vault,
            name: 'Halpert household',
            description: 'Jim, Pam and the kids',
            startedOn: CarbonImmutable::parse('2007-01-01'),
            startedPrecision: DatePrecisionEnum::Year,
        )->execute();

        $this->assertInstanceOf(Group::class, $group);
        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'name' => 'Halpert household',
            'description' => 'Jim, Pam and the kids',
            'started_precision' => 'year',
            'ended_on' => null,
            'ended_precision' => 'unknown',
        ]);
        $this->assertCount(0, $group->members);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupCreation);
    }

    #[Test]
    public function it_forgets_the_precision_of_a_date_nobody_gave(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $group = new CreateGroup(
            user: $user,
            vault: $vault,
            name: 'Scranton branch',
            startedPrecision: DatePrecisionEnum::Year,
        )->execute();

        $this->assertEquals(DatePrecisionEnum::Unknown, $group->started_precision);
    }

    #[Test]
    public function it_refuses_a_vault_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new CreateGroup(
            user: User::factory()->create(),
            vault: Vault::factory()->create(),
            name: 'Dunder Mifflin',
        )->execute();
    }
}
