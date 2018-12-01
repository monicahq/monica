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
use App\Models\Contact\Address;
use App\Models\Contact\Contact;
use App\Helpers\CountriesHelper;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AddressTest extends FeatureTestCase
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

    public function test_users_can_get_countries()
    {
        $user = $this->signIn();

        $response = $this->get('/countries');

        $response->assertStatus(200);

        $countries = CountriesHelper::getAll();

        $response->assertSee($countries->first()->country);
    }

    public function test_users_can_get_addresses()
    {
        list($user, $contact) = $this->fetchUser();

        $address = factory(Address::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
            'name' => 'test',
        ]);

        $response = $this->get('/people/'.$contact->hashID().'/addresses');

        $response->assertStatus(200);

        $response->assertSee('test');
    }

    public function test_users_can_add_addresses()
    {
        list($user, $contact) = $this->fetchUser();

        $params = [
            'name' => 'test',
        ];

        $response = $this->post('/people/'.$contact->hashID().'/addresses', $params);

        $response->assertStatus(201);

        $params['account_id'] = $user->account_id;
        $params['contact_id'] = $contact->id;
        $params['name'] = 'test';

        $this->assertDatabaseHas('addresses', $params);

        $response = $this->get('/people/'.$contact->hashID().'/addresses');

        $response->assertStatus(200);

        $response->assertSee('test');
    }

    public function test_users_can_edit_addresses()
    {
        list($user, $contact) = $this->fetchUser();

        $params = [
            'name' => 'test2',
        ];

        $address = factory(Address::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
        ]);

        $response = $this->put('/people/'.$contact->hashID().'/addresses/'.$address->id, $params);

        $response->assertStatus(200);

        $params['account_id'] = $user->account_id;
        $params['contact_id'] = $contact->id;
        $params['name'] = 'test2';

        $this->assertDatabaseHas('addresses', $params);

        $response = $this->get('/people/'.$contact->hashID().'/addresses');

        $response->assertStatus(200);

        $response->assertSee('test2');
    }

    public function test_users_can_delete_addresses()
    {
        list($user, $contact) = $this->fetchUser();

        $address = factory(Address::class)->create([
            'contact_id' => $contact->id,
            'account_id' => $user->account_id,
        ]);

        $response = $this->delete('/people/'.$contact->hashID().'/addresses/'.$address->id);
        $response->assertStatus(200);

        $params = ['id' => $address->id];

        $this->assertDatabaseMissing('addresses', $params);
    }
}
