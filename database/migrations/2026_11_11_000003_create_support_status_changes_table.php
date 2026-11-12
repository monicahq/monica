<?php

declare(strict_types=1);

use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_status_changes', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->foreignIdFor(SupportRequest::class)->comment('request whose status changed')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable()->comment('status before, empty when the request was opened');
            $table->string('to_status')->comment('status after');
            $table->foreignIdFor(User::class)->nullable()->comment('user who changed it')->constrained()->nullOnDelete();
            $table->string('user_name')->comment('name of that user at the time, kept if the user goes');
            $table->timestamps();

            $table->index(['support_request_id', 'created_at']);
        });
    }
};
