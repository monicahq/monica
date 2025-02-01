<?php

namespace Tests\Unit\Domains\Vault\ManageVaultSettings\Services;

use App\Domains\Vault\ManageVaultSettings\Services\UpdateLifeEventCategoryPosition;
use App\Models\Account;
use App\Models\LifeEventCategory;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateLifeEventCategoryPositionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_life_event_category_position(): void
    {
        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_EDIT, $vault);
        $lifeEventCategory = LifeEventCategory::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $lifeEventCategory);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new UpdateLifeEventCategoryPosition)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_EDIT, $vault);
        $lifeEventCategory = LifeEventCategory::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $account, $vault, $lifeEventCategory);
    }

    /** @test */
    public function it_fails_if_life_event_category_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_EDIT, $vault);
        $lifeEventCategory = LifeEventCategory::factory()->create();
        $this->executeService($ross, $ross->account, $vault, $lifeEventCategory);
    }

    private function executeService(User $author, Account $account, Vault $vault, LifeEventCategory $lifeEventCategory): void
    {
        $lifeEventCategory1 = LifeEventCategory::factory()->create([
            'vault_id' => $vault->id,
            'position' => 1,
        ]);
        $lifeEventCategory3 = LifeEventCategory::factory()->create([
            'vault_id' => $vault->id,
            'position' => 3,
        ]);
        $lifeEventCategory4 = LifeEventCategory::factory()->create([
            'vault_id' => $vault->id,
            'position' => 4,
        ]);

        $request = [
            'account_id' => $account->id,
            'vault_id' => $vault->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'life_event_category_id' => $lifeEventCategory->id,
            'new_position' => 3,
        ];

        $lifeEventCategory = (new UpdateLifeEventCategoryPosition)->execute($request);

        $this->assertDatabaseHas('life_event_categories', [
            'id' => $lifeEventCategory1->id,
            'vault_id' => $vault->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('life_event_categories', [
            'id' => $lifeEventCategory3->id,
            'vault_id' => $vault->id,
            'position' => 2,
        ]);
        $this->assertDatabaseHas('life_event_categories', [
            'id' => $lifeEventCategory4->id,
            'vault_id' => $vault->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('life_event_categories', [
            'id' => $lifeEventCategory->id,
            'vault_id' => $vault->id,
            'position' => 3,
        ]);

        $request['new_position'] = 2;

        $lifeEventCategory = (new UpdateLifeEventCategoryPosition)->execute($request);

        $this->assertDatabaseHas('life_event_categories', [
            'id' => $lifeEventCategory1->id,
            'vault_id' => $vault->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('life_event_categories', [
            'id' => $lifeEventCategory3->id,
            'vault_id' => $vault->id,
            'position' => 3,
        ]);
        $this->assertDatabaseHas('life_event_categories', [
            'id' => $lifeEventCategory4->id,
            'vault_id' => $vault->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('life_event_categories', [
            'id' => $lifeEventCategory->id,
            'vault_id' => $vault->id,
            'position' => 2,
        ]);

        $this->assertInstanceOf(
            LifeEventCategory::class,
            $lifeEventCategory
        );
    }
}
