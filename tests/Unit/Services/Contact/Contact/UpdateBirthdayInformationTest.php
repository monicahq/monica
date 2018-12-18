<?php

namespace Tests\Unit\Services\Contact\Contact;

use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Models\Instance\SpecialDate;
use App\Exceptions\MissingParameterException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Contact\Contact\UpdateBirthdayInformation;

class UpdateBirthdayInformationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_deletes_all_birthday_information()
    {
        // to delete birthday information, we need first to update the contact
        // with its birthday info, then update it again by indicating that
        // we don't know his birthday info
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_date_known' => true,
            'day' => 10,
            'month' => 10,
            'year' => 1980,
            'is_age_based' => false,
            'age' => 0,
            'add_reminder' => false,
        ];

        $birthdayService = new UpdateBirthdayInformation;
        $contact = $birthdayService->execute($request);

        $specialDate = SpecialDate::where('contact_id', $contact->id)->first();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account->id,
            'birthday_special_date_id' => $specialDate->id,
        ]);

        $this->assertDatabaseHas('special_dates', [
            'id' => $specialDate->id,
            'account_id' => $contact->account->id,
            'is_age_based' => false,
        ]);

        // then we update it again
        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_date_known' => false,
        ];

        $birthdayService = new UpdateBirthdayInformation;
        $contact = $birthdayService->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account->id,
            'birthday_special_date_id' => null,
        ]);
    }

    public function test_it_sets_a_date_if_age_is_provided()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_date_known' => true,
            'is_age_based' => true,
            'age' => 10,
        ];

        $birthdayService = new UpdateBirthdayInformation;
        $contact = $birthdayService->execute($request);

        $specialDate = SpecialDate::where('contact_id', $contact->id)->first();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account->id,
            'birthday_special_date_id' => $specialDate->id,
        ]);

        $this->assertDatabaseHas('special_dates', [
            'id' => $specialDate->id,
            'account_id' => $contact->account->id,
            'is_age_based' => true,
        ]);
    }

    public function test_it_sets_a_complete_date()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_date_known' => true,
            'day' => 10,
            'month' => 10,
            'year' => 1980,
            'is_age_based' => false,
            'add_reminder' => false,
        ];

        $birthdayService = new UpdateBirthdayInformation;
        $contact = $birthdayService->execute($request);

        $specialDate = SpecialDate::where('contact_id', $contact->id)->first();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account->id,
            'birthday_special_date_id' => $specialDate->id,
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
            'is_date_known' => true,
            'day' => 10,
            'month' => 10,
            'year' => 1980,
            'is_age_based' => false,
            'add_reminder' => true,
        ];

        $birthdayService = new UpdateBirthdayInformation;
        $contact = $birthdayService->execute($request);

        $specialDate = SpecialDate::where('contact_id', $contact->id)->first();

        $this->assertNotNull($specialDate->reminder_id);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'day' => 10,
            'month' => 10,
            'year' => 1980,
            'is_age_based' => false,
            'add_reminder' => false,
        ];

        $this->expectException(MissingParameterException::class);

        $updateContact = new UpdateBirthdayInformation;
        $updateContact->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_and_account_are_not_linked()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => 11111111,
            'contact_id' => $contact->id,
            'is_date_known' => true,
            'day' => 10,
            'month' => 10,
            'year' => 1980,
            'is_age_based' => false,
            'add_reminder' => false,
        ];

        $this->expectException(MissingParameterException::class);

        (new UpdateBirthdayInformation)->execute($request);
    }
}
