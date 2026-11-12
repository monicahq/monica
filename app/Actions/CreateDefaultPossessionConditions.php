<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\PossessionConditionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionCondition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Give an account the six conditions a thing is usually in, from new to broken, so describing the
 * state of a possession needs no setting up first. The names are written in the language of the
 * user, and an account that already holds a condition of its own is left alone.
 */
class CreateDefaultPossessionConditions
{
    public function __construct(
        private readonly User $user,
    ) {}

    /** @return list<PossessionCondition> */
    public function execute(): array
    {
        if ($this->user->account->possessionConditions()->exists()) {
            return [];
        }

        $possessionConditions = DB::transaction(fn (): array => $this->create());

        $this->log();

        return $possessionConditions;
    }

    /** @return list<PossessionCondition> */
    private function create(): array
    {
        $locale = $this->user->locale ?? config('app.locale');
        $possessionConditions = [];
        $position = 1;

        foreach (PossessionConditionEnum::cases() as $case) {
            $possessionConditions[] = PossessionCondition::query()->create([
                'account_id' => $this->user->account_id,
                'name' => __($case->label(), [], $locale),
                'colour' => $case->colour()->value,
                'icon' => null,
                'position' => $position,
            ]);

            $position++;
        }

        return $possessionConditions;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionConditionsSeeded,
        )->onQueue('low');
    }
}
