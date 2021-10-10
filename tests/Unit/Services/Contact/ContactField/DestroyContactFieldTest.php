<?php

namespace Tests\Unit\Services\Contact\ContactField;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\ContactField;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\ContactField\DestroyContactField;

class DestroyContactFieldTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_a_contact_field()
    {
        $contactField = factory(ContactField::class)->create();

        $request = [
            'account_id' => $contactField->account_id,
            'contact_field_id' => $contactField->id,
        ];

        app(DestroyContactField::class)->execute($request);

        $this->assertDatabaseMissing('contact_fields', [
            'id' => $contactField->id,
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create();

        $request = [
            'account_id' => $account->id,
        ];

        $this->expectException(ValidationException::class);

        app(DestroyContactField::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_field_doesnt_exist()
    {
        $account = factory(Account::class)->create();

        $request = [
            'account_id' => $account->id,
            'contact_field_id' => -1,
        ];

        $this->expectException(ValidationException::class);
        app(DestroyContactField::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_field_use_wrong_account()
    {
        $account = factory(Account::class)->create();
        $contactField = factory(ContactField::class)->create();

        $request = [
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
        ];

        $this->expectException(ModelNotFoundException::class);
        app(DestroyContactField::class)->execute($request);
    }
}
