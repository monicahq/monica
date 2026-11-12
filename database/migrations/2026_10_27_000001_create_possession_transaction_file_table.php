<?php

declare(strict_types=1);

use App\Models\File;
use App\Models\PossessionTransaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('possession_transaction_file', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(PossessionTransaction::class)->comment('transaction the document is attached to')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(File::class)->comment('the document, same account, counted once by this row')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['possession_transaction_id', 'file_id'], 'possession_transaction_file_unique');
        });
    }
};
