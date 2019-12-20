<?php

namespace Tests\Unit\Services\Account\gender;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Account\CustomField;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Account\CustomField\DestroyCustomField;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyCustomFieldTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_custom_field()
    {
        $customField = factory(CustomField::class)->create([]);

        $request = [
            'account_id' => $customField->account_id,
            'custom_field_id' => $customField->id,
        ];

        app(DestroyCustomField::class)->execute($request);

        $this->assertDatabaseMissing('custom_fields', [
            'id' => $customField->id,
        ]);
    }

    public function test_it_throws_an_exception_if_account_is_not_linked_to_custom_field()
    {
        $account = factory(Account::class)->create([]);
        $customField = factory(CustomField::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'custom_field_id' => $customField->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        app(DestroyCustomField::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_ids_do_not_exist()
    {
        $request = [
            'account_id' => 11111111,
            'custom_field_id' => 11111111,
        ];

        $this->expectException(ValidationException::class);
        app(DestroyCustomField::class)->execute($request);
    }
}
