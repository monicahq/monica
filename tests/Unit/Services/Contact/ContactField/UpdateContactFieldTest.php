<?php

namespace Tests\Unit\Services\Contact\ContactField;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\ContactField;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\ContactField\UpdateContactField;

class UpdateContactFieldTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_contact_field()
    {
        $contactField = factory(ContactField::class)->create();

        $request = [
            'account_id' => $contactField->account_id,
            'contact_field_id' => $contactField->id,
            'contact_id' => $contactField->contact_id,
            'contact_field_type_id' => $contactField->contactFieldType->id,
            'data' => 'mark@twain.com',
        ];

        $contactField = app(UpdateContactField::class)->execute($request);

        $this->assertDatabaseHas('contact_fields', [
            'id' => $contactField->id,
            'account_id' => $contactField->account_id,
            'data' => 'mark@twain.com',
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $contactField = factory(ContactField::class)->create();

        $request = [
            'account_id' => $contactField->account_id,
            'contact_field_id' => $contactField->id,
            'contact_id' => $contactField->contact_id,
            'contact_field_type_id' => $contactField->contactFieldType->id,
            'data' => null,
        ];

        $this->expectException(ValidationException::class);
        app(UpdateContactField::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_account_doesnt_exist()
    {
        $contactField = factory(ContactField::class)->create([]);

        $request = [
            'account_id' => -1,
            'contact_field_id' => $contactField->id,
            'contact_id' => $contactField->contact_id,
            'contact_field_type_id' => $contactField->contactFieldType->id,
            'data' => 'mark@twain.com',
        ];

        $this->expectException(ValidationException::class);
        app(UpdateContactField::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_field_doesnt_exist()
    {
        $contactField = factory(ContactField::class)->create();

        $request = [
            'account_id' => $contactField->account_id,
            'contact_field_id' => -1,
            'contact_id' => $contactField->contact_id,
            'contact_field_type_id' => $contactField->contactFieldType->id,
            'data' => 'mark@twain.com',
        ];

        $this->expectException(ValidationException::class);
        app(UpdateContactField::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_field_type_is_wrong_account()
    {
        $account = factory(Account::class)->create();
        $contactField = factory(ContactField::class)->create();

        $request = [
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'contact_id' => $contactField->contact_id,
            'contact_field_type_id' => $contactField->contactFieldType->id,
            'data' => 'mark@twain.com',
        ];

        $this->expectException(ModelNotFoundException::class);
        app(UpdateContactField::class)->execute($request);
    }
}
