<?php

namespace Tests\Unit\Services\Account\Place;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Occupation;
use App\Exceptions\MissingParameterException;
use App\Services\Contact\Occupation\UpdateOccupation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateOccupationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_an_occupation()
    {
        $occupation = factory(Occupation::class)->create([]);

        $request = [
            'account_id' => $occupation->account_id,
            'contact_id' => $occupation->contact_id,
            'company_id' => $occupation->company_id,
            'occupation_id' => $occupation->id,
            'title' => 'Fashion girl',
            'description' => null,
            'salary' => '30000',
        ];

        $occupationService = new UpdateOccupation;
        $occupation = $occupationService->execute($request);

        $this->assertDatabaseHas('occupations', [
            'id' => $occupation->id,
            'account_id' => $occupation->account_id,
            'contact_id' => $occupation->contact_id,
            'company_id' => $occupation->company_id,
            'title' => 'Fashion girl',
            'salary' => 30000,
        ]);

        $this->assertInstanceOf(
            Occupation::class,
            $occupation
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $occupation = factory(Occupation::class)->create([]);

        $request = [
            'name' => '199 Lafayette Street',
        ];

        $this->expectException(MissingParameterException::class);
        (new UpdateOccupation)->execute($request);
    }

    public function test_it_throws_an_exception_if_occupation_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create([]);
        $occupation = factory(Occupation::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'contact_id' => $occupation->contact_id,
            'company_id' => $occupation->company_id,
            'occupation_id' => $occupation->id,
            'title' => 'Fashion',
        ];

        $this->expectException(ModelNotFoundException::class);
        (new UpdateOccupation)->execute($request);
    }
}
