<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Put an activity nature somewhere else inside the category it is filed under.
 *
 * The natures of that one category are numbered from one again, the retired ones counted among them,
 * so a nature switched back on finds the place it had. A position past the end lands last. Moving a
 * nature to another category is the ordinary update, not this.
 */
class UpdateActivityTypePosition
{
    public function __construct(
        private readonly User $user,
        private readonly ActivityType $activityType,
        private readonly int $position,
    ) {}

    public function execute(): ActivityType
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->activityType;
    }

    private function validate(): void
    {
        if ($this->activityType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This activity type is in another account.');
        }
    }

    private function update(): void
    {
        $types = ActivityType::query()
            ->where('activity_category_id', $this->activityType->activity_category_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (ActivityType $type): bool => $type->is($this->activityType))
            ->values();

        $types->splice(max(0, $this->position - 1), 0, [$this->activityType]);

        DB::transaction(function () use ($types): void {
            $types->each(function (ActivityType $type, int $index): void {
                $type->position = $index + 1;
                $type->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ActivityTypePositionUpdate,
            parameters: ['name' => $this->activityType->name],
            loggable: $this->activityType,
        )->onQueue('low');
    }
}
