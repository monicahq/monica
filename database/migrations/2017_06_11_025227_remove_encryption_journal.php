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


use App\Models\Journal\Entry;
use Illuminate\Database\Migrations\Migration;

class RemoveEncryptionJournal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $entries = Entry::all();
        foreach ($entries as $entry) {
            echo $entry->id.' ';
            if (! is_null($entry->title)) {
                $entry->title = decrypt($entry->title);
            }

            if (! is_null($entry->post)) {
                $entry->post = decrypt($entry->post);
            }

            $entry->save();
        }
    }
}
