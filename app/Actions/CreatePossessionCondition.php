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
use Illuminate\Validation\ValidationException;

/**
 * Add a state the account's possessions can be in. It goes last in the account's order, its name
 * is unique in the account ignoring case, and its icon, when it has one, comes from the catalogue.
 */
class CreatePossessionCondition
{
    private PossessionCondition $possessionCondition;

    public function __construct(
        private readonly User $user,
        private string $name,
        private readonly VaultColourEnum $colour = VaultColourEnum::Blue,
        private ?string $icon = null,
    ) {}

    public function execute(): PossessionCondition
    {
        $this->sanitize();
        $this->validate();
        $this->create();
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
        if ($this->name === '') {
            throw ValidationException::withMessages([
                'name' => __('Write a name for this condition.'),
            ]);
        }

        $taken = $this->user->account->possessionConditions()
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

    private function create(): void
    {
        $position = (int) $this->user->account->possessionConditions()->max('position');

        $this->possessionCondition = PossessionCondition::query()->create([
            'account_id' => $this->user->account_id,
            'name' => $this->name,
            'colour' => $this->colour->value,
            'icon' => $this->icon,
            'position' => $position + 1,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionConditionCreation,
            parameters: ['name' => $this->possessionCondition->name],
            loggable: $this->possessionCondition,
        )->onQueue('low');
    }
}
