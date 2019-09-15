<?php

namespace Tests\Unit\Services\Contact\Contact;

use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Models\Contact\Reminder;
use App\Models\Instance\SpecialDate;
use Illuminate\Validation\ValidationException;
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
            'is_date_known' => false,
            'add_reminder' => false,
        ];

        app(UpdateDeceasedInformation::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account->id,
            'is_dead' => 1,
        ]);

        // now set the contact as not dead anymore (a zombie, basically)
        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_deceased' => false,
            'is_date_known' => false,
            'add_reminder' => false,
        ];

        app(UpdateDeceasedInformation::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account->id,
            'is_dead' => 0,
            'deceased_special_date_id' => null,
            'deceased_reminder_id' => null,
        ]);
    }

    public function test_it_sets_a_complete_date()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_deceased' => true,
            'is_date_known' => true,
            'day' => 10,
            'month' => 10,
            'year' => 1980,
            'add_reminder' => false,
        ];

        $contact = app(UpdateDeceasedInformation::class)->execute($request);

        $specialDate = SpecialDate::where('contact_id', $contact->id)->first();

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

    public function test_it_sets_a_complete_date_with_unknown_year()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_deceased' => true,
            'is_date_known' => true,
            'day' => 10,
            'month' => 10,
            'year' => 0,
            'add_reminder' => false,
        ];

        $contact = app(UpdateDeceasedInformation::class)->execute($request);

        $specialDate = SpecialDate::where('contact_id', $contact->id)->first();

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

    public function test_it_sets_a_complete_date_and_sets_a_reminder()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_deceased' => true,
            'is_date_known' => true,
            'day' => 10,
            'month' => 10,
            'year' => 1980,
            'add_reminder' => true,
        ];

        $contact = app(UpdateDeceasedInformation::class)->execute($request);

        $specialDate = SpecialDate::where('contact_id', $contact->id)->first();
        $reminder = Reminder::where('contact_id', $contact->id)->first();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account_id,
            'deceased_special_date_id' => $specialDate->id,
            'deceased_reminder_id' => $reminder->id,
        ]);
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_date_known' => true,
            'day' => 10,
            'month' => 10,
            'year' => 1980,
            'add_reminder' => false,
        ];

        $this->expectException(ValidationException::class);
        app(UpdateDeceasedInformation::class)->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_and_account_are_not_linked()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => 11111111,
            'contact_id' => $contact->id,
            'is_deceased' => true,
            'is_date_known' => true,
            'day' => 10,
            'month' => 10,
            'year' => 1980,
            'add_reminder' => false,
        ];

        $this->expectException(ValidationException::class);
        app(UpdateDeceasedInformation::class)->execute($request);
    }

    public function test_it_removes_deceased_reminder()
    {
        $reminder = factory(Reminder::class)->create([]);
        $contact = factory(Contact::class)->create([
            'account_id' => $reminder->account->id,
            'deceased_reminder_id' => $reminder->id,
        ]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_deceased' => true,
            'is_date_known' => true,
            'day' => 10,
            'month' => 10,
            'year' => 1980,
            'add_reminder' => true,
        ];

        app(UpdateDeceasedInformation::class)->execute($request);

        $this->assertDatabaseMissing('reminders', [
            'id' => $reminder->id,
        ]);
    }

    public function test_it_removes_deceased_special_date()
    {
        $special_date = factory(SpecialDate::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $special_date->account->id,
            'deceased_special_date_id' => $special_date->id,
        ]);

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'is_deceased' => true,
            'is_date_known' => true,
            'day' => 10,
            'month' => 10,
            'year' => 1980,
            'add_reminder' => true,
        ];

        app(UpdateDeceasedInformation::class)->execute($request);

        $this->assertDatabaseMissing('special_dates', [
            'id' => $special_date->id,
        ]);
    }
}
