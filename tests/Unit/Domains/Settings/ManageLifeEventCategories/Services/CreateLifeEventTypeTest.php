<?php

namespace Tests\Unit\Domains\Settings\ManageLifeEventCategories\Services;

use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use App\Settings\ManageLifeEventCategories\Services\CreateLifeEventType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateLifeEventTypeTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_an_activity(): void
    {
        $ross = $this->createAdministrator();
        $category = LifeEventCategory::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $category);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateLifeEventType)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $category = LifeEventCategory::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $category);
    }

    /** @test */
    public function it_fails_if_user_is_not_administrator(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $category = LifeEventCategory::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $category);
    }

    /** @test */
    public function it_fails_if_category_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $category = LifeEventCategory::factory()->create();
        $this->executeService($ross, $account, $category);
    }

    private function executeService(User $author, Account $account, LifeEventCategory $category): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'life_event_category_id' => $category->id,
            'label' => 'type name',
            'can_be_deleted' => true,
        ];

        $type = (new CreateLifeEventType)->execute($request);

        $this->assertDatabaseHas('life_event_types', [
            'id' => $type->id,
            'life_event_category_id' => $category->id,
            'label' => 'type name',
            'can_be_deleted' => true,
        ]);

        $this->assertInstanceOf(
            LifeEventType::class,
            $type
        );
    }
}
