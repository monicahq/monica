<?php

namespace Tests\Unit\Services\Family\Family;

use Tests\TestCase;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Family\Family;
use App\Services\Family\Family\CreateFamily;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateFamilyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_a_family()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'name' => 'family',
        ];

        $family = app(CreateFamily::class)->execute($request);

        $this->assertDatabaseHas('families', [
            'id' => $family->id,
            'account_id' => $family->account_id,
            'name' => 'family',
        ]);

        $this->assertInstanceOf(
            Family::class,
            $family
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create([]);
        $request = [
            'account_id' => $account->id,
        ];

        $this->expectException(ValidationException::class);
        app(CreateFamily::class)->execute($request);
    }
}
