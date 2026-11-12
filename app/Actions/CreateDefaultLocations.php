<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DefaultLocationEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Give an account a house and five rooms inside it, so saying where a thing is kept needs no setting
 * up first. The names are written in the language of the user, and an account that already holds a
 * location of its own is left alone.
 */
class CreateDefaultLocations
{
    public function __construct(
        private readonly User $user,
    ) {}

    /** @return list<Location> */
    public function execute(): array
    {
        if ($this->user->account->locations()->exists()) {
            return [];
        }

        $locations = DB::transaction(fn (): array => $this->create());

        $this->log();

        return $locations;
    }

    /** @return list<Location> */
    private function create(): array
    {
        $locale = $this->user->locale ?? config('app.locale');
        $house = null;
        $locations = [];

        foreach (DefaultLocationEnum::cases() as $case) {
            $location = Location::query()->create([
                'account_id' => $this->user->account_id,
                'parent_id' => $case->isInsideHouse() ? $house?->id : null,
                'name' => __($case->label(), [], $locale),
                'emoji' => $case->emoji(),
                'address' => null,
            ]);

            if (! $case->isInsideHouse()) {
                $house = $location;
            }

            $locations[] = $location;
        }

        return $locations;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LocationsSeeded,
        )->onQueue('low');
    }
}
