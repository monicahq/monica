<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('magic_links', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('user_id')->comment('user the link signs in');
            $table->string('token', 64)->unique()->comment('token of the link');
            $table->datetime('expires_at')->comment('when the link stops working');
            $table->datetime('used_at')->nullable()->comment('when the link was used, which is also what stops it being used twice');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('magic_links');
    }
};
