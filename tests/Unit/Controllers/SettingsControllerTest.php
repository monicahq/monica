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



namespace Tests\Unit\Controllers;

use Tests\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SettingsControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_updates_the_default_profile_view()
    {
        $user = $this->signin();

        $response = $this->json('POST', '/settings/updateDefaultProfileView', [
            'name' => 'life-events',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'profile_active_tab' => 'life-events',
            'id' => $user->id,
        ]);

        $response = $this->json('POST', '/settings/updateDefaultProfileView', [
            'name' => 'notes',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'profile_active_tab' => 'notes',
            'id' => $user->id,
        ]);

        $response = $this->json('POST', '/settings/updateDefaultProfileView', [
            'name' => 'nawak',
        ]);

        $response->assertStatus(200);
    }
}
