<?php

use App\Models\Account;
use App\Models\Contact;
use App\Models\RelationshipGroupType;
use App\Models\RelationshipType;
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
        Schema::create('relationship_group_types', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('name_translation_key')->nullable();
            $table->string('type')->nullable();
            $table->boolean('can_be_deleted')->default(true);
            $table->timestamps();
        });

        Schema::create('relationship_types', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(RelationshipGroupType::class)->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('name_translation_key')->nullable();
            $table->string('name_reverse_relationship')->nullable();
            $table->string('name_reverse_relationship_translation_key')->nullable();
            $table->string('type')->nullable();
            $table->boolean('can_be_deleted')->default(true);
            $table->timestamps();
        });

        Schema::create('relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(RelationshipType::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class, 'related_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('relationships');
        Schema::dropIfExists('relationship_types');
        Schema::dropIfExists('relationship_group_types');
    }
};
