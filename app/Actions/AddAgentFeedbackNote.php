<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogAdministratorAction;
use App\Models\AgentFeedbackNote;
use App\Models\AgentFeedbackReport;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Leave a note on an agent feedback report for the other administrators. It changes nothing about
 * where the report stands, and nobody but the administrators ever reads it.
 */
class AddAgentFeedbackNote
{
    private AgentFeedbackNote $note;

    public function __construct(
        private readonly User $administrator,
        private readonly AgentFeedbackReport $report,
        private readonly string $body,
    ) {}

    public function execute(): AgentFeedbackNote
    {
        $this->validate();
        $this->create();
        $this->log();

        return $this->note;
    }

    private function validate(): void
    {
        if (! $this->administrator->isAdministrator()) {
            throw new ModelNotFoundException('This report does not exist.');
        }
    }

    private function create(): void
    {
        $this->note = AgentFeedbackNote::query()->create([
            'agent_feedback_report_id' => $this->report->id,
            'author_user_id' => $this->administrator->id,
            'author_name' => $this->administrator->name,
            'body' => TextSanitizer::plainText($this->body),
        ]);
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::AgentFeedbackNoteAdded,
            account: $this->report->account,
            parameters: ['reference' => "#{$this->report->id}"],
            subject: $this->report,
        )->onQueue('low');
    }
}
