<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_feedback_reports', function (Blueprint $table): void {
            $table->id()->comment('primary key, shown to administrators as #id');
            $table->uuid()->unique()->comment('unguessable identifier used in URLs');
            $table->foreignIdFor(Account::class)->comment('account the agent was working in')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->nullable()->comment('user the agent was working for')->constrained()->nullOnDelete();
            $table->string('status')->comment('where the report stands, an AgentFeedbackStatusEnum value');
            $table->string('kind')->comment('what the report is about, an AgentFeedbackKindEnum value');
            $table->string('severity')->comment('how much it got in the way, an AgentFeedbackSeverityEnum value');
            $table->string('title', 200)->comment('one line summary written by the agent');
            $table->string('tool', 100)->nullable()->comment('MCP tool the report is about, as the agent wrote it');
            $table->text('request')->nullable()->comment('what the person asked, paraphrased by the agent');
            $table->json('steps')->nullable()->comment('tool calls the agent tried: tool, arguments, outcome, result');
            $table->text('observed')->comment('what happened');
            $table->text('expected')->comment('what should have happened');
            $table->text('proposed_fix')->comment('the fix the agent suggests');
            $table->text('proposed_code')->nullable()->comment('code the agent suggests for the fix');
            $table->string('model', 100)->nullable()->comment('model the agent says it runs on');
            $table->string('connection_name')->comment('assistant or API key the report came through');
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('tool');
        });
    }
};
