<?php

namespace Tests\Api;

use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiRelationshipControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    public function test_it_rejects_the_update_if_parameters_are_not_right()
    {
        $user = $this->signin();
        $contactA = factory('App\Contact')->create([
            'account_id' => $user->account_id,
        ]);
        $contactB = factory('App\Contact')->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('POST', '/api/relationships', [
                            'contact_is' => 'a',
                            'relationship_type_id' => 1,
                            'of_contact' => 1,
                        ]);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'message' => ['The tags field is required.'],
            'error_code' => 32,
        ]);
    }
}
