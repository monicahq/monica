<?php

use App\Models\Account;
use App\Models\Contact;
use App\Models\Currency;
use App\Models\Loan;
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
        Schema::create('gift_occasions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('label_translation_key')->nullable();
            $table->integer('position')->nullable();
            $table->timestamps();
        });

        Schema::create('gift_states', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('label_translation_key')->nullable();
            $table->integer('position')->nullable();
            $table->timestamps();
        });

        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('estimated_price')->nullable();
            $table->foreignIdFor(Currency::class)->nullable()->constrained()->nullOnDelete();
            $table->datetime('received_at')->nullable();
            $table->datetime('given_at')->nullable();
            $table->datetime('bought_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_gift', function (Blueprint $table) {
            $table->foreignIdFor(Loan::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class, 'loaner_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignIdFor(Contact::class, 'loanee_id')->constrained('contacts')->cascadeOnDelete();
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
        Schema::dropIfExists('contact_gift');
        Schema::dropIfExists('gifts');
        Schema::dropIfExists('gift_states');
        Schema::dropIfExists('gift_occasions');
    }
};
