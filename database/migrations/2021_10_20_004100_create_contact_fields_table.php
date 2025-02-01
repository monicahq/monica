<?php

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactInformationType;
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
        Schema::create('contact_information_types', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('name_translation_key')->nullable();
            $table->string('protocol')->nullable();
            $table->boolean('can_be_deleted')->default(true);
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_information', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ContactInformationType::class, 'type_id')->constrained('contact_information_types')->cascadeOnDelete();
            $table->string('data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('contact_information');
        Schema::dropIfExists('contact_information_types');
    }
};
