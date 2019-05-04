<?php

namespace Tests\Api\Account;

use Tests\ApiTestCase;
use App\Models\User\User;
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

        $response->assertStatus(200);

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
        $user->terms()->syncWithoutDetaching([$term->id => ['account_id' => $user->account->id]]);

        $response = $this->get('/api/me/compliance/'.$term->id);

        $response->assertJsonFragment([
            'signed' => true,
            'ip_address' => null,
        ]);

        $response->assertJsonStructure([
            'data' => [
                'signed',
                'signed_date',
                'ip_address',
                'user',
                'term',
            ],
        ]);
    }

    public function test_it_returns_method_not_found_if_no_policy_is_found()
    {
        $user = $this->signIn();

        $response = $this->get('/api/me/compliance/32455212');

        $response->assertStatus(404);

        $response->assertJsonFragment([
            'message' => 'The resource has not been found',
            'error_code' => 31,
        ]);
    }

    public function test_it_gets_all_the_compliances_signed_by_user()
    {
        $user = $this->signIn();
        $term = factory(Term::class)->create([]);
        $user->terms()->syncWithoutDetaching([$term->id => ['account_id' => $user->account_id]]);

        $term2 = factory(Term::class)->create([]);
        $user->terms()->syncWithoutDetaching([$term2->id => ['account_id' => $user->account_id]]);

        $response = $this->get('/api/me/compliance');

        $response->assertStatus(200);

        $response->assertJsonCount(2, 'data');
    }

    public function test_it_gets_no_compliances_signed_by_user()
    {
        $user = $this->signIn();

        $response = $this->get('/api/me/compliance');

        $response->assertStatus(200);

        $response->assertJsonCount(0, 'data');
    }

    public function test_it_tries_to_sign_latest_policy()
    {
        $user = $this->signIn();

        $response = $this->post('/api/me/compliance');

        $this->expectDataError($response, [
            'The ip address field is required.',
        ]);
    }

    public function test_it_signs_latest_policy()
    {
        $user = $this->signIn();
        $term = factory(Term::class)->create([]);
        $user->terms()->syncWithoutDetaching([$term->id => ['account_id' => $user->account_id]]);

        $term2 = factory(Term::class)->create([]);
        $user->terms()->syncWithoutDetaching([$term2->id => ['account_id' => $user->account_id]]);

        $params = [
            'ip_address' => '128.3.1.2',
        ];

        $response = $this->json('POST', '/api/me/compliance', $params);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'signed',
                'signed_date',
                'ip_address',
                'user',
                'term',
            ],
        ]);
    }
}
