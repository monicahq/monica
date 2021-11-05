<?php

namespace Tests\Unit\Services\Contact\Contact;

use Tests\TestCase;
use App\Models\User\User;
use App\Models\Contact\Contact;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Contact\UpdateContact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UpdateContactTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_updates_a_contact()
    {
        $contact = factory(Contact::class)->create([]);
        $user = factory(User::class)->create([]);

        $request = [
            'account_id' => $contact->account_id,
            'author_id' => $user->id,
            'contact_id' => $contact->id,
            'first_name' => 'john',
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'gender_id' => $contact->gender_id,
            'description' => 'this is a test',
            'is_partial' => false,
            'is_birthdate_known' => true,
            'birthdate_day' => 10,
            'birthdate_month' => 10,
            'birthdate_year' => 1980,
            'birthdate_is_age_based' => false,
            'birthdate_age' => 0,
            'birthdate_add_reminder' => false,
            'is_deceased' => true,
            'is_deceased_date_known' => true,
            'deceased_date_day' => 10,
            'deceased_date_month' => 10,
            'deceased_date_year' => 1980,
            'deceased_date_add_reminder' => true,
        ];

        $contact = app(UpdateContact::class)->execute($request);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'account_id' => $contact->account_id,
            'first_name' => 'john',
        ]);

        $this->assertInstanceOf(
            Contact::class,
            $contact
        );
    }

    /** @test */
    public function it_fails_if_contact_is_archived()
    {
        $contact = factory(Contact::class)->state('archived')->create([]);
        $user = factory(User::class)->create([]);

        $request = [
            'account_id' => $contact->account_id,
            'author_id' => $user->id,
            'contact_id' => $contact->id,
            'first_name' => 'john',
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'gender_id' => $contact->gender_id,
            'description' => 'this is a test',
            'is_partial' => false,
            'is_birthdate_known' => true,
            'birthdate_day' => 10,
            'birthdate_month' => 10,
            'birthdate_year' => 1980,
            'birthdate_is_age_based' => false,
            'birthdate_age' => 0,
            'birthdate_add_reminder' => false,
            'is_deceased' => true,
            'is_deceased_date_known' => true,
            'deceased_date_day' => 10,
            'deceased_date_month' => 10,
            'deceased_date_year' => 1980,
            'deceased_date_add_reminder' => true,
        ];

        $this->expectException(ValidationException::class);
        app(UpdateContact::class)->execute($request);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'gender_id' => $contact->gender_id,
            'description' => 'this is a test',
            'is_partial' => false,
            'is_birthdate_known' => true,
            'birthdate_day' => 10,
            'birthdate_month' => 10,
            'birthdate_year' => 1980,
            'birthdate_is_age_based' => false,
            'birthdate_age' => 0,
            'birthdate_add_reminder' => false,
            'is_deceased' => true,
            'is_deceased_date_known' => true,
            'deceased_date_day' => 10,
            'deceased_date_month' => 10,
            'deceased_date_year' => 1980,
            'deceased_date_add_reminder' => true,
        ];

        $this->expectException(ValidationException::class);
        app(UpdateContact::class)->execute($request);
    }

    /** @test */
    public function it_throws_an_exception_if_account_doesnt_exist()
    {
        $contact = factory(Contact::class)->create([]);
        $user = factory(User::class)->create([]);

        $request = [
            'account_id' => 11111,
            'author_id' => $user->id,
            'contact_id' => $contact->id,
            'first_name' => 'john',
            'middle_name' => 'franck',
            'last_name' => 'doe',
            'gender_id' => $contact->gender_id,
            'description' => 'this is a test',
            'is_partial' => false,
            'is_birthdate_known' => true,
            'birthdate_day' => 10,
            'birthdate_month' => 10,
            'birthdate_year' => 1980,
            'birthdate_is_age_based' => false,
            'birthdate_age' => 0,
            'birthdate_add_reminder' => false,
            'is_deceased' => true,
            'is_deceased_date_known' => true,
            'deceased_date_day' => 10,
            'deceased_date_month' => 10,
            'deceased_date_year' => 1980,
            'deceased_date_add_reminder' => true,
        ];

        $this->expectException(ValidationException::class);
        app(UpdateContact::class)->execute($request);
    }
}
