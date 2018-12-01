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


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPartialNotion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('is_partial')->after('gender')->default(0);
        });

        $contacts = DB::table('contacts')->get();
        foreach ($contacts as $contact) {
            if ($contact->is_kid == 1 or $contact->is_significant_other == 1) {
                DB::table('contacts')
                    ->where('id', $contact->id)
                    ->update(['is_partial' => 1]);
            }
        }

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(
                'is_significant_other',
                'is_kid'
            );
        });
    }
}
