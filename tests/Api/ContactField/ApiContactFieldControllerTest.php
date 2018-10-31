<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactField;
use App\Models\Contact\ContactFieldType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiContactFieldControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureContactField = [
        'id',
        'object',
        'content',
        'contact_field_type',
        'account' => [
            'id',
        ],
        'contact',
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_a_contact_field()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        $field = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'contact_field_type_id' => $field->id,
        ]);

        $response = $this->json('GET', '/api/contactfields/'.$contactField->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $contactField->id,
            'object' => 'contactfield',
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContactField,
        ]);
    }
}
