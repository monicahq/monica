<?php

namespace Tests\Api\Family;

use Tests\ApiTestCase;
use App\Models\User\User;
use App\Models\Contact\Contact;
use App\Models\Contact\Conversation;
use App\Models\Contact\ContactFieldType;
use App\Models\Family\Family;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiFamilyControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonFamilies = [
        'id',
        'object',
        'name',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    private function createFamily(User $user) : Family
    {
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $family = factory(Family::class)->create([
            'account_id' => $user->account_id,
        ]);

        return $family;
    }

    /** @test */
    public function it_gets_a_list_of_families()
    {
        $user = $this->signin();

        for ($i = 0; $i < 3; $i++) {
            $this->createFamily($user);
        }

        $response = $this->json('GET', '/api/families');

        $response->assertStatus(200);

        $this->assertCount(
            3,
            $response->decodeResponseJson()['data']
        );

        $response->assertJsonFragment([
            'total' => 3,
            'current_page' => 1,
        ]);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonFamilies,
            ],
        ]);
    }

    /** @test */
    public function it_applies_the_limit_parameter_in_search()
    {
        $user = $this->signin();

        for ($i = 0; $i < 10; $i++) {
            $this->createFamily($user);
        }

        $response = $this->json('GET', '/api/families?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/families?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '2',
            'last_page' => 5,
        ]);
    }

    /** @test */
    public function it_gets_a_family()
    {
        $user = $this->signin();

        $family = $this->createFamily($user);

        $response = $this->json('GET', '/api/families/' . $family->id);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->jsonFamilies,
        ]);
    }

    /** @test */
    public function it_creates_a_family()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/families', [
            'account_id' => $user->account_id,
            'name' => 'Happy family',
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonFamilies,
        ]);
    }

    /** @test */
    public function it_updates_a_family()
    {
        $user = $this->signin();

        $family = $this->createFamily($user);

        $response = $this->json('PUT', '/api/families/' . $family->id, [
            'name' => 'Brave family',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonFamilies,
        ]);
    }

    /** @test */
    public function it_destroys_a_family()
    {
        $user = $this->signin();

        $family = $this->createFamily($user);

        $response = $this->delete('/api/families/' . $family->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'deleted' => true,
            'id' => $family->id,
        ]);
    }
}
