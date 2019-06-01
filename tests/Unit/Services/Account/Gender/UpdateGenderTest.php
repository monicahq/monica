<?php

namespace Tests\Unit\Services\Account\Gender;

use Tests\TestCase;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Services\Account\Gender\UpdateGender;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateGenderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_gender()
    {
        $gender = factory(Gender::class)->create([]);

        $request = [
            'account_id' => $gender->account->id,
            'gender_id' => $gender->id,
            'name' => 'man',
            'type' => 'M',
        ];

        $gender = app(UpdateGender::class)->execute($request);

        $this->assertDatabaseHas('genders', [
            'id' => $gender->id,
            'account_id' => $gender->account_id,
            'name' => 'man',
            'type' => 'M',
        ]);

        $this->assertInstanceOf(
            Gender::class,
            $gender
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $gender = factory(Gender::class)->create([]);

        $request = [
            'name' => 'man',
            'type' => 'X',
        ];

        $this->expectException(ValidationException::class);
        app(UpdateGender::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_place_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create([]);
        $gender = factory(Gender::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'gender_id' => $gender->id,
            'name' => 'man',
            'type' => 'M',
        ];

        $this->expectException(ModelNotFoundException::class);
        app(UpdateGender::class)->execute($request);
    }
}
