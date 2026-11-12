<?php

declare(strict_types=1);

use App\Models\Possession;
use App\Models\PossessionTag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('possession_possession_tag', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(Possession::class)->comment('possession carrying the tag')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(PossessionTag::class)->comment('tag the possession carries, same account')->constrained()->cascadeOnDelete();

            $table->unique(['possession_id', 'possession_tag_id'], 'possession_possession_tag_unique');
        });
    }
};
