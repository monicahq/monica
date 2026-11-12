<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SupportRequestStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\SupportRequest;
use App\Models\SupportStatusChange;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Tell the support team a request needs nothing more. Writing in it again reopens it.
 */
class SolveSupportRequest
{
    public function __construct(
        private readonly User $user,
        private readonly SupportRequest $supportRequest,
    ) {}

    public function execute(): SupportRequest
    {
        $this->validate();
        $this->solve();
        $this->log();

        return $this->supportRequest;
    }

    private function validate(): void
    {
        if ($this->supportRequest->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This support request is in another account.');
        }

        $this->user->authorize('solve', $this->supportRequest);
    }

    private function solve(): void
    {
        DB::transaction(function (): void {
            $previous = $this->supportRequest->status;

            $this->supportRequest->status = SupportRequestStatusEnum::Solved;
            $this->supportRequest->solved_at = now();
            $this->supportRequest->save();

            SupportStatusChange::query()->create([
                'support_request_id' => $this->supportRequest->id,
                'from_status' => $previous,
                'to_status' => SupportRequestStatusEnum::Solved,
                'user_id' => $this->user->id,
                'user_name' => $this->user->name,
            ]);
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SupportRequestSolved,
            parameters: ['subject' => $this->supportRequest->subject],
            loggable: $this->supportRequest,
        );
    }
}
