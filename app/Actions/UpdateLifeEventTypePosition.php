<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Put a life event nature somewhere else inside the category it is filed under.
 *
 * The natures of that one category are numbered from one again, the retired ones counted among them,
 * so a nature switched back on finds the place it had. A position past the end lands last. Moving a
 * nature to another category is the ordinary update, not this.
 */
class UpdateLifeEventTypePosition
{
    public function __construct(
        private readonly User $user,
        private readonly LifeEventType $lifeEventType,
        private readonly int $position,
    ) {}

    public function execute(): LifeEventType
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->lifeEventType;
    }

    private function validate(): void
    {
        if ($this->lifeEventType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event type is in another account.');
        }
    }

    private function update(): void
    {
        $types = LifeEventType::query()
            ->where('life_event_category_id', $this->lifeEventType->life_event_category_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (LifeEventType $type): bool => $type->is($this->lifeEventType))
            ->values();

        $types->splice(max(0, $this->position - 1), 0, [$this->lifeEventType]);

        DB::transaction(function () use ($types): void {
            $types->each(function (LifeEventType $type, int $index): void {
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
            action: UserActionEnum::LifeEventTypePositionUpdate,
            parameters: ['name' => $this->lifeEventType->name],
            loggable: $this->lifeEventType,
        )->onQueue('low');
    }
}
