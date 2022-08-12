<?php

namespace Tests\Api\Account;

use Tests\ApiTestCase;
use App\Models\Account\Account;
use App\Models\Account\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiCompanyControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonCompany = [
        'id',
        'object',
        'name',
        'website',
        'number_of_employees',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    /** @test */
    public function it_gets_a_list_of_companies()
    {
        $user = $this->signin();

        factory(Company::class, 3)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/companies');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonCompany],
        ]);
    }

    /** @test */
    public function it_applies_the_limit_parameter_in_search()
    {
        $user = $this->signin();

        factory(Company::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/companies?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => 1,
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/companies?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => 2,
            'last_page' => 5,
        ]);
    }

    /** @test */
    public function it_gets_one_company()
    {
        $user = $this->signin();

        $company = factory(Company::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('get', '/api/companies/'.$company->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonCompany,
        ]);
        $response->assertJsonFragment([
            'object' => 'company',
            'id' => $company->id,
        ]);
    }

    /** @test */
    public function it_cant_get_a_call_with_unexistent_id()
    {
        $user = $this->signin();

        $response = $this->json('get', '/api/companies/0');

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_creates_a_company()
    {
        $user = $this->signin();

        $response = $this->json('post', '/api/companies', [
            'name' => 'Central Perk',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonCompany,
        ]);

        $companyId = $response->json('data.id');

        $response->assertJsonFragment([
            'object' => 'company',
            'id' => $companyId,
        ]);

        $this->assertDatabaseHas('companies', [
            'account_id' => $user->account_id,
            'id' => $companyId,
            'name' => 'Central Perk',
            'number_of_employees' => null,
        ]);
    }

    /** @test */
    public function it_updates_a_company()
    {
        $user = $this->signin();

        $company = factory(Company::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('put', '/api/companies/'.$company->id, [
            'name' => 'Central Perk Central',
            'number_of_employees' => 30,
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonCompany,
        ]);

        $companyId = $response->json('data.id');

        $this->assertEquals($company->id, $companyId);

        $response->assertJsonFragment([
            'object' => 'company',
            'id' => $companyId,
        ]);

        $this->assertDatabaseHas('companies', [
            'account_id' => $user->account_id,
            'id' => $companyId,
            'name' => 'Central Perk Central',
            'number_of_employees' => 30,
        ]);
    }

    /** @test */
    public function it_cant_update_a_company_if_account_is_not_linked_to_company()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create([]);
        $company = factory(Company::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('put', '/api/companies/'.$company->id, [
            'name' => 'Central Perk',
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_deletes_a_company()
    {
        $user = $this->signin();

        $company = factory(Company::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('delete', '/api/companies/'.$company->id);

        $response->assertStatus(200);

        $this->assertdatabasemissing('companies', [
            'account_id' => $user->account_id,
            'id' => $company->id,
        ]);
    }

    /** @test */
    public function it_cant_delete_a_company_if_company_doesnt_exist()
    {
        $user = $this->signin();

        $response = $this->json('delete', '/api/companies/0');

        $this->expectDataError($response, [
            'The selected company id is invalid.',
        ]);
    }
}
