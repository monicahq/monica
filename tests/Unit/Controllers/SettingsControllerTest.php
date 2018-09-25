<?php

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
