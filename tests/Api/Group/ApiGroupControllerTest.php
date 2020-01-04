<?php

namespace Tests\Api\Group;

use Tests\ApiTestCase;
use App\Models\User\User;
use App\Models\Group\Group;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiGroupControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonGroups = [
        'id',
        'object',
        'name',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    private function createGroup(User $user): Group
    {
        factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $group = factory(Group::class)->create([
            'account_id' => $user->account_id,
        ]);

        return $group;
    }

    /** @test */
    public function it_gets_a_list_of_groups()
    {
        $user = $this->signin();

        for ($i = 0; $i < 3; $i++) {
            $this->createGroup($user);
        }

        $response = $this->json('GET', '/api/groups');

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
                '*' => $this->jsonGroups,
            ],
        ]);
    }

    /** @test */
    public function it_applies_the_limit_parameter_in_search()
    {
        $user = $this->signin();

        for ($i = 0; $i < 10; $i++) {
            $this->createGroup($user);
        }

        $response = $this->json('GET', '/api/groups?limit=1');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '1',
            'last_page' => 10,
        ]);

        $response = $this->json('GET', '/api/groups?limit=2');

        $response->assertJsonFragment([
            'total' => 10,
            'current_page' => 1,
            'per_page' => '2',
            'last_page' => 5,
        ]);
    }

    /** @test */
    public function it_gets_a_group()
    {
        $user = $this->signin();

        $group = $this->createGroup($user);

        $response = $this->json('GET', '/api/groups/'.$group->id);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->jsonGroups,
        ]);
    }

    /** @test */
    public function it_creates_a_group()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/api/groups', [
            'account_id' => $user->account_id,
            'name' => 'Happy family',
        ]);

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonGroups,
        ]);
    }

    /** @test */
    public function it_updates_a_group()
    {
        $user = $this->signin();

        $group = $this->createGroup($user);

        $response = $this->json('PUT', '/api/groups/'.$group->id, [
            'name' => 'Brave family',
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonGroups,
        ]);
    }

    /** @test */
    public function it_destroys_a_group()
    {
        $user = $this->signin();

        $group = $this->createGroup($user);

        $response = $this->delete('/api/groups/'.$group->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'deleted' => true,
            'id' => $group->id,
        ]);
    }

    /** @test */
    public function it_attaches_contacts_to_a_group()
    {
        $user = $this->signin();

        $group = $this->createGroup($user);
        $contactA = factory(Contact::class)->create([
            'account_id' => $group->account_id,
        ]);
        $contactB = factory(Contact::class)->create([
            'account_id' => $group->account_id,
        ]);
        $contactC = factory(Contact::class)->create([
            'account_id' => $group->account_id,
        ]);

        $response = $this->json('POST', '/api/groups/'.$group->id.'/attach', [
            'contacts' => [
                $contactA->id, $contactB->id, $contactC->id,
            ],
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => $this->jsonGroups,
        ]);

        $this->assertDatabaseHas('contact_group', [
            'group_id' => $group->id,
            'contact_id' => $contactA->id,
        ]);

        $this->assertDatabaseHas('contact_group', [
            'group_id' => $group->id,
            'contact_id' => $contactB->id,
        ]);

        $this->assertDatabaseHas('contact_group', [
            'group_id' => $group->id,
            'contact_id' => $contactC->id,
        ]);
    }
}
