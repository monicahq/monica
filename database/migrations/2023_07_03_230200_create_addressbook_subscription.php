<?php

use App\Models\AddressBookSubscription;
use App\Models\SyncToken;
use App\Models\User;
use App\Models\Vault;
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
        Schema::create('addressbook_subscriptions', function (Blueprint $table) {
            $table->uuid('id');
            $table->primary('id');
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Vault::class)->constrained()->cascadeOnDelete();

            $table->string('uri', 2096);
            $table->string('username', 1024);
            $table->string('password', 2048);
            $table->boolean('active')->default(true);
            $table->unsignedTinyInteger('sync_way')->default(AddressBookSubscription::WAY_BOTH);
            $table->string('capabilities', 2048);
            $table->string('distant_sync_token', 512)->nullable();
            $table->string('last_batch')->nullable();
            $table->unsignedBigInteger('current_logid')->nullable();
            $table->foreignIdFor(SyncToken::class)->nullable()->constrained()->nullOnDelete();
            $table->smallInteger('frequency')->default(180); // 3 hours
            $table->timestamp('last_synchronized_at', 0)->nullable();
            $table->timestamps();

            $table->foreign('last_batch')->references('id')->on('job_batches')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('addressbook_subscriptions');
    }
};
