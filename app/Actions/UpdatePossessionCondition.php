<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\PossessionCondition;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Rename a possession condition, give it another vault colour, and set, change or remove its icon.
 * It keeps its place in the order. The new name may differ from its own only by case, but not match
 * another condition of the account ignoring case.
 */
class UpdatePossessionCondition
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionCondition $possessionCondition,
        private string $name,
        private readonly VaultColourEnum $colour,
        private ?string $icon = null,
    ) {}

    public function execute(): PossessionCondition
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->possessionCondition;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->icon = $this->icon === '' ? null : $this->icon;
    }

    private function validate(): void
    {
        if ($this->possessionCondition->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession condition is in another account.');
        }

        if ($this->name === '') {
            throw ValidationException::withMessages([
                'name' => __('Write a name for this condition.'),
            ]);
        }

        $taken = $this->user->account->possessionConditions()
            ->whereKeyNot($this->possessionCondition->id)
            ->pluck('name')
            ->contains(fn (string $name): bool => mb_strtolower($name) === mb_strtolower($this->name));

        if ($taken) {
            throw ValidationException::withMessages([
                'name' => __('You already have a condition with this name.'),
            ]);
        }

        if ($this->icon !== null && ! in_array($this->icon, Section::ICONS, true)) {
            throw ValidationException::withMessages([
                'icon' => __('Pick an icon from the list.'),
            ]);
        }
    }

    private function update(): void
    {
        $this->possessionCondition->name = $this->name;
        $this->possessionCondition->colour = $this->colour->value;
        $this->possessionCondition->icon = $this->icon;
        $this->possessionCondition->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionConditionUpdate,
            parameters: ['name' => $this->possessionCondition->name],
            loggable: $this->possessionCondition,
        )->onQueue('low');
    }
}
