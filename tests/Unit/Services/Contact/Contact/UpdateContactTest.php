<?php

namespace Tests\Unit\Services\Contact\Contact;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Contact\UpdateContact;
use App\Models\Contact\Gender;

class UpdateContactTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_updates_a_contact()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'first_name' => 'john',
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'gender_id' => $contact->gender->id,
            'description' => 'this is a test',
            'is_partial' => false,
            'birthdate' => '1990-02-02',
            'birthdate_is_age_based' => false,
            'birthdate_is_year_unknown' => false,
            'birthdate_age' => 0,
            'birthdate_add_reminder' => true,
        ];

        $contactService = new UpdateContact;
        $contact = $contactService->execute($request);

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

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'gender_id' => $contact->gender->id,
            'description' => 'this is a test',
            'is_partial' => false,
            'birthdate' => '1990-02-02',
            'birthdate_is_age_based' => false,
            'birthdate_is_year_unknown' => false,
            'birthdate_age' => 0,
            'birthdate_add_reminder' => true,
        ];

        $this->expectException(MissingParameterException::class);

        $updateContact = new UpdateContact;
        $contact = $updateContact->execute($request);
    }

    public function test_it_throws_an_exception_if_account_doesnt_exist()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => 11111,
            'contact_id' => $contact->id,
            'first_name' => 'john',
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'gender_id' => $contact->gender->id,
            'description' => 'this is a test',
            'is_partial' => false,
            'birthdate' => '1990-02-02',
            'birthdate_is_age_based' => false,
            'birthdate_is_year_unknown' => false,
            'birthdate_age' => 0,
            'birthdate_add_reminder' => true,
        ];

        $this->expectException(MissingParameterException::class);

        $updateContact = (new UpdateContact)->execute($request);
    }
}
