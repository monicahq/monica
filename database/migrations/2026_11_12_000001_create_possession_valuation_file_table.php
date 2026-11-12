<?php

declare(strict_types=1);

use App\Models\File;
use App\Models\PossessionValuation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('possession_valuation_file', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(PossessionValuation::class)->comment('valuation the document is attached to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(File::class)->comment('the document, same account, counted once by this row')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['possession_valuation_id', 'file_id'], 'possession_valuation_file_unique');
        });
    }
};
