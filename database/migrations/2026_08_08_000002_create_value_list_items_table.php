<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('value_list_items', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->unsignedBigInteger('account_id')->comment('account the item belongs to');
            $table->unsignedBigInteger('value_list_id')->comment('list the item belongs to');
            $table->string('key', 64)->nullable()->comment('stable identifier for a supplied item, such as an ISO code, null for a user made one');
            $table->string('label')->nullable()->comment('label the user gave the item, null when it falls back to its translation key');
            $table->string('label_translation_key')->nullable()->comment('translation key of a supplied label');
            $table->string('description')->nullable()->comment('clarification shown next to the label when the label alone is ambiguous');
            $table->string('colour', 7)->nullable()->comment('hex colour, used where the item is rendered as a pill');
            $table->string('icon', 64)->nullable()->comment('icon name, used the same way');
            $table->unsignedInteger('position')->comment('order the item appears in, which the user controls');
            $table->boolean('is_system')->default(false)->comment('whether the product supplied it');
            $table->boolean('is_active')->default(true)->comment('whether it can still be picked, false disables it without breaking existing records');
            $table->unsignedInteger('usage_count')->default(0)->comment('how many stored values point at it, so the user sees the cost of disabling it');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['value_list_id', 'key']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->foreign('value_list_id')->references('id')->on('value_lists')->cascadeOnDelete();
        });
    }
};
