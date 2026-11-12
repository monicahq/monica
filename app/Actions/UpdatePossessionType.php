<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Enums\VaultColourEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\PossessionType;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Rename a possession type, give it another vault colour, and set, change or remove its icon.
 */
class UpdatePossessionType
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionType $possessionType,
        private string $name,
        private readonly VaultColourEnum $colour,
        private ?string $icon,
    ) {}

    public function execute(): PossessionType
    {
        $this->sanitize();
        $this->validate();
        $this->update();
        $this->log();

        return $this->possessionType;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
        $this->icon = $this->icon === '' ? null : $this->icon;
    }

    private function validate(): void
    {
        if ($this->possessionType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession type is in another account.');
        }

        if ($this->name === '') {
            throw ValidationException::withMessages([
                'name' => __('Write a name for this possession type.'),
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
        $this->possessionType->name = $this->name;
        $this->possessionType->colour = $this->colour->value;
        $this->possessionType->icon = $this->icon;
        $this->possessionType->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionTypeUpdate,
            parameters: ['name' => $this->possessionType->name],
            loggable: $this->possessionType,
        )->onQueue('low');
    }
}
