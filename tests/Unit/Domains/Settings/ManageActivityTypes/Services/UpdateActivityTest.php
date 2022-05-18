<?php

namespace Tests\Unit\Domains\Settings\ManageActivityTypes\Services;

use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\User;
use App\Settings\ManageActivityTypes\Services\UpdateActivity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateActivityTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_activity(): void
    {
        $ross = $this->createAdministrator();
        $type = ActivityType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $activity = Activity::factory()->create([
            'activity_type_id' => $type->id,
        ]);
        $this->executeService($ross, $ross->account, $type, $activity);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateActivity)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $type = ActivityType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $activity = Activity::factory()->create([
            'activity_type_id' => $type->id,
        ]);
        $this->executeService($ross, $account, $type, $activity);
    }

    /** @test */
    public function it_fails_if_type_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $type = ActivityType::factory()->create();
        $activity = Activity::factory()->create([
            'activity_type_id' => $type->id,
        ]);
        $this->executeService($ross, $ross->account, $type, $activity);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_account(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $type = ActivityType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $activity = Activity::factory()->create([
            'activity_type_id' => $type->id,
        ]);
        $this->executeService($ross, $ross->account, $type, $activity);
    }

    private function executeService(User $author, Account $account, ActivityType $type, Activity $activity): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'activity_type_id' => $type->id,
            'activity_id' => $activity->id,
            'label' => 'type name',
        ];

        $activity = (new UpdateActivity)->execute($request);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'activity_type_id' => $type->id,
            'label' => 'type name',
        ]);
    }
}
