<?php

use App\Models\Account;
use App\Models\Contact;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('relationship_group_types', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->nullable();
            $table->boolean('can_be_deleted')->default(true);
            $table->timestamps();
        });

        Schema::create('relationship_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('relationship_group_type_id');
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('name_reverse_relationship');
            $table->boolean('can_be_deleted')->default(true);
            $table->timestamps();
            $table->foreign('relationship_group_type_id')->references('id')->on('relationship_group_types')->onDelete('cascade');
        });

        Schema::create('relationships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('relationship_type_id');
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class, 'related_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->timestamps();
            $table->foreign('relationship_type_id')->references('id')->on('relationship_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('relationship_group_types');
        Schema::dropIfExists('relationship_types');
        Schema::dropIfExists('relationships');
    }
};
