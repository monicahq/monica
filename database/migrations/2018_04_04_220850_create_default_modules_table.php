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

class CreateDefaultModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('default_contact_modules', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key');
            $table->string('translation_key');
            $table->boolean('delible')->default(0);
            $table->boolean('active')->default(1);
            $table->boolean('migrated')->default(0);
            $table->timestamps();
        });

        DB::table('default_contact_modules')->insert([
        [
            'key' => 'love_relationships',
            'translation_key' => 'app.relationship_type_group_love',
        ],
        [
            'key' => 'family_relationships',
            'translation_key' => 'app.relationship_type_group_family',
        ],
        [
            'key' => 'other_relationships',
            'translation_key' => 'app.relationship_type_group_other',
        ],
        [
            'key' => 'pets',
            'translation_key' => 'people.pets_title',
        ],
        [
            'key' => 'contact_information',
            'translation_key' => 'people.section_contact_information',
        ],
        [
            'key' => 'addresses',
            'translation_key' => 'people.contact_address_title',
        ],
        [
            'key' => 'how_you_met',
            'translation_key' => 'people.introductions_sidebar_title',
        ],
        [
            'key' => 'work_information',
            'translation_key' => 'people.work_information',
        ],
        [
            'key' => 'food_preferences',
            'translation_key' => 'people.food_preferencies_title',
        ],
        [
            'key' => 'notes',
            'translation_key' => 'people.section_personal_notes',
        ],
        [
            'key' => 'phone_calls',
            'translation_key' => 'people.call_title',
        ],
        [
            'key' => 'activities',
            'translation_key' => 'people.activity_title',
        ],
        [
            'key' => 'reminders',
            'translation_key' => 'people.section_personal_reminders',
        ],
        [
            'key' => 'tasks',
            'translation_key' => 'people.section_personal_tasks',
        ],
        [
            'key' => 'gifts',
            'translation_key' => 'people.gifts_title',
        ],
        [
            'key' => 'debts',
            'translation_key' => 'people.debt_title',
        ], ]);
    }
}
