<?php

use App\Models\Contact;
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
        Schema::create('contact_feed_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('description')->nullable();
            $table->nullableNumericMorphs('feedable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('contact_feed_items');
    }
};
