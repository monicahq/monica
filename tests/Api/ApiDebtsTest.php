<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use App\Models\Contact\Debt;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Settings\Currency;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiDebtsTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonDebt = [
        'id',
        'object',
        'in_debt',
        'status',
        'amount',
        'amount_with_currency',
        'reason',
        'account' => [
            'id',
        ],
        'contact' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    /** @test */
    public function it_gets_all_the_debts()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $debt1 = factory(Debt::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $debt2 = factory(Debt::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/debts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonDebt],
        ]);
        $response->assertJsonFragment([
            'object' => 'debt',
            'id' => $debt1->id,
        ]);
        $response->assertJsonFragment([
            'object' => 'debt',
            'id' => $debt2->id,
        ]);
    }

    /** @test */
    public function it_gets_all_the_debts_for_a_given_contact()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $debt1 = factory(Debt::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $debt2 = factory(Debt::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact2->id,
        ]);

        $response = $this->json('GET', '/api/contacts/'.$contact1->id.'/debts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonDebt],
        ]);
        $response->assertJsonFragment([
            'object' => 'debt',
            'id' => $debt1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'debt',
            'id' => $debt2->id,
        ]);
    }

    /** @test */
    public function it_cant_get_debts_from_an_invalid_contact()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/debts');

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_gets_one_debt()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $debt1 = factory(Debt::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);
        $debt2 = factory(Debt::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact1->id,
        ]);

        $response = $this->json('GET', '/api/debts/'.$debt1->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonDebt,
        ]);
        $response->assertJsonFragment([
            'object' => 'debt',
            'id' => $debt1->id,
        ]);
        $response->assertJsonMissingExact([
            'object' => 'debt',
            'id' => $debt2->id,
        ]);
    }

    /** @test */
    public function it_cant_get_a_debt_with_an_invalid_id()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/debts/0');

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_creates_a_debt()
    {
        $user = $this->signin();
        $user->locale = 'fr';
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $currency = factory(Currency::class)->create([
            'iso' => 'USD',
            'symbol' => '$',
        ]);
        $user->currency()->associate($currency);
        $user->save();

        $response = $this->json('POST', '/api/debts', [
            'contact_id' => $contact->id,
            'in_debt' => 'yes',
            'status' => 'inprogress',
            'amount' => 42,
            'reason' => 'that\'s why',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonDebt,
        ]);
        $debt_id = $response->json('data.id');
        $response->assertJsonFragment([
            'object' => 'debt',
            'id' => $debt_id,
            'in_debt' => 'yes',
            'status' => 'inprogress',
            'amount' => '42.00',
            'value' => '42,00',
            'amount_with_currency' => '42,00'.chr(0xA0).'$US',
            'reason' => 'that\'s why',
        ]);

        $this->assertGreaterThan(0, $debt_id);
        $this->assertDatabaseHas('debts', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $debt_id,
            'in_debt' => 'yes',
            'status' => 'inprogress',
            'amount' => 4200,
            'reason' => 'that\'s why',
        ]);
    }

    /** @test */
    public function it_cant_create_a_debt_if_fields_are_missing()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/debts', [
            'contact_id' => $contact->id,
        ]);

        $this->expectDataError($response, [
            'The in debt field is required.',
            'The status field is required.',
            'The amount field is required.',
        ]);
    }

    /** @test */
    public function it_cant_create_a_debt_with_a_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('POST', '/api/debts', [
            'contact_id' => $contact->id,
            'in_debt' => 'yes',
            'status' => 'inprogress',
            'amount' => 42,
            'reason' => 'that\'s why',
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_updates_a_debt()
    {
        $user = $this->signin();
        $user->locale = 'fr';
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $debt = factory(Debt::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/debts/'.$debt->id, [
            'contact_id' => $contact->id,
            'in_debt' => 'yes',
            'status' => 'completed',
            'amount' => 142.01,
            'reason' => 'voilà',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonDebt,
        ]);
        $debt_id = $response->json('data.id');
        $this->assertEquals($debt->id, $debt_id);
        $response->assertJsonFragment([
            'object' => 'debt',
            'id' => $debt_id,
            'in_debt' => 'yes',
            'status' => 'completed',
            'amount' => '142.01',
            'value' => '142,01',
            'amount_with_currency' => '142,01'.chr(0xA0).'$US',
            'reason' => 'voilà',
        ]);

        $this->assertGreaterThan(0, $debt_id);
        $this->assertDatabaseHas('debts', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $debt_id,
            'in_debt' => 'yes',
            'status' => 'completed',
            'amount' => 14201,
            'reason' => 'voilà',
        ]);
    }

    /** @test */
    public function it_cant_update_a_debt_with_missing_parameters()
    {
        $user = $this->signin();
        $debt = factory(Debt::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('PUT', '/api/debts/'.$debt->id, [
            'contact_id' => $debt->contact_id,
        ]);

        $this->expectDataError($response, [
            'The in debt field is required.',
            'The status field is required.',
            'The amount field is required.',
        ]);
    }

    /** @test */
    public function it_cant_update_a_debt_with_a_wrong_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $debt = factory(Debt::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/debts/'.$debt->id, [
            'contact_id' => $contact->id,
            'in_debt' => 'yes',
            'status' => 'completed',
            'amount' => 142,
            'reason' => 'voilà',
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_deletes_a_debt()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $debt = factory(Debt::class)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('debts', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $debt->id,
        ]);

        $response = $this->json('DELETE', '/api/debts/'.$debt->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('debts', [
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'id' => $debt->id,
        ]);
    }

    /** @test */
    public function it_cant_delete_a_debt_with_an_invalid_id()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/debts/0');

        $this->expectNotFound($response);
    }
}
