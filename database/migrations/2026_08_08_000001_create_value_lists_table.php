<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('value_lists', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('account_id')->comment('account the list belongs to');
            $table->string('key', 64)->nullable()->comment('stable identifier for a supplied list, such as countries, null for a user made one');
            $table->string('name')->nullable()->comment('name the user gave the list, null when it falls back to its translation key');
            $table->string('name_translation_key')->nullable()->comment('translation key of a supplied name, so it reads in the user language');
            $table->text('description')->nullable()->comment('what the list is for, shown in the field builder');
            $table->boolean('is_system')->default(false)->comment('whether the product supplied it, which only affects whether the key can be relied on');
            $table->boolean('allows_user_values')->default(true)->comment('whether a user may add their own item to it');
            $table->boolean('is_shared')->default(true)->comment('whether the list is part of the account vocabulary, false when it holds the choices of one field and is listed nowhere else');
            $table->unsignedInteger('position')->comment('order the lists appear in on the settings screen');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['account_id', 'key']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });
    }
};
