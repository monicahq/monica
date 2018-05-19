<?php

namespace Tests\Api\Contact;

use App\User;
use Tests\ApiTestCase;
use App\Models\Settings\Term;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiUserControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureUser = [
        'id',
        'object',
        'first_name',
        'last_name',
        'email',
        'timezone',
        'currency',
        'locale',
        'is_policy_compliant',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_the_authenticated_user()
    {
        $user = $this->signIn();

        $response = $this->get('/api/me');

        $response->assertStatus(201);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureUser,
        ]);

        $response->assertJsonFragment([
            'first_name' => $user->first_name,
            'object' => 'user',
        ]);
    }

    public function test_it_tells_if_the_user_has_signed_a_given_policy()
    {
        $user = $this->signIn();

        $term = factory(Term::class)->create([]);
        $term->users()->sync($user->id);

        $response = $this->get('/api/me/compliance/'.$term->id);

        $response->assertJsonFragment([
            'signed' => true,
            'ip_address' => null,
        ]);
    }
}
