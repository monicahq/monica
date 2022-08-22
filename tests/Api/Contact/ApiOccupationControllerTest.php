<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\Account\Account;
use App\Models\Account\Company;
use App\Models\Contact\Contact;
use App\Models\Contact\Occupation;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiOccupationControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonOccupation = [
        'id',
        'object',
        'title',
        'description',
        'salary',
        'salary_unit',
        'currently_works_here',
        'start_date',
        'end_date',
        'company' => [
            'id',
        ],
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_a_list_of_occupations()
    {
        $user = $this->signin();

        factory(Occupation::class, 3)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/occupations');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonOccupation],
        ]);
    }

    public function test_it_applies_the_limit_parameter_in_search()
    {
        $user = $this->signin();

        factory(Occupation::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/occupations?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => 1,
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/occupations?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => 2,
            'last_page' => 5,
        ]);
    }

    public function test_it_gets_one_occupation()
    {
        $user = $this->signin();

        $occupation = factory(Occupation::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('get', '/api/occupations/'.$occupation->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonOccupation,
        ]);
        $response->assertJsonFragment([
            'object' => 'occupation',
            'id' => $occupation->id,
        ]);
    }

    public function test_it_cant_get_a_occupation_with_unexistent_id()
    {
        $user = $this->signin();

        $response = $this->json('get', '/api/occupations/0');

        $this->expectNotFound($response);
    }

    public function test_it_creates_a_occupation()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);
        $company = factory(Company::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('post', '/api/occupations', [
            'contact_id' => $contact->id,
            'company_id' => $company->id,
            'title' => 'Waiter',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonOccupation,
        ]);

        $occupationId = $response->json('data.id');

        $response->assertJsonFragment([
            'object' => 'occupation',
            'id' => $occupationId,
        ]);

        $this->assertDatabaseHas('occupations', [
            'account_id' => $user->account_id,
            'id' => $occupationId,
            'title' => 'Waiter',
        ]);
    }

    public function test_it_updates_a_occupation()
    {
        $user = $this->signin();
        $occupation = factory(Occupation::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('put', '/api/occupations/'.$occupation->id, [
            'contact_id' => $occupation->contact_id,
            'company_id' => $occupation->company_id,
            'title' => 'Commissaire',
            'salary' => null,
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonOccupation,
        ]);

        $occupationId = $response->json('data.id');

        $this->assertEquals($occupation->id, $occupationId);

        $response->assertJsonFragment([
            'object' => 'occupation',
            'id' => $occupationId,
        ]);

        $this->assertDatabaseHas('occupations', [
            'account_id' => $user->account_id,
            'id' => $occupationId,
            'title' => 'Commissaire',
            'salary' => null,
        ]);
    }

    public function test_it_cant_update_a_occupation_if_account_is_not_linked_to_occupation()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create([]);
        $occupation = factory(Occupation::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('put', '/api/occupations/'.$occupation->id, [
            'contact_id' => $occupation->contact_id,
            'company_id' => $occupation->company_id,
            'title' => 'Commissaire',
            'salary' => null,
        ]);

        $this->expectNotFound($response);
    }

    public function test_it_deletes_a_occupation()
    {
        $user = $this->signin();

        $occupation = factory(Occupation::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('delete', '/api/occupations/'.$occupation->id);

        $response->assertStatus(200);

        $this->assertdatabasemissing('occupations', [
            'account_id' => $user->account_id,
            'id' => $occupation->id,
        ]);
    }

    public function test_it_cant_delete_a_occupation_if_occupation_doesnt_exist()
    {
        $user = $this->signin();

        $response = $this->json('delete', '/api/occupations/0');

        $this->expectDataError($response, [
            'The selected occupation id is invalid.',
        ]);
    }
}
