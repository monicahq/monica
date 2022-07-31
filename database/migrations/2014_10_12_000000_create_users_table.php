<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // necessary for SQLlite
        Schema::enableForeignKeyConstraints();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('name_order')->default('%first_name% %last_name%');
            $table->string('date_format')->default('MMM DD, YYYY');
            $table->string('timezone')->nullable();
            $table->string('number_format')->default(User::NUMBER_FORMAT_TYPE_COMMA_THOUSANDS_DOT_DECIMAL);
            $table->string('default_map_site')->default(User::MAPS_SITE_OPEN_STREET_MAPS);
            $table->string('password')->nullable();
            $table->boolean('is_account_administrator')->default(false);
            $table->boolean('help_shown')->default(true);
            $table->string('invitation_code')->nullable();
            $table->dateTime('invitation_accepted_at')->nullable();
            $table->string('locale')->default('en');
            $table->rememberToken();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('vaults', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('type');
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('default_template_id')->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('default_template_id')->references('id')->on('templates')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('vaults');
    }
}
