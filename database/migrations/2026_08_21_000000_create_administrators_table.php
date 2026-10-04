<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrators', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(User::class)->unique()->comment('person holding the administrator capacity, unique because it is either held or it is not')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'granted_by_user_id')->nullable()->comment('administrator who granted it, null when the console designated them')->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }
};
