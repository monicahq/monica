<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DateFormatEnum;
use App\Enums\UserActionEnum;
use App\Helpers\AvailableLocales;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\Possession;
use App\Models\PossessionLocationStay;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Move a possession to another location of the account on a given day. The stay where it is now
 * closes on that day and the next one opens on the same day, and the possession's current location
 * follows.
 */
class MovePossession
{
    private PossessionLocationStay $stay;

    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly Location $location,
        private readonly CarbonImmutable $movedOn,
        private readonly ?string $reason = null,
        private readonly ?string $note = null,
    ) {}

    public function execute(): PossessionLocationStay
    {
        $this->validate();
        $this->move();
        $this->log();

        return $this->stay;
    }

    private function validate(): void
    {
        if ($this->possession->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession is in another account.');
        }

        if ($this->location->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This location is in another account.');
        }
    }

    /**
     * The possession row is locked before the open stay is read, so two moves sent at once cannot
     * both find the same open stay and leave two stays open behind them.
     */
    private function move(): void
    {
        DB::transaction(function (): void {
            Possession::query()->whereKey($this->possession->id)->lockForUpdate()->first();

            $open = PossessionLocationStay::query()
                ->where('possession_id', $this->possession->id)
                ->whereNull('left_on')
                ->first();

            $movedOn = $this->movedOn->startOfDay();

            if ($open instanceof PossessionLocationStay) {
                $this->refuse($open, $movedOn);

                $open->update(['left_on' => $movedOn]);
            }

            $this->stay = PossessionLocationStay::query()->create([
                'account_id' => $this->possession->account_id,
                'vault_id' => $this->possession->vault_id,
                'possession_id' => $this->possession->id,
                'location_id' => $this->location->id,
                'arrived_on' => $movedOn,
                'left_on' => null,
                'reason' => TextSanitizer::nullablePlainText($this->reason),
                'note' => TextSanitizer::nullablePlainText($this->note),
            ]);

            $this->possession->update(['current_location_id' => $this->location->id]);
        });
    }

    private function refuse(PossessionLocationStay $open, CarbonImmutable $movedOn): void
    {
        if ($open->location_id === $this->location->id) {
            throw ValidationException::withMessages([
                'location_id' => __('It is already in :name.', ['name' => $this->location->name]),
            ]);
        }

        if ($open->arrived_on !== null && $movedOn->lessThan($open->arrived_on)) {
            throw ValidationException::withMessages([
                'moved_on' => __('The current stay began on :date. Date the move after that, or correct the current stay instead.', [
                    'date' => $this->writeDate($open->arrived_on),
                ]),
            ]);
        }
    }

    private function writeDate(CarbonImmutable $date): string
    {
        $format = $this->user->date_format->value
            ?? (string) AvailableLocales::details(app()->getLocale())['date_format'];

        return DateFormatEnum::write($date->locale(app()->getLocale()), $format);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionMove,
            parameters: ['name' => $this->possession->name, 'location' => $this->location->name],
            loggable: $this->stay,
        )->onQueue('low');
    }
}
