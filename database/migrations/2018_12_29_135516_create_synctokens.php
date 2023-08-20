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
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sync_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('name')->default('contacts');
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->index(['account_id', 'user_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sync_tokens');
    }
};
