<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\ActivityCategory;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change what a nature is called, what it covers, how it is drawn and which category it is filed
 * under.
 *
 * A nature the product supplied is changed like any other: being supplied governs deletion and
 * nothing else. Moving a nature to another category puts it last there, because the order inside a
 * category is the order somebody chose and the arrival has no claim on a place in it. Nothing an
 * activity has recorded changes, since an activity names its nature rather than copying it.
 */
class UpdateActivityType
{
    public function __construct(
        private readonly User $user,
        private readonly ActivityType $activityType,
        private readonly ActivityCategory $activityCategory,
        private string $name,
        private ?string $description = null,
        private readonly ?string $icon = null,
        private readonly ?string $colour = null,
    ) {}

    public function execute(): ActivityType
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->activityType;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->description = TextSanitizer::nullablePlainText($this->description);
    }

    private function validate(): void
    {
        if ($this->activityType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This activity type is in another account.');
        }

        if ($this->activityCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This activity category is in another account.');
        }
    }

    private function update(): void
    {
        if ($this->activityCategory->id !== $this->activityType->activity_category_id) {
            $this->activityType->activity_category_id = $this->activityCategory->id;
            $this->activityType->position = $this->lastPositionInTheCategory() + 1;
        }

        $this->activityType->name = $this->name;
        $this->activityType->name_translation_key = null;
        $this->activityType->description = $this->description;
        $this->activityType->icon = $this->icon;
        $this->activityType->colour = $this->colour;
        $this->activityType->save();
    }

    private function lastPositionInTheCategory(): int
    {
        return (int) ActivityType::query()
            ->where('activity_category_id', $this->activityCategory->id)
            ->max('position');
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::ActivityTypeUpdate,
            parameters: ['name' => $this->activityType->name],
            loggable: $this->activityType,
        )->onQueue('low');
    }
}
