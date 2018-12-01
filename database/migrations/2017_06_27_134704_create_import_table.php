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


use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImportTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('user_id');
            $table->string('type')->default('vcard');
            $table->integer('contacts_found')->nullable();
            $table->integer('contacts_skipped')->nullable();
            $table->integer('contacts_imported')->nullable();
            $table->string('filename')->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->boolean('failed')->default(0);
            $table->mediumText('failed_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('import_job_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->integer('user_id');
            $table->integer('import_job_id');
            $table->mediumText('contact_information');
            $table->boolean('skipped');
            $table->string('skip_reason')->nullable();
            $table->timestamps();
        });
    }
}
