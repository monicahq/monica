<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function testSearchContactsReturnsCollection()
    {
        $contact = factory('App\Contact')->make();
        $searchResults = $contact->search($contact->first_name);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $searchResults);
    }

    /** @test */
    public function testSearchContactsThroughFirstNameAndResultContainsContact()
    {
        $contact = factory('App\Contact')->create([ 'first_name' => 'FirstName' ]);
        $searchResults = $contact->search($contact->first_name);

        $this->assertTrue($searchResults->contains($contact));
    }

    /** @test */
    public function testSearchContactsThroughMiddleNameAndResultContainsContact()
    {
        $contact = factory('App\Contact')->create(['middle_name' => 'MiddleName']);
        $searchResults = $contact->search($contact->middle_name);

        $this->assertTrue($searchResults->contains($contact));
    }

    /** @test */
    public function testSearchContactsThroughLastNameAndResultContainsContact()
    {
        $contact = factory(\App\Contact::class)->create(['last_name' => 'LastName']);

        $searchResults = $contact->search($contact->last_name);

        $this->assertTrue($searchResults->contains($contact));
    }

    /** @test */
    public function testSearchContactsThroughEmailAndResultContainsContact()
    {
        $contact = factory(\App\Contact::class)->create([ 'email' => 'email@email.com' ]);
        $searchResults = $contact->search($contact->email);

        $this->assertTrue($searchResults->contains($contact));
    }

    /** @test */
    public function testSearchContactsThroughStreetAndResultContainsContact()
    {
        $contact = factory(\App\Contact::class)->create([ 'street' => 'Street' ]);
        $searchResults = $contact->search($contact->street);

        $this->assertTrue($searchResults->contains($contact));
    }

    /** @test */
    public function testSearchContactsThroughPostalCodeAndResultContainsContact()
    {
        $contact = factory(\App\Contact::class)->create([ 'postal_code' => '33080', ]);
        $searchResults = $contact->search($contact->postal_code);

        $this->assertTrue($searchResults->contains($contact));
    }

    /** @test */
    public function testSearchContactsThroughProvinceAndResultContainsContact()
    {
        $contact = factory(\App\Contact::class)->create([ 'province' => 'Province' ]);
        $searchResults = $contact->search($contact->province);

        $this->assertTrue($searchResults->contains($contact));
    }

    /** @test */
    public function testSearchContactsThroughFoodPreferencesAndResultContainsContact()
    {
        $contact = factory(\App\Contact::class)->create([ 'food_preferencies' => 'Food Preference' ]);
        $searchResults = $contact->search($contact->food_preferencies);

        $this->assertTrue($searchResults->contains($contact));
    }

    /** @test */
    public function testSearchContactsThroughJobAndResultContainsContact()
    {
        $contact = factory(\App\Contact::class)->create([ 'job' => 'Job' ]);
        $searchResults = $contact->search($contact->job);

        $this->assertTrue($searchResults->contains($contact));
    }

    /** @test */
    public function testSearchContactsThroughCompanyAndResultContainsContact()
    {
        $contact = factory(\App\Contact::class)->create(['company' => 'Company']);
        $searchResults = $contact->search($contact->company);

        $this->assertTrue($searchResults->contains($contact));
    }

    /** @test */
    public function testFailingSearchContacts()
    {
        $contact = factory(\App\Contact::class)->create(['first_name' => 'TestShouldFail']);
        $searchResults = $contact->search('TestWillSucceed');

        $this->assertFalse($searchResults->contains($contact));
    }
}
