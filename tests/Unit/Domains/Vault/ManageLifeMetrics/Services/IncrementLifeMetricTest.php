<?php

namespace Tests\Unit\Domains\Vault\ManageLifeMetrics\Services;

use App\Domains\Vault\ManageLifeMetrics\Services\IncrementLifeMetric;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\LifeMetric;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class IncrementLifeMetricTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_increments_a_life_metric(): void
    {
        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $lifeMetric = LifeMetric::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $lifeMetric);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new IncrementLifeMetric)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $lifeMetric = LifeMetric::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $account, $vault, $lifeMetric);
    }

    /** @test */
    public function it_fails_if_life_metric_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_MANAGE, $vault);
        $lifeMetric = LifeMetric::factory()->create();
        $this->executeService($ross, $ross->account, $vault, $lifeMetric);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_account(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $vault = $this->createVault($ross->account);
        $vault = $this->setPermissionInVault($ross, Vault::PERMISSION_VIEW, $vault);
        $lifeMetric = LifeMetric::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $this->executeService($ross, $ross->account, $vault, $lifeMetric);
    }

    private function executeService(User $author, Account $account, Vault $vault, LifeMetric $lifeMetric): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'life_metric_id' => $lifeMetric->id,
        ];

        (new IncrementLifeMetric)->execute($request);

        $contact = $author->getContactInVault($vault);

        $this->assertDatabaseHas('contact_life_metric', [
            'contact_id' => $contact->id,
            'life_metric_id' => $lifeMetric->id,
        ]);
    }
}
