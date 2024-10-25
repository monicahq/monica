<?php

use App\Models\CallReason;
use App\Models\Contact;
use App\Models\Emotion;
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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(CallReason::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(Emotion::class)->nullable()->constrained()->nullOnDelete();
            $table->string('author_name');
            $table->datetime('called_at');
            $table->integer('duration')->nullable();
            $table->string('type');
            $table->text('description')->nullable();
            $table->boolean('answered')->default(true);
            $table->string('who_initiated');
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
        Schema::dropIfExists('calls');
    }
};
