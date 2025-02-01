<?php

namespace Tests\Unit\Domains\Settings\ManagePetCategories\Services;

use App\Domains\Settings\ManagePetCategories\Services\DestroyPetCategory;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\PetCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyPetCategoryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_gender(): void
    {
        $ross = $this->createAdministrator();
        $petCategory = PetCategory::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $petCategory);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyPetCategory)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $petCategory = PetCategory::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $petCategory);
    }

    /** @test */
    public function it_fails_if_gender_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $petCategory = PetCategory::factory()->create();
        $this->executeService($ross, $ross->account, $petCategory);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $petCategory = PetCategory::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $petCategory);
    }

    private function executeService(User $author, Account $account, PetCategory $petCategory): void
    {
        Queue::fake();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'pet_category_id' => $petCategory->id,
        ];

        (new DestroyPetCategory)->execute($request);

        $this->assertDatabaseMissing('pet_categories', [
            'id' => $petCategory->id,
        ]);
    }
}
