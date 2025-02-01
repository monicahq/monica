<?php

use App\Models\Account;
use App\Models\CallReasonType;
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
        Schema::create('call_reason_types', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('label_translation_key')->nullable();
            $table->timestamps();
        });

        Schema::create('call_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CallReasonType::class)->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('label_translation_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('call_reasons');
        Schema::dropIfExists('call_reason_types');
    }
};
