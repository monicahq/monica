<?php

namespace Tests\Unit\Domains\Settings\ManageReligion\Services;

use App\Domains\Settings\ManageReligion\Services\UpdateReligionPosition;
use App\Models\Account;
use App\Models\Religion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateReligionPositionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_religion_position(): void
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
        (new UpdateReligionPosition)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = $this->createAccount();
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

    private function executeService(User $author, Account $account, Religion $religion): void
    {
        $religion1 = Religion::factory()->create([
            'account_id' => $account->id,
            'position' => 1,
        ]);
        $religion3 = Religion::factory()->create([
            'account_id' => $account->id,
            'position' => 3,
        ]);
        $religion4 = Religion::factory()->create([
            'account_id' => $account->id,
            'position' => 4,
        ]);

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'religion_id' => $religion->id,
            'new_position' => 3,
        ];

        $religion = (new UpdateReligionPosition)->execute($request);

        $this->assertDatabaseHas('religions', [
            'id' => $religion1->id,
            'account_id' => $account->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('religions', [
            'id' => $religion3->id,
            'account_id' => $account->id,
            'position' => 2,
        ]);
        $this->assertDatabaseHas('religions', [
            'id' => $religion4->id,
            'account_id' => $account->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('religions', [
            'id' => $religion->id,
            'account_id' => $account->id,
            'position' => 3,
        ]);

        $request['new_position'] = 2;

        $religion = (new UpdateReligionPosition)->execute($request);

        $this->assertDatabaseHas('religions', [
            'id' => $religion1->id,
            'account_id' => $account->id,
            'position' => 1,
        ]);
        $this->assertDatabaseHas('religions', [
            'id' => $religion3->id,
            'account_id' => $account->id,
            'position' => 3,
        ]);
        $this->assertDatabaseHas('religions', [
            'id' => $religion4->id,
            'account_id' => $account->id,
            'position' => 4,
        ]);
        $this->assertDatabaseHas('religions', [
            'id' => $religion->id,
            'account_id' => $account->id,
            'position' => 2,
        ]);

        $this->assertInstanceOf(
            Religion::class,
            $religion
        );
    }
}
