<?php

namespace Tests\Unit\Services\Account\gender;

use Tests\TestCase;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Services\Account\Gender\DestroyGender;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyGenderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_gender()
    {
        $gender = factory(Gender::class)->create([]);

        $request = [
            'account_id' => $gender->account_id,
            'gender_id' => $gender->id,
        ];

        $genderService = new DestroyGender;
        $bool = $genderService->execute($request);

        $this->assertDatabaseMissing('genders', [
            'id' => $gender->id,
        ]);
    }

    public function test_it_throws_an_exception_if_account_is_not_linked_to_gender()
    {
        $account = factory(Account::class)->create([]);
        $gender = factory(Gender::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'gender_id' => $gender->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        (new DestroyGender)->execute($request);
    }

    public function test_it_throws_an_exception_if_ids_do_not_exist()
    {
        $request = [
            'account_id' => 11111111,
            'gender_id' => 11111111,
        ];

        $this->expectException(ValidationException::class);
        (new DestroyGender)->execute($request);
    }
}
