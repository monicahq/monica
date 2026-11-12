<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\PossessionLocationStay;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Correct one stay of a possession: where it was, when it arrived, when it left, and why. The stays
 * before and after it are left as they are. Correcting where the open stay is changes where the
 * possession is now.
 */
class UpdatePossessionLocationStay
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionLocationStay $stay,
        private readonly Location $location,
        private readonly ?CarbonImmutable $arrivedOn = null,
        private readonly ?CarbonImmutable $leftOn = null,
        private readonly ?string $reason = null,
        private readonly ?string $note = null,
    ) {}

    public function execute(): PossessionLocationStay
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->stay;
    }

    private function validate(): void
    {
        if ($this->stay->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This stay is in another account.');
        }

        if ($this->location->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This location is in another account.');
        }

        if ($this->stay->isOpen() && $this->leftOn instanceof CarbonImmutable) {
            throw ValidationException::withMessages(['left_on' => __('Move the possession to close the current stay.')]);
        }

        if (! $this->stay->isOpen() && ! $this->leftOn instanceof CarbonImmutable) {
            throw ValidationException::withMessages(['left_on' => __('Enter the day it left.')]);
        }

        if ($this->leftOn instanceof CarbonImmutable && $this->arrivedOn instanceof CarbonImmutable && $this->leftOn->startOfDay()->lessThan($this->arrivedOn->startOfDay())) {
            throw ValidationException::withMessages(['left_on' => __('This is before the day it arrived.')]);
        }
    }

    private function update(): void
    {
        DB::transaction(function (): void {
            $this->stay->update([
                'location_id' => $this->location->id,
                'arrived_on' => $this->arrivedOn?->startOfDay(),
                'left_on' => $this->stay->isOpen() ? null : $this->leftOn?->startOfDay(),
                'reason' => TextSanitizer::nullablePlainText($this->reason),
                'note' => TextSanitizer::nullablePlainText($this->note),
            ]);

            if ($this->stay->isOpen()) {
                $this->stay->possession->update(['current_location_id' => $this->location->id]);
            }
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionLocationStayUpdate,
            parameters: ['name' => $this->stay->possession->name],
            loggable: $this->stay,
        )->onQueue('low');
    }
}
