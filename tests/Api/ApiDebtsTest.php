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

    public function test_debts_get_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $debt1 = factory(Debt::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $debt2 = factory(Debt::class)->create([
            'account_id' => $user->account->id,
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

    public function test_debts_get_contact_all()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $debt1 = factory(Debt::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $contact2 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $debt2 = factory(Debt::class)->create([
            'account_id' => $user->account->id,
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

    public function test_debts_get_contact_all_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/contacts/0/debts');

        $this->expectNotFound($response);
    }

    public function test_debts_get_one()
    {
        $user = $this->signin();
        $contact1 = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $debt1 = factory(Debt::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact1->id,
        ]);
        $debt2 = factory(Debt::class)->create([
            'account_id' => $user->account->id,
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

    public function test_debts_get_one_error()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/debts/0');

        $this->expectNotFound($response);
    }

    public function test_debts_create()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
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
            'amount' => 42,
            'amount_with_currency' => '$42.00',
            'reason' => 'that\'s why',
        ]);

        $this->assertGreaterThan(0, $debt_id);
        $this->assertDatabaseHas('debts', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $debt_id,
            'in_debt' => 'yes',
            'status' => 'inprogress',
            'amount' => 42,
            'reason' => 'that\'s why',
        ]);
    }

    public function test_debts_create_error()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
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

    public function test_debts_create_error_bad_account()
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

    public function test_debts_update()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $debt = factory(Debt::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('PUT', '/api/debts/'.$debt->id, [
            'contact_id' => $contact->id,
            'in_debt' => 'yes',
            'status' => 'completed',
            'amount' => 142,
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
            'amount' => 142,
            'reason' => 'voilà',
        ]);

        $this->assertGreaterThan(0, $debt_id);
        $this->assertDatabaseHas('debts', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $debt_id,
            'in_debt' => 'yes',
            'status' => 'completed',
            'amount' => 142,
            'reason' => 'voilà',
        ]);
    }

    public function test_debts_update_error()
    {
        $user = $this->signin();
        $debt = factory(Debt::class)->create([
            'account_id' => $user->account->id,
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

    public function test_dets_update_error_bad_account()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
        ]);
        $debt = factory(Debt::class)->create([
            'account_id' => $user->account->id,
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

    public function test_debts_delete()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $debt = factory(Debt::class)->create([
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
        ]);
        $this->assertDatabaseHas('debts', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $debt->id,
        ]);

        $response = $this->json('DELETE', '/api/debts/'.$debt->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('debts', [
            'account_id' => $user->account->id,
            'contact_id' => $contact->id,
            'id' => $debt->id,
        ]);
    }

    public function test_debts_delete_error()
    {
        $user = $this->signin();

        $response = $this->json('DELETE', '/api/debts/0');

        $this->expectNotFound($response);
    }
}
