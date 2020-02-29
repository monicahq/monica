<?php

namespace Tests\Unit\Services\Contact\ContactField;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactFieldType;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Contact\ContactField\CreateContactField;

class CreateContactFieldTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_stores_a_contact_field()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
        ]);

        $contactField = app(CreateContactField::class)->execute([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => 'john@doe.com',
        ]);

        $this->assertDatabaseHas('contact_fields', [
            'id' => $contactField->id,
            'account_id' => $account->id,
            'data' => 'john@doe.com',
        ]);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
        ]);

        $this->expectException(ValidationException::class);
        app(CreateContactField::class)->execute([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => '',
        ]);
    }

    /** @test */
    public function it_throws_an_exception_if_account_doesnt_exist()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
        ]);

        $this->expectException(ValidationException::class);
        app(CreateContactField::class)->execute([
            'account_id' => -1,
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => 'john@doe.com',
        ]);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_use_wrong_account()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create();
        $contactFieldType = factory(ContactFieldType::class)->create([
            'account_id' => $account->id,
        ]);

        $this->expectException(ValidationException::class);
        app(CreateContactField::class)->execute([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => '',
        ]);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_field_use_wrong_account()
    {
        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $contactFieldType = factory(ContactFieldType::class)->create();

        $this->expectException(ValidationException::class);
        app(CreateContactField::class)->execute([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
            'contact_field_type_id' => $contactFieldType->id,
            'data' => '',
        ]);
    }
}
