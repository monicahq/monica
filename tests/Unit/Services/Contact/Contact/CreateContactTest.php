<?php

namespace Tests\Unit\Services\Contact\Contact;

use Tests\TestCase;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Contact\CreateContact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CreateContactTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_stores_a_contact()
    {
        $account = factory(Account::class)->create([]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'first_name' => 'john',
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'gender_id' => $gender->id,
            'description' => 'this is a test',
            'is_partial' => false,
            'is_birthdate_known' => false,
            'is_deceased' => false,
            'is_deceased_date_known' => false,
        ];

        $contact = app(CreateContact::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account->id,
            'first_name' => 'john',
        ]);

        $this->assertInstanceOf(
            Contact::class,
            $contact
        );
    }

    public function test_it_stores_a_contact_without_gender()
    {
        $account = factory(Account::class)->create([]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'first_name' => 'john',
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'description' => 'this is a test',
            'is_partial' => false,
            'is_birthdate_known' => false,
            'is_deceased' => false,
            'is_deceased_date_known' => false,
        ];

        $contact = app(CreateContact::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account->id,
            'first_name' => 'john',
            'gender_id' => null,
        ]);

        $this->assertInstanceOf(
            Contact::class,
            $contact
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $account = factory(Account::class)->create([]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);

        $request = [
            'account_id' => $account->id,
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'gender_id' => $gender->id,
            'description' => 'this is a test',
            'is_partial' => false,
            'is_birthdate_known' => false,
            'is_deceased' => false,
            'is_deceased_date_known' => false,
        ];

        $this->expectException(ValidationException::class);
        app(CreateContact::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_account_doesnt_exist()
    {
        $gender = factory(Gender::class)->create([]);

        $request = [
            'account_id' => 111111111,
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'gender_id' => $gender->id,
            'description' => 'this is a test',
            'is_partial' => false,
            'is_birthdate_known' => false,
            'is_deceased' => false,
            'is_deceased_date_known' => false,
        ];

        $this->expectException(ValidationException::class);

        app(CreateContact::class)->execute($request);
    }
}
