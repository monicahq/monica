<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ContactDuplicateCandidate;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Record that two contacts the product proposed are two different people. A dismissal is permanent:
 * detection leaves a dismissed row alone, so the pair never comes back to the screen.
 */
class DismissDuplicateCandidate
{
    public function __construct(
        private readonly User $user,
        private readonly ContactDuplicateCandidate $candidate,
    ) {}

    public function execute(): ContactDuplicateCandidate
    {
        $this->validate();
        $this->dismiss();
        $this->log();

        return $this->candidate;
    }

    private function validate(): void
    {
        if ($this->candidate->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This pair is in another account.');
        }

        if (! $this->candidate->hasBothContacts()) {
            throw new ModelNotFoundException('One of these two contacts is gone.');
        }
    }

    private function dismiss(): void
    {
        if ($this->candidate->isDismissed()) {
            return;
        }

        $this->candidate->dismissed_at = now();
        $this->candidate->dismissed_by_user_id = $this->user->id;
        $this->candidate->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::DuplicateCandidateDismissed,
            vault: $this->candidate->vault,
            parameters: [
                'name' => $this->candidate->contact->name,
                'other' => $this->candidate->otherContact->name,
            ],
            loggable: $this->candidate->contact,
        );
    }
}
