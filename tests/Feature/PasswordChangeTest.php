<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PasswordChangeTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_user_can_change_password()
    {
        $user = $this->signIn();

        $user->password = $password = bcrypt('password');
        $user->save();

        $response = $this->followingRedirects()->post('/settings/security/passwordChange', [
            'password_current' => 'password',
            'password' => 'newPassword',
            'password_confirmation' => 'newPassword',
        ]);

        $response->assertStatus(200);

        $response->assertSee('Password changed successfully.');

        $user->refresh();
        $this->assertNotEquals($password, $user->password);
    }

    public function test_current_password_checked()
    {
        $user = $this->signIn();

        $user->password = bcrypt('password');
        $user->save();

        $response = $this->followingRedirects()->post('/settings/security/passwordChange', [
            'password_current' => 'xpassword',
            'password' => 'newPassword',
            'password_confirmation' => 'newPassword',
        ]);

        $response->assertStatus(200);

        $response->assertSee('Current password you entered is not correct.');
    }

    public function test_new_password_policy_check()
    {
        $user = $this->signIn();

        $user->password = bcrypt('password');
        $user->save();

        $response = $this->followingRedirects()->post('/settings/security/passwordChange', [
            'password_current' => 'password',
            'password' => 'admin',
            'password_confirmation' => 'admin',
        ], [
            'HTTP_REFERER' => '/settings/security',
        ]);

        $response->assertStatus(200);

        $response->assertSee('The password must be at least 6 characters.');
    }

    public function test_new_password_validation_check()
    {
        $user = $this->signIn();

        $user->password = bcrypt('password');
        $user->save();

        $response = $this->followingRedirects()->post('/settings/security/passwordChange', [
            'password_current' => 'password',
            'password' => 'admin0',
            'password_confirmation' => 'admin1',
        ], [
            'HTTP_REFERER' => '/settings/security',
        ]);

        $response->assertStatus(200);

        $response->assertSee('The password confirmation does not match.');
    }
}
