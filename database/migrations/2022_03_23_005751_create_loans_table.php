<?php

use App\Models\Contact;
use App\Models\Currency;
use App\Models\Loan;
use App\Models\Vault;
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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Vault::class)->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('amount_lent')->nullable();
            $table->foreignIdFor(Currency::class)->nullable()->constrained()->nullOnDelete();
            $table->datetime('loaned_at')->nullable();
            $table->boolean('settled')->default(false);
            $table->datetime('settled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_loan', function (Blueprint $table) {
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
        Schema::dropIfExists('contact_loan');
        Schema::dropIfExists('loans');
    }
};
