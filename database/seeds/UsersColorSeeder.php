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


use App\Models\Contact\Contact;
use Illuminate\Database\Seeder;

class UsersColorSeeder extends Seeder
{
    /**
     * This is a one-time migration. It was used to populate the default
     * user color for the avatars when the feature got introduced.
     *
     * @return void
     */
    public function run()
    {
        $contacts = Contact::all();

        foreach ($contacts as $contact) {
            $contact->setAvatarColor();
            $contact->save();
        }
    }
}
