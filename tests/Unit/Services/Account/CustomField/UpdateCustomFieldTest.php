<?php

namespace Tests\Unit\Services\Account\Gender;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\CustomField;
use Illuminate\Validation\ValidationException;
use App\Services\Account\CustomField\UpdateCustomField;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateCustomFieldTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_custom_field()
    {
        $customField = factory(CustomField::class)->create([]);

        $request = [
            'account_id' => $customField->account_id,
            'custom_field_id' => $customField->id,
            'name' => 'man',
        ];

        $customField = app(UpdateCustomField::class)->execute($request);

        $this->assertDatabaseHas('custom_fields', [
            'id' => $customField->id,
            'account_id' => $customField->account_id,
            'name' => 'man',
        ]);

        $this->assertInstanceOf(
            CustomField::class,
            $customField
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        factory(CustomField::class)->create([]);

        $request = [
            'name' => 'man',
            'type' => 'X',
        ];

        $this->expectException(ValidationException::class);
        app(UpdateCustomField::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_custom_field_is_not_linked_to_account()
    {
        $account = factory(Account::class)->create([]);
        $customField = factory(CustomField::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'custom_field_id' => $customField->id,
            'name' => 'man',
        ];

        $this->expectException(ModelNotFoundException::class);
        app(UpdateCustomField::class)->execute($request);
    }
}
