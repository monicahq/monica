<?php

namespace Tests\Unit\Domains\Vault\ManageLifeMetrics\Services;

use App\Domains\Vault\ManageLifeMetrics\Services\CreateLifeMetric;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\LifeMetric;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateLifeMetricTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_a_life_metric(): void
    {
        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $this->executeService($ross, $ross->account, $vault);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new CreateLifeMetric)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $this->executeService($ross, $account, $vault);
    }

    /** @test */
    public function it_fails_if_user_is_not_editor(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_VIEW, $vault);
        $this->executeService($ross, $ross->account, $vault);
    }

    /** @test */
    public function it_fails_if_vault_is_not_in_the_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $vault = $this->createVault(Account::factory()->create());
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_VIEW, $vault);
        $this->executeService($ross, $ross->account, $vault);
    }

    private function executeService(User $author, Account $account, Vault $vault): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'label' => 'label name',
        ];

        $lifeMetric = (new CreateLifeMetric)->execute($request);

        $this->assertDatabaseHas('life_metrics', [
            'id' => $lifeMetric->id,
            'vault_id' => $vault->id,
            'label' => 'label name',
        ]);

        $this->assertInstanceOf(
            LifeMetric::class,
            $lifeMetric
        );
    }
}
