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
        'type',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    /** @test */
    public function it_gets_a_list_of_genders()
    {
        $user = $this->signin();

        factory(Gender::class, 3)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/genders');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonGender],
        ]);
    }

    /** @test */
    public function it_applies_the_limit_parameter_in_search()
    {
        $user = $this->signin();

        factory(Gender::class, 10)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', '/api/genders?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => 1,
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/genders?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => 2,
            'last_page' => 5,
        ]);
    }

    /** @test */
    public function it_gets_one_gender()
    {
        $user = $this->signin();

        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('get', '/api/genders/'.$gender->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => $this->jsonGender,
        ]);
        $response->assertJsonFragment([
            'object' => 'gender',
            'id' => $gender->id,
        ]);
    }

    /** @test */
    public function it_cant_get_a_gender_with_unexistent_id()
    {
        $user = $this->signin();

        $response = $this->json('get', '/api/genders/0');

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_creates_a_gender()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/genders', [
            'name' => 'man',
            'type' => 'M',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => $this->jsonGender,
        ]);

        $genderId = $response->json('data.id');

        $response->assertJsonFragment([
            'object' => 'gender',
            'id' => $genderId,
        ]);

        $this->assertDatabasehas('genders', [
            'account_id' => $user->account_id,
            'id' => $genderId,
            'name' => 'man',
            'type' => 'M',
        ]);
    }

    /** @test */
    public function it_updates_a_gender()
    {
        $user = $this->signin();

        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('put', '/api/genders/'.$gender->id, [
            'name' => 'man',
            'type' => 'M',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonGender,
        ]);

        $genderId = $response->json('data.id');

        $this->assertEquals($gender->id, $genderId);

        $response->assertJsonFragment([
            'object' => 'gender',
            'id' => $genderId,
        ]);

        $this->assertDatabaseHas('genders', [
            'account_id' => $user->account_id,
            'id' => $genderId,
            'name' => 'man',
            'type' => 'M',
        ]);
    }

    /** @test */
    public function it_cant_update_a_gender_if_account_is_not_linked_to_gender()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create([]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('put', '/api/genders/'.$gender->id, [
            'name' => 'man',
            'type' => 'M',
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_cant_update_a_gender_if_account_is_not_linked_to_gender2()
    {
        $user = $this->signin();

        $account = factory(Account::class)->create([]);
        $gender = factory(Gender::class)->create([
            'account_id' => $account->id,
        ]);

        $response = $this->json('put', '/api/genders/'.$gender->id, [
            'account_id' => $account->id,
            'name' => 'man',
            'type' => 'M',
        ]);

        $this->expectNotFound($response);
    }

    /** @test */
    public function it_deletes_a_gender()
    {
        $user = $this->signin();

        $gender = factory(Gender::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('delete', '/api/genders/'.$gender->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('genders', [
            'account_id' => $user->account_id,
            'id' => $gender->id,
        ]);
    }

    /** @test */
    public function it_cant_delete_a_gender_if_gender_doesnt_exist()
    {
        $user = $this->signin();

        $response = $this->json('delete', '/api/genders/0');

        $this->expectDataError($response, [
            'The selected gender id is invalid.',
        ]);
    }
}
