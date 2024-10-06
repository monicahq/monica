<?php

namespace Tests\Unit\Domains\Settings\ManageGenders\Services;

use App\Domains\Settings\ManageGenders\Services\DestroyGender;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\Gender;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestroyGenderTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_gender(): void
    {
        $ross = $this->createAdministrator();
        $gender = Gender::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $gender);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new DestroyGender)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $account = Account::factory()->create();
        $gender = Gender::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $account, $gender);
    }

    /** @test */
    public function it_fails_if_gender_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $ross = $this->createAdministrator();
        $gender = Gender::factory()->create();
        $this->executeService($ross, $ross->account, $gender);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $ross = $this->createUser();
        $gender = Gender::factory()->create([
            'account_id' => $ross->account_id,
        ]);
        $this->executeService($ross, $ross->account, $gender);
    }

    private function executeService(User $author, Account $account, Gender $gender): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'gender_id' => $gender->id,
        ];

        (new DestroyGender)->execute($request);

        $this->assertDatabaseMissing('genders', [
            'id' => $gender->id,
        ]);
    }
}
