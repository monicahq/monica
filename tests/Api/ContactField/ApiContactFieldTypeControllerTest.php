<?php

namespace Tests\Api\Contact;

use Tests\ApiTestCase;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactField;
use App\Models\Contact\ContactFieldType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiContactFieldTypeControllerTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonStructureContactFieldType = [
        'id',
        'object',
        'name',
        'protocol',
        'delible',
        'type',
        'account' => [
            'id',
        ],
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_a_contact_field_type()
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

        $response = $this->json('GET', '/api/contactfieldtypes/'.$field->id);

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $field->id,
            'object' => 'contactfieldtype',
        ]);

        $response->assertJsonStructure([
            'data' => $this->jsonStructureContactFieldType,
        ]);
    }
}
