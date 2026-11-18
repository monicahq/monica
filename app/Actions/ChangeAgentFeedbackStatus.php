<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Enums\AgentFeedbackStatusEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\AgentFeedbackReport;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Move an agent feedback report along: accept it, mark it fixed, dismiss it or reopen it, writing
 * down which administrator did it. Only the moves the current status allows are accepted.
 */
class ChangeAgentFeedbackStatus
{
    public function __construct(
        private readonly User $administrator,
        private readonly AgentFeedbackReport $report,
        private readonly AgentFeedbackStatusEnum $status,
    ) {}

    public function execute(): AgentFeedbackReport
    {
        $this->validate();
        $this->change();
        $this->log();

        return $this->report;
    }

    private function validate(): void
    {
        if (! $this->administrator->isAdministrator()) {
            throw new ModelNotFoundException('This report does not exist.');
        }

        if (! in_array($this->status, $this->report->status->allowedTargets(), true)) {
            $this->refuse();
        }
    }

    private function change(): void
    {
        $changed = AgentFeedbackReport::query()
            ->whereKey($this->report->id)
            ->where('status', $this->report->status->value)
            ->update(['status' => $this->status->value]);

        $this->report->refresh();

        if ($changed === 0) {
            $this->refuse();
        }
    }

    private function refuse(): never
    {
        throw ValidationException::withMessages([
            'status' => __('A report with the status “:from” cannot be moved to “:to”.', [
                'from' => __($this->report->status->label()),
                'to' => __($this->status->label()),
            ]),
        ]);
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::AgentFeedbackStatusChanged,
            account: $this->report->account,
            parameters: [
                'reference' => "#{$this->report->id}",
                'status' => $this->status->label(),
            ],
            subject: $this->report,
        )->onQueue('low');
    }
}
