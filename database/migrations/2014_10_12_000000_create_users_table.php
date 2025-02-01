<?php

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('password')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('name_order')->default('%first_name% %last_name%');
            $table->string('date_format')->default('MMM DD, YYYY');
            $table->string('timezone')->nullable();
            $table->string('number_format', 8)->default(User::NUMBER_FORMAT_TYPE_LOCALE_DEFAULT);
            $table->string('default_map_site')->default(User::MAPS_SITE_OPEN_STREET_MAPS);
            $table->string('distance_format')->default(User::DISTANCE_UNIT_MILES);
            $table->boolean('is_account_administrator')->default(false);
            $table->boolean('help_shown')->default(true);
            $table->string('invitation_code')->nullable();
            $table->dateTime('invitation_accepted_at')->nullable();
            $table->string('locale')->default('en');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
