<?php

declare(strict_types=1);

use App\Models\MarketplaceCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_categories', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->string('key', 64)->unique()->comment('stable identifier, used in URLs and by the API');
            $table->string('name_translation_key')->comment('translation key of the name, since the catalogue reads in the language of whoever browses it');
            $table->string('description_translation_key')->comment('translation key of the description');
            $table->string('icon', 64)->comment('icon shown on the category card');
            $table->foreignIdFor(MarketplaceCategory::class, 'parent_id')->nullable()->comment('category it sits under, null for a top level one')->constrained()->nullOnDelete();
            $table->unsignedInteger('position')->comment('order the categories are listed in');
            $table->boolean('is_active')->default(true)->comment('whether new content can still be published into it');
            $table->unsignedInteger('listing_count')->default(0)->comment('how many published listings it holds');
            $table->timestamps();

            $table->index(['parent_id', 'position']);
        });
    }
};
