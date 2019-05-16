<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;
use App\Models\Contact\Contact;
use LaravelWebauthn\Models\WebauthnKey;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SettingsTest extends FeatureTestCase
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

    public function test_user_can_access_settings_page()
    {
        [$user, $contact] = $this->fetchUser();

        $response = $this->get('/settings');

        $response->assertStatus(200);

        $response->assertSee(trans('settings.sidebar_settings'));
    }

    public function test_user_can_export_account()
    {
        [$user, $contact] = $this->fetchUser();

        $response = $this->get('/settings/export');

        $response->assertStatus(200);

        $response->assertSee(trans('settings.export_title'));

        $response = $this->get(route('settings.sql'));

        $response->assertStatus(200);
        $this->assertTrue($response->headers->get('content-disposition') == 'attachment; filename=monica.sql');
    }

    public function test_user_can_reset_account()
    {
        [$user, $contact] = $this->fetchUser();

        $response = $this->followingRedirects()
                        ->post(route('settings.reset'));

        $response->assertStatus(200);

        $response->assertSee('Sorry for the interruption');
    }

    public function test_user_can_delete_account()
    {
        [$user, $contact] = $this->fetchUser();

        $response = $this->followingRedirects()
            ->post(route('settings.delete'));

        $response->assertStatus(200);

        $response->assertSee('Login');
    }

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

    public function test_user_see_webauthnkeys()
    {
        $user = $this->signin();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->id,
            'updated_at' => '2019-04-01 09:18:35',
        ]);

        $this->session([
            'webauthn_auth' => true,
        ]);

        $response = $this->followingRedirects()
            ->get(route('settings.security.index'));

        $response->assertStatus(200);

        $response->assertSee($webauthnKey->name);
        $response->assertSee('2019-04-01T09:18:35Z');
    }
}
