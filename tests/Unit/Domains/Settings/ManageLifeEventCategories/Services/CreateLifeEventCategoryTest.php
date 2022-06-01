<?php

namespace Tests\Unit\Domains\Settings\ManageLifeEventCategories\Services;

use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\LifeEventCategory;
use App\Models\User;
use App\Settings\ManageLifeEventCategories\Services\CreateLifeEventCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateLifeEventCategoryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_life_event_category(): void
    {
        $ross = $this->createAdministrator();
        $this->executeService($ross, $ross->account);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateLifeEventCategory)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $this->executeService($ross, $account);
    }

    /** @test */
    public function it_fails_if_user_is_not_administrator(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $this->executeService($ross, $ross->account);
    }

    private function executeService(User $author, Account $account): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'label' => 'type name',
            'can_be_deleted' => true,
            'type' => null,
        ];

        $type = (new CreateLifeEventCategory)->execute($request);

        $this->assertDatabaseHas('life_event_categories', [
            'id' => $type->id,
            'account_id' => $account->id,
            'label' => 'type name',
            'can_be_deleted' => true,
        ]);

        $this->assertInstanceOf(
            LifeEventCategory::class,
            $type
        );
    }
}
