<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Jobs\RecountLastInTouchOfType;
use App\Models\LifeEvent;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Change what a nature is called, what it covers, how it is drawn, which category it is filed under
 * and whether its life events update Last in touch.
 *
 * A nature the product supplied is changed like any other: being supplied governs deletion and
 * nothing else. Moving a nature to another category puts it last there, because the order inside a
 * category is the order somebody chose and the arrival has no claim on a place in it. Nothing an
 * life event has recorded changes, since a life event names its nature rather than copying it.
 */
class UpdateLifeEventType
{
    public function __construct(
        private readonly User $user,
        private readonly LifeEventType $lifeEventType,
        private readonly LifeEventCategory $lifeEventCategory,
        private string $name,
        private ?string $description = null,
        private readonly ?string $icon = null,
        private readonly ?string $colour = null,
        private readonly ?bool $updatesLastInTouch = null,
    ) {}

    public function execute(): LifeEventType
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->resendItsUntitledLifeEvents();
        $this->recountLastInTouch();
        $this->log();

        return $this->lifeEventType;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->description = TextSanitizer::nullablePlainText($this->description);
    }

    private function validate(): void
    {
        if ($this->lifeEventType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event type is in another account.');
        }

        if ($this->lifeEventCategory->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This life event category is in another account.');
        }
    }

    /**
     * A life event with no title of its own is found by the name of its type.
     */
    private function resendItsUntitledLifeEvents(): void
    {
        if (! $this->lifeEventType->wasChanged('name')) {
            return;
        }

        LifeEvent::sendToSearch(LifeEvent::query()
            ->where('life_event_type_id', $this->lifeEventType->id)
            ->whereNull('title'));
    }

    private function recountLastInTouch(): void
    {
        if (! $this->lifeEventType->wasChanged('updates_last_in_touch')) {
            return;
        }

        RecountLastInTouchOfType::dispatch(lifeEventType: $this->lifeEventType)->onQueue('low');
    }

    private function update(): void
    {
        if ($this->lifeEventCategory->id !== $this->lifeEventType->life_event_category_id) {
            $this->lifeEventType->life_event_category_id = $this->lifeEventCategory->id;
            $this->lifeEventType->position = $this->lastPositionInTheCategory() + 1;
        }

        $this->lifeEventType->name = $this->name;
        $this->lifeEventType->name_translation_key = null;
        $this->lifeEventType->description = $this->description;
        $this->lifeEventType->icon = $this->icon;
        $this->lifeEventType->colour = $this->colour;
        $this->lifeEventType->updates_last_in_touch = $this->updatesLastInTouch ?? $this->lifeEventType->updates_last_in_touch;
        $this->lifeEventType->save();
    }

    private function lastPositionInTheCategory(): int
    {
        return (int) LifeEventType::query()
            ->where('life_event_category_id', $this->lifeEventCategory->id)
            ->max('position');
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LifeEventTypeUpdate,
            parameters: ['name' => $this->lifeEventType->name],
            loggable: $this->lifeEventType,
        );
    }
}
