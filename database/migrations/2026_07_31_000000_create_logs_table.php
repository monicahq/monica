<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('logs', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('company_id')->comment('company the action was performed in');
            $table->unsignedBigInteger('user_id')->nullable()->comment('user who performed the action, null once that user is deleted');
            $table->string('user_email')->comment('email address of the user at the time of the action, kept so the log still says who acted once the user is gone');
            $table->string('action')->comment('action that was performed, also used as translation key');
            $table->json('parameters')->nullable()->comment('parameters for the translation');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
