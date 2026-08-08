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
        Schema::create('entity_types', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier used by the API, alongside the readable slug');
            $table->unsignedBigInteger('account_id')->comment('account the type belongs to');
            $table->string('name_singular')->comment('what one of them is called, used in headers and buttons');
            $table->string('name_plural')->comment('what several of them are called, used in list titles');
            $table->string('slug', 64)->comment('stable identifier used in URLs and by the API, unique within the account');
            $table->text('description')->nullable()->comment('what the type is for');
            $table->string('icon', 64)->nullable()->comment('icon shown in navigation and next to each record');
            $table->string('colour', 7)->comment('hex colour used the same way');
            $table->unsignedInteger('position')->comment('order the types appear in the navigation');
            $table->boolean('is_active')->default(true)->comment('whether new records of this type can be created');
            $table->unsignedInteger('entity_count')->default(0)->comment('running count across all vaults, shown on the settings screen');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['account_id', 'slug']);

            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });
    }
};
