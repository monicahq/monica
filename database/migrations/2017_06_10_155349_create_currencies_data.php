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

class CreateCurrenciesData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('iso');
            $table->string('name');
            $table->string('symbol');
        });

        //defaults
        DB::table('currencies')->insert(['iso' => 'CAD', 'name' => 'Canadian Dollar', 'symbol'=>'$']);
        DB::table('currencies')->insert(['iso' => 'USD', 'name' => 'US Dollar', 'symbol'=>'$']);
        DB::table('currencies')->insert(['iso' => 'GBP', 'name' => 'British Pound', 'symbol'=>'£']);
        DB::table('currencies')->insert(['iso' => 'EUR', 'name' => 'Euro', 'symbol'=>'€']);
        DB::table('currencies')->insert(['iso' => 'RUB', 'name' => 'Russian Ruble', 'symbol'=>'₽']);

        Schema::table('users', function (Blueprint $table) {
            $dollarResult = DB::table('currencies')->select('id')->where('iso', '=', 'USD')->value('id');
            $table->integer('currency_id')->after('timezone')->default(
            $dollarResult
          );
        });
    }
}
