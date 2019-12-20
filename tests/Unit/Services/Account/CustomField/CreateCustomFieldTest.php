<?php

namespace Tests\Unit\Services\Account\CustomField;

use Tests\TestCase;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Account\CustomField;
use App\Services\Account\CustomField\CreateCustomField;
use App\Services\Account\Gender\CreateGender;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateCustomFieldTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_a_custom_field()
    {
        $account = factory(Account::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'name' => 'work information',
        ];

        $customField = app(CreateCustomField::class)->execute($request);

        $this->assertDatabaseHas('custom_fields', [
            'id' => $customField->id,
            'account_id' => $account->id,
            'name' => 'work information',
        ]);

        $this->assertInstanceOf(
            CustomField::class,
            $customField
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        factory(Account::class)->create([]);

        $request = [
            'name' => 'man',
        ];

        $this->expectException(ValidationException::class);
        app(CreateCustomField::class)->execute($request);
    }
}
