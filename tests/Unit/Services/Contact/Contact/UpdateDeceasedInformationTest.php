<?php

namespace Tests\Unit\Services\Contact\Contact;

use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Contact\Contact\UpdateDeceasedInformation;

class UpdateDeceasedInformationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_sets_contact_as_not_deceased()
    {
        // first we are going to update a contact and set it as deceased,
        // then we are going to update it again and set it as non deceased
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_deceased' => true,
            'deceased_date' => '1990-02-02',
            'is_age_based' => true,
            'is_year_unknown' => false,
            'age' => 30,
            'add_reminder' => false,
        ];

        $deceasedService = new UpdateDeceasedInformation;
        $specialDate = $deceasedService->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account->id,
            'is_dead' => true,
            'deceased_special_date_id' => $specialDate->id,
        ]);

        $this->assertDatabaseHas('special_dates', [
            'id' => $specialDate->id,
            'account_id' => $contact->account->id,
            'is_age_based' => true,
        ]);

        // now set the contact as not dead anymore (a zombie, basically)
        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_deceased' => false,
            'deceased_date' => '',
            'is_age_based' => false,
            'is_year_unknown' => false,
            'add_reminder' => false,
        ];

        $deceasedService = new UpdateDeceasedInformation;
        $deceasedService->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account->id,
            'is_dead' => false,
            'deceased_special_date_id' => null,
        ]);
    }

    public function test_it_sets_a_date_if_age_is_provided()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_deceased' => true,
            'deceased_date' => '1990-02-02',
            'is_age_based' => true,
            'is_year_unknown' => false,
            'age' => 30,
            'add_reminder' => false,
        ];

        $deceasedService = new UpdateDeceasedInformation;
        $specialDate = $deceasedService->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account->id,
            'deceased_special_date_id' => $specialDate->id,
        ]);

        $this->assertDatabaseHas('special_dates', [
            'id' => $specialDate->id,
            'account_id' => $contact->account->id,
            'is_age_based' => true,
        ]);
    }

    public function test_it_sets_a_date_if_month_and_day_are_provided()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_deceased' => true,
            'deceased_date' => '1990-02-02',
            'is_age_based' => false,
            'is_year_unknown' => true,
            'add_reminder' => false,
        ];

        $deceasedService = new UpdateDeceasedInformation;
        $specialDate = $deceasedService->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account->id,
            'deceased_special_date_id' => $specialDate->id,
        ]);

        $this->assertDatabaseHas('special_dates', [
            'id' => $specialDate->id,
            'account_id' => $contact->account->id,
            'is_age_based' => false,
            'is_year_unknown' => true,
        ]);
    }

    public function test_it_sets_a_date_if_month_and_day_are_provided_and_sets_a_reminder()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_deceased' => true,
            'deceased_date' => '1990-02-02',
            'is_age_based' => false,
            'is_year_unknown' => true,
            'add_reminder' => true,
        ];

        $deceasedService = new UpdateDeceasedInformation;
        $specialDate = $deceasedService->execute($request);

        $this->assertNotNull($specialDate->reminder_id);
    }

    public function test_it_sets_a_complete_date()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_deceased' => true,
            'deceased_date' => '1990-02-02',
            'is_age_based' => false,
            'is_year_unknown' => false,
            'add_reminder' => false,
        ];

        $deceasedService = new UpdateDeceasedInformation;
        $specialDate = $deceasedService->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account->id,
            'deceased_special_date_id' => $specialDate->id,
        ]);

        $this->assertDatabaseHas('special_dates', [
            'id' => $specialDate->id,
            'account_id' => $contact->account->id,
            'is_age_based' => false,
            'is_year_unknown' => false,
        ]);
    }

    public function test_it_sets_a_complete_date_and_sets_a_reminder()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_deceased' => true,
            'deceased_date' => '1990-02-02',
            'is_age_based' => false,
            'is_year_unknown' => false,
            'add_reminder' => true,
        ];

        $deceasedService = new UpdateDeceasedInformation;
        $specialDate = $deceasedService->execute($request);

        $this->assertNotNull($specialDate->reminder_id);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'deceased_date' => '1990-02-02',
            'is_age_based' => false,
            'is_year_unknown' => true,
            'add_reminder' => true,
        ];

        $this->expectException(MissingParameterException::class);

        $updateContact = new UpdateDeceasedInformation;
        $contact = $updateContact->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_and_account_are_not_linked()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => 11111111,
            'contact_id' => $contact->id,
            'is_deceased' => true,
            'deceased_date' => '1990-02-02',
            'is_age_based' => false,
            'is_year_unknown' => true,
            'add_reminder' => true,
        ];

        $this->expectException(MissingParameterException::class);

        $updateContact = (new UpdateDeceasedInformation)->execute($request);
    }
}
