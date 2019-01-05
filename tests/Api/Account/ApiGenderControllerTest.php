<?php

namespace Tests\Api\Account;

use Tests\ApiTestCase;
use App\Models\Contact\Gender;
use App\Models\Account\Account;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiGenderControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonGender = [
        'id',
        'object',
        'name',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_a_list_of_genders()
    {
        $user = $this->signin();

        factory(Gender::class, 3)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('GET', '/api/genders');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonGender],
        ]);
    }

    public function test_it_applies_the_limit_parameter_in_search()
    {
        $user = $this->signin();

        factory(Gender::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/genders?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/genders?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '2',
            'last_page' => 5,
        ]);
    }

    public function test_it_gets_one_gender()
    {
        $user = $this->signin();

        $gender = factory(Gender::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('get', '/api/genders/'.$gender->id);

        $response->assertstatus(200);
        $response->assertjsonstructure([
            'data' => $this->jsonGender,
        ]);
        $response->assertjsonfragment([
            'object' => 'gender',
            'id' => $gender->id,
        ]);
    }

    public function test_it_cant_get_a_gender_with_unexistent_id()
    {
        $user = $this->signin();

        $response = $this->json('get', '/api/genders/0');

        $this->expectnotfound($response);
    }

    public function test_it_creates_a_gender()
    {
        $user = $this->signin();

        $response = $this->json('post', '/api/genders', [
            'name' => 'man',
        ]);

        $response->assertstatus(201);
        $response->assertjsonstructure([
            'data' => $this->jsonGender,
        ]);

        $genderId = $response->json('data.id');

        $response->assertjsonfragment([
            'object' => 'gender',
            'id' => $genderId,
        ]);

        $this->assertdatabasehas('genders', [
            'account_id' => $user->account->id,
            'id' => $genderId,
            'name' => 'man',
        ]);
    }

    public function test_it_updates_a_gender()
    {
        $user = $this->signin();

        $gender = factory(Gender::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('put', '/api/genders/'.$gender->id, [
            'name' => 'man',
        ]);

        $response->assertstatus(200);

        $response->assertjsonstructure([
            'data' => $this->jsonGender,
        ]);

        $genderId = $response->json('data.id');

        $this->assertequals($gender->id, $genderId);

        $response->assertjsonfragment([
            'object' => 'gender',
            'id' => $genderId,
        ]);

        $this->assertDatabaseHas('genders', [
            'account_id' => $user->account->id,
            'id' => $genderId,
            'name' => 'man',
        ]);
    }

    public function test_it_cant_update_a_gender_if_account_is_not_linked_to_gender()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create([]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('put', '/api/genders/'.$gender->id, [
            'name' => 'man',
        ]);

        $this->expectnotfound($response);
    }

    public function test_it_deletes_a_gender()
    {
        $user = $this->signin();

        $gender = factory(Gender::class)->create([
            'account_id' => $user->account->id,
        ]);

        $response = $this->json('delete', '/api/genders/'.$gender->id);

        $response->assertstatus(200);

        $this->assertdatabasemissing('genders', [
            'account_id' => $user->account->id,
            'id' => $gender->id,
        ]);
    }

    public function test_it_cant_delete_a_gender_if_gender_doesnt_exist()
    {
        $user = $this->signin();

        $response = $this->json('delete', '/api/genders/0');

        $this->expectDataError($response, [
            'The selected gender id is invalid.',
        ]);
    }
}
