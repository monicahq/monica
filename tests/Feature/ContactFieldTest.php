<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\Contact\Contact;
use App\Models\Contact\ContactField;
use App\Models\Contact\ContactFieldType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactFieldTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /**
     * Returns an array containing a user object along with
     * a contact for that user.
     * @return array
     */
    private function fetchUser()
    {
        $user = $this->signIn();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        return [$user, $contact];
    }

    public function test_user_can_get_contact_fields()
    {
        list($user, $contact) = $this->fetchUser();

        $feild = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'contact_field_type_id' => $feild->id,
        ]);

        $response = $this->get('/people/'.$contact->hashID().'/contactfield');

        $response->assertStatus(200);

        $response->assertSee($contactField->data);
    }

    public function test_user_can_get_contact_field_types()
    {
        list($user, $contact) = $this->fetchUser();

        $feild = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $response = $this->get('/people/'.$contact->hashID().'/contactfieldtypes');

        $response->assertStatus(200);

        $response->assertSee($feild->name);
    }

    public function test_users_can_add_contact_field()
    {
        list($user, $contact) = $this->fetchUser();

        $feild = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
            'name' => 'Test Name',
            'type' => 'test',
        ]);

        $params = [
            'contact_field_type_id' => $feild->id,
            'data' => 'test_data',
        ];

        $response = $this->post('/people/'.$contact->hashID().'/contactfield', $params);

        $response->assertStatus(201);

        $params['account_id'] = $user->account_id;
        $params['contact_id'] = $contact->id;
        $params['data'] = 'test_data';

        $this->assertDatabaseHas('contact_fields', $params);

        $response = $this->get('/people/'.$contact->hashID().'/contactfield');

        $response->assertStatus(200);

        $response->assertSee('test_data');
    }

    public function test_users_can_edit_contact_field()
    {
        list($user, $contact) = $this->fetchUser();

        $params = ['data' => 'test_data'];

        $feild = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
            'name' => 'Test Name',
            'type' => 'test',
        ]);

        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'contact_field_type_id' => $feild->id,
        ]);

        $params['id'] = $contactField->id;
        $params['contact_field_type_id'] = $feild->id;

        $response = $this->put('/people/'.$contact->hashID().'/contactfield/'.$contactField->id, $params);

        $response->assertStatus(200);

        $params['account_id'] = $user->account_id;
        $params['contact_id'] = $contact->id;
        $params['data'] = 'test_data';

        $this->assertDatabaseHas('contact_fields', $params);

        $response = $this->get('/people/'.$contact->hashID().'/contactfield');

        $response->assertStatus(200);

        $response->assertSee('test_data');
    }

    public function test_users_can_delete_addresses()
    {
        list($user, $contact) = $this->fetchUser();

        $feild = factory(ContactFieldType::class)->create([
            'account_id' => $user->account_id,
        ]);

        $contactField = factory(ContactField::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'contact_field_type_id' => $feild->id,
        ]);

        $response = $this->delete('/people/'.$contact->hashID().'/contactfield/'.$contactField->id);
        $response->assertStatus(200);

        $params = ['id' => $contactField->id];

        $this->assertDatabaseMissing('contact_fields', $params);
    }
}
