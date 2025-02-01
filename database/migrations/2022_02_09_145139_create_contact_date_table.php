<?php

use App\Models\Contact;
use App\Models\ContactImportantDateType;
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
        Schema::create('contact_important_date_types', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Vault::class)->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('internal_type')->nullable();
            $table->boolean('can_be_deleted')->default(true);
            $table->timestamps();
        });

        Schema::create('contact_important_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ContactImportantDateType::class)->nullable()->constrained()->nullOnDelete();
            $table->string('label');
            $table->integer('day')->nullable();
            $table->integer('month')->nullable();
            $table->integer('year')->nullable();
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
        Schema::dropIfExists('contact_important_dates');
        Schema::dropIfExists('contact_important_date_types');
    }
};
