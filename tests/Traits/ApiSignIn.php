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


namespace Tests\Traits;

use App\Models\User\User;
use Laravel\Passport\Passport;

trait ApiSignIn
{
    /**
     * Create a user and sign in as that user.
     *
     * @param null $user
     * @return mixed
     */
    public function signIn()
    {
        $user = factory(User::class)->create();
        Passport::actingAs($user);

        return $user;
    }
}
