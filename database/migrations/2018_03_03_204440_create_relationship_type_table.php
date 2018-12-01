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


use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRelationshipTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('default_relationship_type_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->boolean('delible')->default(0);
            $table->boolean('migrated')->default(0);
            $table->timestamps();
        });

        Schema::create('default_relationship_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('name_reverse_relationship');
            $table->integer('relationship_type_group_id');
            $table->boolean('delible')->default(0);
            $table->boolean('migrated')->default(0);
            $table->timestamps();
        });

        // Create new table structure
        Schema::create('relationship_types', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('name');
            $table->string('name_reverse_relationship');
            $table->integer('relationship_type_group_id');
            $table->boolean('delible')->default(0);
            $table->timestamps();
        });

        Schema::create('relationship_type_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('name');
            $table->boolean('delible')->default(0);
            $table->timestamps();
        });

        Schema::create('temp_relationships_table', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('relationship_type_id');
            $table->integer('contact_is');
            $table->string('relationship_type_name');
            $table->integer('of_contact');
            $table->timestamps();
        });
    }
}
