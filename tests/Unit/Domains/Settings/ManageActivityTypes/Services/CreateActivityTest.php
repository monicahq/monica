<?php

namespace Tests\Unit\Domains\Settings\ManageActivityTypes\Services;

use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\User;
use App\Settings\ManageActivityTypes\Services\CreateActivity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateActivityTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_an_activity(): void
    {
        $ross = $this->createAdministrator();
        $type = ActivityType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $type);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateActivity)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $type = ActivityType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $type);
    }

    /** @test */
    public function it_fails_if_user_is_not_administrator(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $type = ActivityType::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $type);
    }

    private function executeService(User $author, Account $account, ActivityType $type): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'activity_type_id' => $type->id,
            'label' => 'type name',
        ];

        $activity = (new CreateActivity)->execute($request);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'activity_type_id' => $type->id,
            'label' => 'type name',
        ]);

        $this->assertInstanceOf(
            Activity::class,
            $activity
        );
    }
}
