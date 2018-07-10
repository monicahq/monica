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

    public function test_contact_field_query_all_account()
    {
        $firstuser = $this->signin();
        $firstcontact = factory(Contact::class)->create([
            'account_id' => $firstuser->account->id,
            'first_name' => 'Bad',
        ]);
        $firstfield = factory(ContactFieldType::class)->create([
            'account_id' => $firstuser->account_id,
        ]);
        $contactField = factory(ContactField::class)->create([
            'contact_id' => $firstcontact->id,
            'account_id' => $firstuser->account_id,
            'contact_field_type_id' => $firstfield->id,
        ]);

        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $field = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);
        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'contact_field_type_id' => $field->id,
        ]);

        $response = $this->json('GET', '/api/contacts?with=contactfields&page=1&limit=100&query=email:john@doe');

        $response->assertStatus(200);
        // Assure that firstcontact from other account is not get (wrong filter on account id)
        $response->assertJsonMissing([
            'id' => $firstcontact->id,
            'first_name' => 'Bad',
            'account' => [
                'id' => $firstuser->account->id,
            ],
        ]);
    }

    public function test_contact_query_internationalphone()
    {
        $user = $this->signin();
        $contact = factory(Contact::class)->create([
            'account_id' => $user->account->id,
        ]);
        $field = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
            'name' => 'Phone',
            'protocol' => 'tel:',
            'type' => 'phone',
            ]);
        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'contact_field_type_id' => $field->id,
            'data' => '+447007007007',
        ]);

        $response = $this->json('GET', '/api/contacts?query=Phone:%2B447007007007');

        $response->assertStatus(200);
        $response->assertJsonFragment([
                'id' => $contact->id,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'account' => [
                    'id' => $user->account->id,
                ],
        ]);
    }
}
