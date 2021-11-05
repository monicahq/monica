<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Helpers\DateHelper;
use App\Models\Contact\Call;
use App\Models\Contact\Contact;
use App\Services\Contact\Call\CreateCall;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CallsTest extends FeatureTestCase
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

    protected $jsonDashboardStructure = [
        'id',
        'called_at',
        'name',
        'contact_id',
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

    /** @test */
    public function it_gets_last_talked_to()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $referenceDate = now();

        app(CreateCall::class)->execute([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
            'called_at' => $referenceDate->format('Y-m-d'),
        ]);

        $response = $this->json('GET', "/people/{$contact->hashId()}/calls/last");

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'last_talked_to',
        ]);

        $this->assertEquals($response->json('last_talked_to'), DateHelper::getShortDate($referenceDate));
    }

    /** @test */
    public function it_gets_a_empty_last_talked_to()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->json('GET', "/people/{$contact->hashId()}/calls/last");

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'last_talked_to',
        ]);

        $this->assertNull($response->json('last_talked_to'));
    }

    public function test_dashboard_calls()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
            'first_name' => 'Éric',
            'last_name' => 'Çezt',
        ]);

        factory(Call::class, 10)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('GET', '/dashboard/calls');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->jsonDashboardStructure,
        ]);

        $this->assertCount(
            10,
            $response->decodeResponseJson()
        );
    }
}
