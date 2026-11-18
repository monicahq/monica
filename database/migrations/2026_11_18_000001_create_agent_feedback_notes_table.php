<?php

declare(strict_types=1);

use App\Models\AgentFeedbackReport;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_feedback_notes', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('unguessable identifier');
            $table->foreignIdFor(AgentFeedbackReport::class)->comment('report the note is about')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'author_user_id')->nullable()->comment('administrator who wrote it')->constrained()->nullOnDelete();
            $table->string('author_name')->comment('name of the author when it was written, kept if the user goes');
            $table->text('body')->comment('what was written, as plain text');
            $table->timestamps();

            $table->index(['agent_feedback_report_id', 'created_at']);
        });
    }
};
