<?php

namespace Tests\Unit\Domains\Settings\ManagePetCategories\Services;

use App\Domains\Settings\ManagePetCategories\Services\CreatePetCategory;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\PetCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreatePetCategoryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_pet_category(): void
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
        (new CreatePetCategory)->execute($request);
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
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'name' => 'gender name',
        ];

        $petCategory = (new CreatePetCategory)->execute($request);

        $this->assertDatabaseHas('pet_categories', [
            'id' => $petCategory->id,
            'account_id' => $account->id,
            'name' => 'gender name',
        ]);

        $this->assertInstanceOf(
            PetCategory::class,
            $petCategory
        );
    }
}
