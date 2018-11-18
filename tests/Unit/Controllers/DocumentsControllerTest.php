<?php

namespace Tests\Unit\Controllers;

use Tests\FeatureTestCase;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DocumentsControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    protected $jsonStructure = [
        'id',
        'object',
        'original_filename',
        'new_filename',
        'filesize',
        'type',
        'number_of_downloads',
        'contact',
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_the_list_of_documents()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        factory(Document::class, 100)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('GET', '/people/'.$contact->hashID().'/documents');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonStructure,
            ],
        ]);

        $this->assertCount(
            100,
            $response->decodeResponseJson()['data']
        );
    }
}
