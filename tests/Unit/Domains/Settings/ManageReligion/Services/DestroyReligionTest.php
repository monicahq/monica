<?php

namespace Tests\Unit\Domains\Settings\ManageReligion\Services;

use App\Domains\Settings\ManageReligion\Services\DestroyReligion;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Religion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyReligionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_religion(): void
    {
        $ross = $this->createAdministrator();
        $religion = Religion::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $religion);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyReligion)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $religion = Religion::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $religion);
    }

    /** @test */
    public function it_fails_if_religion_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $religion = Religion::factory()->create();
        $this->executeService($ross, $ross->account, $religion);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $religion = Religion::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $religion);
    }

    private function executeService(User $author, Account $account, Religion $religion): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'religion_id' => $religion->id,
        ];

        (new DestroyReligion)->execute($request);

        $this->assertDatabaseMissing('religions', [
            'id' => $religion->id,
        ]);
    }
}
