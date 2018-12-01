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
        list($user, $contact) = $this->fetchUser();

        $response = $this->get('/settings');

        $response->assertStatus(200);

        $response->assertSee(trans('settings.sidebar_settings'));
    }

    public function test_user_can_export_account()
    {
        list($user, $contact) = $this->fetchUser();

        $response = $this->get('/settings/export');

        $response->assertStatus(200);

        $response->assertSee(trans('settings.export_title'));

        $response = $this->get(route('settings.sql'));

        $response->assertStatus(200);
        $this->assertTrue($response->headers->get('content-disposition') == 'attachment; filename=monica.sql');
    }

    public function test_user_can_reset_account()
    {
        list($user, $contact) = $this->fetchUser();

        $response = $this->followingRedirects()
                        ->post(route('settings.reset'));

        $response->assertStatus(200);

        $response->assertSee('Sorry for the interruption');
    }

    public function test_user_can_delete_account()
    {
        list($user, $contact) = $this->fetchUser();

        $response = $this->followingRedirects()
            ->post(route('settings.delete'));

        $response->assertStatus(200);

        $response->assertSee('Login');
    }
}
