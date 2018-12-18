<?php

namespace Tests\Unit\Controllers\Contact;

use Tests\FeatureTestCase;
use App\Models\Contact\Call;
use App\Models\Contact\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CallsControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    protected $jsonStructure = [
        'id',
        'object',
        'called_at',
        'content',
        'contact',
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_the_list_of_calls()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        factory(Call::class, 10)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('GET', '/people/'.$contact->hashID().'/calls');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonStructure,
            ],
        ]);

        $this->assertCount(
            10,
            $response->decodeResponseJson()['data']
        );
    }
}
