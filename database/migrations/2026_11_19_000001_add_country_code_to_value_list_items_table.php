<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('value_list_items', function (Blueprint $table): void {
            $table->string('country_code', 2)->nullable()->after('key')->comment('the ISO 3166 code of a country somebody added to the Countries list, which picks its address layout');
        });
    }
};
