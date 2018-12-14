<?php

namespace Tests\Unit\Services\Contact\Contact;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\Contact\Contact\UpdateDeceasedInformation;
use App\Models\Contact\Gender;

class UpdateDeceasedInformationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_sets_a_date_if_age_is_provided()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
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
            'deceased_date' => '1990-02-02',
            'is_age_based' => false,
            'is_year_unknown' => true,
            'add_reminder' => true,
        ];

        $this->expectException(MissingParameterException::class);

        $updateContact = (new UpdateDeceasedInformation)->execute($request);
    }
}
