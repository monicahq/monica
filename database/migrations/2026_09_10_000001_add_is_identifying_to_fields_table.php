<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fields', function (Blueprint $table): void {
            $table->boolean('is_identifying')->default(false)->after('is_unique')->comment('whether two records sharing this value are likely the same one, so the pair is proposed as a duplicate');
        });
    }
};
