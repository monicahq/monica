<?php
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

class CreateContactFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_field_types', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->string('name');
            $table->string('fontawesome_icon')->nullable();
            $table->string('protocol')->nullable();
            $table->boolean('delible')->default(1);
            $table->string('type')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('contact_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('contact_field_type_id');
            $table->string('data');
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('contact_field_type_id')->references('id')->on('contact_field_types')->onDelete('cascade');
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('account_id');
            $table->unsignedInteger('contact_id');
            $table->string('name')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->integer('country_id')->nullable();
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });

        Schema::create('default_contact_field_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('fontawesome_icon')->nullable();
            $table->string('protocol')->nullable();
            $table->boolean('migrated')->default(0);
            $table->boolean('delible')->default(1);
            $table->string('type')->nullable();
            $table->timestamps();
        });

        $id = DB::table('default_contact_field_types')->insertGetId([
            'name' => 'Email',
            'fontawesome_icon' => 'fa fa-envelope-open-o',
            'protocol' => 'mailto:',
            'delible' => false,
            'type' => 'email',
        ]);

        $id = DB::table('default_contact_field_types')->insertGetId([
            'name' => 'Phone',
            'fontawesome_icon' => 'fa fa-volume-control-phone',
            'protocol' => 'tel:',
            'delible' => false,
            'type' => 'phone',
        ]);

        $id = DB::table('default_contact_field_types')->insertGetId([
            'name' => 'Facebook',
            'fontawesome_icon' => 'fa fa-facebook-official',
        ]);

        $id = DB::table('default_contact_field_types')->insertGetId([
            'name' => 'Twitter',
            'fontawesome_icon' => 'fa fa-twitter-square',
        ]);

        $id = DB::table('default_contact_field_types')->insertGetId([
            'name' => 'Whatsapp',
            'fontawesome_icon' => 'fa fa-whatsapp',
        ]);

        $id = DB::table('default_contact_field_types')->insertGetId([
            'name' => 'Telegram',
            'fontawesome_icon' => 'fa fa-telegram',
            'protocol' => 'telegram:',
        ]);
    }
}
