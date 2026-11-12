<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AdministratorActionEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Jobs\LogAdministratorAction;
use App\Models\SupportRequest;
use App\Models\SupportStatusChange;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a support request to another status from the administrators' queue, writing down who moved
 * it. Moving it to the status it already has changes nothing.
 */
class ChangeSupportRequestStatus
{
    public function __construct(
        private readonly User $administrator,
        private readonly SupportRequest $supportRequest,
        private readonly SupportRequestStatusEnum $status,
    ) {}

    public function execute(): SupportRequest
    {
        $this->validate();

        if ($this->supportRequest->status === $this->status) {
            return $this->supportRequest;
        }

        $this->change();
        $this->log();

        return $this->supportRequest;
    }

    private function validate(): void
    {
        if (! $this->administrator->isAdministrator()) {
            throw new ModelNotFoundException('This support request does not exist.');
        }
    }

    private function change(): void
    {
        DB::transaction(function (): void {
            $previous = $this->supportRequest->status;

            $this->supportRequest->status = $this->status;
            $this->supportRequest->solved_at = $this->status === SupportRequestStatusEnum::Solved ? now() : null;
            $this->supportRequest->save();

            SupportStatusChange::query()->create([
                'support_request_id' => $this->supportRequest->id,
                'from_status' => $previous,
                'to_status' => $this->status,
                'user_id' => $this->administrator->id,
                'user_name' => $this->administrator->name,
            ]);
        });
    }

    private function log(): void
    {
        LogAdministratorAction::dispatch(
            administrator: $this->administrator,
            action: AdministratorActionEnum::SupportRequestStatusChanged,
            account: $this->supportRequest->account,
            parameters: [
                'reference' => $this->supportRequest->reference,
                'name' => $this->supportRequest->user->name ?? '',
                'status' => $this->status->administratorLabel(),
            ],
            subject: $this->supportRequest,
        )->onQueue('low');
    }
}
